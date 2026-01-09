<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class FuzzyMatchStage
{
    private const QUERY_LENGTH_BONUS = [
        1 => 0.1,
        2 => 0.3,
        3 => 0.6,
        4 => 0.8,
        5 => 1.0,
        6 => 1.1,
        7 => 1.2,
    ];

    public function handle(SearchContext $context, Closure $next)
    {
        if (!$context->options->fuzzy) {
            return $next($context);
        }

        foreach ($context->queryWords as $queryWord) {
            if (strlen($queryWord) >= 2) {
                foreach ($context->wordIndex as $indexedWord => $matches) {
                    $wordSimilarity = $context->similarityCalculator->calculateWordSimilarity(
                        $queryWord,
                        (string) $indexedWord
                    );

                    if ($wordSimilarity >= $context->options->threshold) {
                        $this->processMatches($context, $queryWord, $matches, $wordSimilarity);
                    }
                }
            }
        }

        return $next($context);
    }

    private function processMatches(
        SearchContext $context,
        string $queryWord,
        array $matches,
        float $wordSimilarity
    ): void {
        foreach ($matches as $match) {
            $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

            if (isset($context->seen[$resultKey])) {
                continue;
            }

            $bestWordScore = $this->calculateBestWordScore($context, $queryWord, $match['normalized_words']);

            if ($bestWordScore >= $context->options->threshold) {
                $finalScore = $this->calculateAdjustedScore(
                    $bestWordScore,
                    $queryWord,
                    $context->hasMultipleWords,
                    $match['field'],
                    $match['weight'],
                    $wordSimilarity
                );

                $model = $context->getModelInstance($resultKey);
                if ($model && $finalScore >= $context->options->minScore) {
                    $context->results[$resultKey] = new SearchResultData(
                        item: $model,
                        score: $finalScore,
                        modelType: $match['indexable_type'],
                        matchedField: $match['field'],
                        matchedValue: $match['original_value']
                    );
                    $context->seen[$resultKey] = true;
                }
            }
        }
    }

    private function calculateBestWordScore(SearchContext $context, string $queryWord, array $targetWords): float
    {
        $bestWordScore = 0.0;

        foreach ($targetWords as $targetWord) {
            $targetWord = (string) $targetWord;
            $wordScore = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);
            $bestWordScore = max($bestWordScore, $wordScore);
        }

        return $bestWordScore;
    }

    private function calculateAdjustedScore(
        float $baseScore,
        string $queryWord,
        bool $hasMultipleWords,
        string $field,
        float $fieldWeight,
        float $wordSimilarity
    ): float {
        $adjustedScore = $baseScore * $fieldWeight;

        if ($hasMultipleWords) {
            $adjustedScore *= 0.8;
        }

        $lengthBonus = $this->getQueryLengthBonus($queryWord);
        $adjustedScore *= $lengthBonus;

        // Penalize low similarity
        if ($wordSimilarity < 0.3) {
            $adjustedScore *= 0.5;
        } elseif ($wordSimilarity < 0.6) {
            $adjustedScore *= 0.8;
        }

        return min($adjustedScore, 1.0);
    }

    private function getQueryLengthBonus(string $queryWord): float
    {
        $length = strlen($queryWord);

        if ($length < 3) return 0.5;
        if ($length < 4) return 0.7;

        return self::QUERY_LENGTH_BONUS[min($length, 7)] ?? 1.0;
    }
}
