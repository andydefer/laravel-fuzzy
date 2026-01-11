<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;
use Illuminate\Support\Collection;

/**
 * Score aggregation and unified calculation stage.
 */
class ScoreAggregationStage extends BaseScoringStage
{
    private const CONSECUTIVE_BONUS = [
        2 => 1.05,
        3 => 1.15,
        4 => 1.30,
        5 => 1.50,
    ];

    /**
     * Calculates final scores by aggregating different similarity metrics.
     */
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

            $finalScore = $this->computeFinalScoreForResult($context, $result, $key);

            if ($finalScore >= $context->options->minScore) {
                $result->score = $finalScore;
                $finalResults[$key] = $result;
            }
        }

        $context->finalResults = collect($finalResults);
        return $next($context);
    }

    /**
     * Calculates the final score for a given result.
     */
    private function computeFinalScoreForResult(
        SearchContext $context,
        SearchResultData $result,
        string $resultKey
    ): float {
        // CORRECTION : Utiliser le context pour obtenir le vrai modèle
        $model = $context->getModelInstance($resultKey);

        if (!$model) {
            return 0.0;
        }

        $indexEntries = $this->getIndexEntriesForModel(
            $context,
            $result->modelType,
            $model->getIndexableId()  // <-- Ici on utilise le vrai modèle !
        );

        if (empty($indexEntries)) {
            return 0.0;
        }

        $finalScore = $this->calculateUnifiedScore($context, $indexEntries, $result->matchedValue);
        return min(max($finalScore, 0.0), 1.0);
    }

    /**
     * Retrieves all index entries for a specific model.
     */
    protected function getIndexEntriesForModel(SearchContext $context, string $modelType, $modelId): array
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

    /**
     * Calculates a unified score based on multiple similarity metrics.
     */
    private function calculateUnifiedScore(SearchContext $context, array $indexEntries, string $matchedValue): float
    {
        $queryWords = $context->queryWords;
        $totalWords = count($queryWords);

        if ($totalWords === 0) {
            return 0.0;
        }

        $exactMatchScore = $this->calculateExactMatchScore($context, $matchedValue);
        if ($exactMatchScore > 0) {
            return $exactMatchScore;
        }

        $wordScores = $this->calculateWordScores($context, $queryWords, $indexEntries);

        if (empty($wordScores)) {
            return 0.0;
        }

        return $this->aggregateWordScores($wordScores, $totalWords, $matchedValue);
    }

    /**
     * Calculates score for exact phrase matches.
     */
    private function calculateExactMatchScore(SearchContext $context, string $matchedValue): float
    {
        $normalizedMatched = $context->normalizer->normalize($matchedValue);
        $normalizedQuery = $context->normalizedQuery;

        if ($normalizedMatched === $normalizedQuery) {
            return 1.0;
        }

        if (str_contains($normalizedMatched, $normalizedQuery)) {
            return min(0.95, 0.8 + (strlen($normalizedQuery) / strlen($normalizedMatched)) * 0.2);
        }

        return 0.0;
    }

    /**
     * Calculates individual word scores for each query word.
     */
    private function calculateWordScores(SearchContext $context, array $queryWords, array $indexEntries): array
    {
        $wordScores = [];

        foreach ($queryWords as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            $bestWordScore = $this->findBestWordScore($context, $queryWord, $indexEntries);

            if ($bestWordScore['score'] > 0) {
                $wordScores[] = $bestWordScore;
            }
        }

        return $wordScores;
    }

    /**
     * Finds the best score for a single query word.
     */
    private function findBestWordScore(SearchContext $context, string $queryWord, array $indexEntries): array
    {
        $fieldWeights = $this->getFieldWeights();
        $bestScore = 0.0;
        $bestFieldWeight = $fieldWeights['default'];

        foreach ($indexEntries as $entry) {
            $fieldWeight = $fieldWeights[$entry['field']] ?? $fieldWeights['default'];

            foreach ($entry['normalized_words'] as $targetWord) {
                $targetWord = (string) $targetWord;

                if (strlen($targetWord) < 2) {
                    continue;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);

                if ($similarity > 0) {
                    $enhancedScore = $this->enhanceWordScore(
                        $queryWord,
                        $targetWord,
                        $similarity,
                        $entry['field'],
                        $entry['original_value'] ?? ''
                    );

                    $weightedScore = $enhancedScore * $fieldWeight;

                    if ($weightedScore > $bestScore) {
                        $bestScore = $weightedScore;
                        $bestFieldWeight = $fieldWeight;
                    }
                }
            }
        }

        return [
            'score' => $bestScore,
            'weight' => $bestFieldWeight,
            'word' => $queryWord,
        ];
    }

    /**
     * Enhances a base similarity score with various bonuses.
     */
    private function enhanceWordScore(
        string $queryWord,
        string $targetWord,
        float $baseScore,
        string $field,
        string $fullText
    ): float {
        $enhancedScore = $baseScore;

        $consecutiveBonus = $this->calculateConsecutiveBonus($queryWord, $targetWord);
        $enhancedScore *= $consecutiveBonus;

        $positionBonus = $this->calculatePositionBonus($targetWord, $fullText);
        $enhancedScore *= $positionBonus;

        return min($enhancedScore, 1.0);
    }

    /**
     * Calculates bonus for consecutive character matches.
     */
    private function calculateConsecutiveBonus(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower($queryWord);
        $targetWord = strtolower($targetWord);

        $maxConsecutive = $this->findLongestCommonSubstring($queryWord, $targetWord);

        if ($maxConsecutive >= 2) {
            return self::CONSECUTIVE_BONUS[min($maxConsecutive, 5)] ?? 1.0;
        }

        return 1.0;
    }

    /**
     * Finds the longest common substring between two words.
     */
    private function findLongestCommonSubstring(string $queryWord, string $targetWord): int
    {
        $queryLength = strlen($queryWord);
        $maxConsecutive = 0;

        for ($i = 0; $i < $queryLength; $i++) {
            for ($j = $i + 2; $j <= $queryLength; $j++) {
                $substring = substr($queryWord, $i, $j - $i);
                if (str_contains($targetWord, $substring)) {
                    $maxConsecutive = max($maxConsecutive, strlen($substring));
                }
            }
        }

        return $maxConsecutive;
    }

    /**
     * Calculates bonus based on word position in text.
     */
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

        if ($relativePosition < 0.2) {
            return 1.2;
        } elseif ($relativePosition < 0.4) {
            return 1.1;
        }

        return 1.0;
    }

    /**
     * Aggregates individual word scores into a final unified score.
     */
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

        if ($totalQueryWords > 1) {
            $coverage = $matchedWords / $totalQueryWords;
            $averageScore = $this->applyCoverageBonus($averageScore, $coverage);
        }

        return $averageScore;
    }

    /**
     * Applies coverage bonus for multi-word queries.
     */
    private function applyCoverageBonus(float $baseScore, float $coverage): float
    {
        if ($coverage >= 0.8) {
            return min($baseScore * 1.2, 1.0);
        } elseif ($coverage >= 0.5) {
            return min($baseScore * 1.1, 1.0);
        }

        return $baseScore;
    }
}
