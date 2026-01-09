<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class ScoreAggregationStage
{
    private const FIELD_WEIGHTS = [
        'name' => 1.3,
        'title' => 1.2,
        'email' => 1.0,
        'description' => 0.8,
        'content' => 0.7,
        'default' => 0.6,
    ];

    private const CONSECUTIVE_BONUS = [
        2 => 1.05,  // +5%
        3 => 1.15,  // +15%
        4 => 1.30,  // +30%
        5 => 1.50,  // +50%
    ];

    public function handle(SearchContext $context, Closure $next)
    {
        if (empty($context->results)) {
            return $next($context);
        }

        $finalResults = [];

        foreach ($context->results as $key => $result) {
            if ($result === null) {
                continue;
            }

            // Récupérer toutes les entrées d'index pour ce modèle
            $indexEntries = $this->getIndexEntriesForModel($context, $result->modelType, $result->item->getIndexableId());

            if (empty($indexEntries)) {
                continue;
            }

            // Calculer le score unifié
            $finalScore = $this->calculateUnifiedScore($context, $indexEntries, $result->matchedValue);

            if ($finalScore >= $context->options->minScore) {
                $result->score = $finalScore;
                $finalResults[$key] = $result;
            }
        }

        $context->finalResults = collect($finalResults);
        return $next($context);
    }

    private function getIndexEntriesForModel(SearchContext $context, string $modelType, $modelId): array
    {
        $indexEntries = [];

        foreach ($context->wordIndex as $word => $matches) {
            foreach ($matches as $match) {
                if ($match['indexable_type'] === $modelType && $match['indexable_id'] == $modelId) {
                    $indexEntries[] = $match;
                }
            }
        }

        return $indexEntries;
    }

    private function calculateUnifiedScore(SearchContext $context, array $indexEntries, string $matchedValue): float
    {
        $queryWords = $context->queryWords;
        $totalWords = count($queryWords);

        if ($totalWords === 0) {
            return 0.0;
        }

        // 1. Vérifier la correspondance exacte de phrase
        $normalizedMatched = $context->normalizer->normalize($matchedValue);
        $normalizedQuery = $context->normalizedQuery;

        if ($normalizedMatched === $normalizedQuery) {
            return 1.0;
        }

        // 2. Vérifier si la requête est contenue dans la valeur
        if (str_contains($normalizedMatched, $normalizedQuery)) {
            return min(0.95, 0.8 + (strlen($normalizedQuery) / strlen($normalizedMatched)) * 0.2);
        }

        // 3. Calculer les scores par mot
        $wordScores = [];

        foreach ($queryWords as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            $bestWordScore = 0.0;
            $bestFieldWeight = self::FIELD_WEIGHTS['default'];

            foreach ($indexEntries as $entry) {
                $fieldWeight = self::FIELD_WEIGHTS[$entry['field']] ?? self::FIELD_WEIGHTS['default'];

                foreach ($entry['normalized_words'] as $targetWord) {
                    $targetWord = (string) $targetWord;

                    if (strlen($targetWord) < 2) {
                        continue;
                    }

                    // Calculer la similarité avancée
                    $similarity = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);

                    if ($similarity > 0) {
                        // Appliquer les bonus
                        $enhancedScore = $this->enhanceWordScore(
                            $queryWord,
                            $targetWord,
                            $similarity,
                            $entry['field'],
                            $entry['original_value'] ?? $matchedValue
                        );

                        $weightedScore = $enhancedScore * $fieldWeight;

                        if ($weightedScore > $bestWordScore) {
                            $bestWordScore = $weightedScore;
                            $bestFieldWeight = $fieldWeight;
                        }
                    }
                }
            }

            if ($bestWordScore > 0) {
                $wordScores[] = [
                    'score' => $bestWordScore,
                    'weight' => $bestFieldWeight,
                    'word' => $queryWord,
                ];
            }
        }

        // 4. Agréger les scores
        if (empty($wordScores)) {
            return 0.0;
        }

        $aggregatedScore = $this->aggregateWordScores($wordScores, $totalWords, $matchedValue);

        return min(max($aggregatedScore, 0.0), 1.0);
    }

    private function enhanceWordScore(
        string $queryWord,
        string $targetWord,
        float $baseScore,
        string $field,
        string $fullText
    ): float {
        $enhancedScore = $baseScore;

        // Bonus pour caractères consécutifs
        $consecutiveBonus = $this->calculateConsecutiveBonus($queryWord, $targetWord);
        $enhancedScore *= $consecutiveBonus;

        // Bonus pour position dans le texte
        $positionBonus = $this->calculatePositionBonus($targetWord, $fullText);
        $enhancedScore *= $positionBonus;

        return min($enhancedScore, 1.0);
    }

    private function calculateConsecutiveBonus(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower($queryWord);
        $targetWord = strtolower($targetWord);

        $maxConsecutive = 0;
        $queryLength = strlen($queryWord);

        // Chercher la plus longue sous-chaîne consécutive commune
        for ($i = 0; $i < $queryLength; $i++) {
            for ($j = $i + 2; $j <= $queryLength; $j++) {
                $substring = substr($queryWord, $i, $j - $i);
                if (str_contains($targetWord, $substring)) {
                    $maxConsecutive = max($maxConsecutive, strlen($substring));
                }
            }
        }

        if ($maxConsecutive >= 2) {
            return self::CONSECUTIVE_BONUS[min($maxConsecutive, 5)] ?? 1.0;
        }

        return 1.0;
    }

    private function calculatePositionBonus(string $word, string $fullText): float
    {
        $fullText = strtolower($fullText);
        $word = strtolower($word);

        $position = strpos($fullText, $word);

        if ($position === false) {
            return 1.0;
        }

        $textLength = strlen($fullText);
        $wordLength = strlen($word);
        $relativePosition = $position / max(1, $textLength - $wordLength);

        // Les mots au début ont plus de poids
        if ($relativePosition < 0.2) {
            return 1.2; // +20%
        } elseif ($relativePosition < 0.4) {
            return 1.1; // +10%
        }

        return 1.0;
    }

    private function aggregateWordScores(array $wordScores, int $totalQueryWords, string $matchedValue): float
    {
        $totalScore = 0.0;
        $totalWeight = 0.0;
        $matchedWords = count($wordScores);

        foreach ($wordScores as $wordScore) {
            $totalScore += $wordScore['score'] * $wordScore['weight'];
            $totalWeight += $wordScore['weight'];
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        $averageScore = $totalScore / $totalWeight;

        // Bonus de couverture pour requêtes multi-mots
        if ($totalQueryWords > 1) {
            $coverage = $matchedWords / $totalQueryWords;

            if ($coverage >= 0.8) {
                $averageScore = min($averageScore * 1.2, 1.0);
            } elseif ($coverage >= 0.5) {
                $averageScore = min($averageScore * 1.1, 1.0);
            }
        }

        return $averageScore;
    }
}
