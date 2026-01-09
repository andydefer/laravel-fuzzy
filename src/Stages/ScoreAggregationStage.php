<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class ScoreAggregationStage
{
    private const FIELD_WEIGHTS = [
        'name' => 1.2,
        'title' => 1.1,
        'email' => 1.0,
        'description' => 0.8,
        'content' => 0.7,
        'default' => 0.6,
    ];

    private const CONSECUTIVE_BONUS = [
        2 => 1.1,   // +10%
        3 => 1.3,   // +30%
        4 => 1.6,   // +60%
        5 => 2.0,   // +100%
    ];

    private const POSITION_BONUS = [
        'start' => 1.3,
        'middle' => 1.0,
        'end' => 1.2,
    ];

    private const MULTI_WORD_PENALTY = [
        'single_word' => 1.0,
        'multi_word_partial' => 0.8,
        'multi_word_dispersed' => 0.5,
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

        $wordScores = [];
        $hasMultiWordQuery = $totalWords > 1;
        $exactPhraseMatch = false;

        // 1. Vérifier la correspondance exacte de phrase
        $normalizedMatched = $context->normalizer->normalize($matchedValue);
        $normalizedQuery = $context->normalizedQuery;

        if ($normalizedMatched === $normalizedQuery) {
            return 1.0; // Correspondance parfaite de phrase
        }

        // 2. Calculer les scores par mot
        foreach ($queryWords as $queryWord) {
            $bestWordScore = 0.0;
            $bestFieldWeight = self::FIELD_WEIGHTS['default'];

            foreach ($indexEntries as $entry) {
                $fieldWeight = self::FIELD_WEIGHTS[$entry['field']] ?? self::FIELD_WEIGHTS['default'];

                foreach ($entry['normalized_words'] as $targetWord) {
                    $targetWord = (string) $targetWord;

                    // Calculer la similarité de base
                    $similarity = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);

                    if ($similarity > 0) {
                        // Appliquer les bonus/pénalités
                        $enhancedScore = $this->enhanceWordScore(
                            $queryWord,
                            $targetWord,
                            $similarity,
                            $entry['field'],
                            $matchedValue
                        );

                        $weightedScore = $enhancedScore * $fieldWeight;

                        if ($weightedScore > $bestWordScore) {
                            $bestWordScore = $weightedScore;
                            $bestFieldWeight = $fieldWeight;
                        }
                    }
                }
            }

            $wordScores[] = [
                'score' => $bestWordScore,
                'weight' => $bestFieldWeight,
                'word' => $queryWord,
            ];
        }

        // 3. Agréger les scores des mots
        $aggregatedScore = $this->aggregateWordScores($wordScores, $hasMultiWordQuery, $matchedValue);

        // 4. Appliquer les pénalités multi-mots si nécessaire
        if ($hasMultiWordQuery) {
            $multiWordPenalty = $this->calculateMultiWordPenalty($queryWords, $matchedValue);
            $aggregatedScore *= $multiWordPenalty;
        }

        // 5. Limiter le score entre 0 et 1
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

        // Bonus pour position dans le mot
        $positionBonus = $this->calculatePositionBonus($targetWord, $fullText);
        $enhancedScore *= $positionBonus;

        // Bonus pour champ important
        $fieldBonus = $this->calculateFieldBonus($field);
        $enhancedScore *= $fieldBonus;

        // Pénalité pour dispersion (non-consécutif)
        $dispersionPenalty = $this->calculateDispersionPenalty($queryWord, $targetWord);
        $enhancedScore *= $dispersionPenalty;

        return min($enhancedScore, 1.0);
    }

    private function calculateConsecutiveBonus(string $queryWord, string $targetWord): float
    {
        $maxConsecutive = 0;
        $queryLength = strlen($queryWord);

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
        $position = strpos(strtolower($fullText), strtolower($word));

        if ($position === false) {
            return self::POSITION_BONUS['middle'];
        }

        $textLength = strlen($fullText);
        $wordLength = strlen($word);
        $relativePosition = $position / max(1, $textLength - $wordLength);

        if ($relativePosition < 0.3) {
            return self::POSITION_BONUS['start'];
        } elseif ($relativePosition > 0.7) {
            return self::POSITION_BONUS['end'];
        }

        return self::POSITION_BONUS['middle'];
    }

    private function calculateFieldBonus(string $field): float
    {
        return self::FIELD_WEIGHTS[$field] ?? self::FIELD_WEIGHTS['default'];
    }

    private function calculateDispersionPenalty(string $queryWord, string $targetWord): float
    {
        // Si la requête est courte (< 4) et non consécutive, pénalité
        if (strlen($queryWord) >= 3 && strlen($queryWord) <= 6) {
            $hasConsecutive = $this->hasConsecutiveMatch($queryWord, $targetWord);
            if (!$hasConsecutive) {
                return 0.7; // -30%
            }
        }

        return 1.0;
    }

    private function hasConsecutiveMatch(string $query, string $text): bool
    {
        for ($len = strlen($query); $len >= 3; $len--) {
            for ($i = 0; $i <= strlen($query) - $len; $i++) {
                $substring = substr($query, $i, $len);
                if (str_contains($text, $substring)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function aggregateWordScores(array $wordScores, bool $hasMultiWordQuery, string $matchedValue): float
    {
        if (empty($wordScores)) {
            return 0.0;
        }

        $totalScore = 0.0;
        $totalWeight = 0.0;
        $matchedWords = 0;

        foreach ($wordScores as $wordScore) {
            if ($wordScore['score'] > 0) {
                $totalScore += $wordScore['score'] * $wordScore['weight'];
                $totalWeight += $wordScore['weight'];
                $matchedWords++;
            }
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        $averageScore = $totalScore / $totalWeight;

        // Bonus de couverture pour requêtes multi-mots
        if ($hasMultiWordQuery && count($wordScores) > 1) {
            $coverage = $matchedWords / count($wordScores);
            $coverageBonus = $this->calculateCoverageBonus($coverage);
            $averageScore = $averageScore * (1 - $coverageBonus) + $coverageBonus;
        }

        return min($averageScore, 1.0);
    }

    private function calculateCoverageBonus(float $coverage): float
    {
        if ($coverage >= 1.0) return 0.3;   // +30% si tous les mots correspondent
        if ($coverage >= 0.75) return 0.15; // +15% si 75% des mots
        if ($coverage >= 0.5) return 0.05;  // +5% si 50% des mots
        return 0.0;
    }

    private function calculateMultiWordPenalty(array $queryWords, string $matchedValue): float
    {
        $words = preg_split('/[\s\-_,\.]+/', strtolower($matchedValue));

        if (count($words) <= 1) {
            return self::MULTI_WORD_PENALTY['single_word'];
        }

        // Vérifier si la requête est dispersée sur plusieurs mots
        $queryChars = implode('', $queryWords);
        $foundInSingleWord = false;
        $foundInMultipleWords = 0;

        foreach ($words as $word) {
            $charsFound = 0;
            foreach (str_split($queryChars) as $char) {
                if (str_contains($word, $char)) {
                    $charsFound++;
                }
            }

            if ($charsFound >= 3) {
                $foundInSingleWord = true;
            }
            if ($charsFound > 0) {
                $foundInMultipleWords++;
            }
        }

        if ($foundInSingleWord) {
            return self::MULTI_WORD_PENALTY['multi_word_partial'];
        }

        if ($foundInMultipleWords > 1) {
            // Pénalité sévère pour dispersion multi-mots
            $dispersionRatio = $foundInMultipleWords / min(count($words), strlen($queryChars));
            if ($dispersionRatio > 0.8) {
                return 0.3; // -70%
            } elseif ($dispersionRatio > 0.5) {
                return 0.5; // -50%
            }
        }

        return self::MULTI_WORD_PENALTY['multi_word_dispersed'];
    }
}
