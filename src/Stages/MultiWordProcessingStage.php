<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;
use Illuminate\Support\Collection;

/**
 * Pipeline stage for processing multi-word queries.
 */
class MultiWordProcessingStage extends BaseScoringStage
{
    /**
     * Process search context for multi-word queries.
     */
    public function handle(SearchContext $context, Closure $next)
    {
        if (!$context->hasMultipleWords) {
            $this->filterSingleWordResults($context);
            return $next($context);
        }

        $this->processMultiWordResults($context);
        return $next($context);
    }

    /**
     * Filter single word results by minimum score.
     */
    private function filterSingleWordResults(SearchContext $context): void
    {
        $filteredResults = $this->filterResultsByScore($context->results, $context->options->minScore);
        $context->finalResults = new Collection($filteredResults);
    }

    /**
     * Process multi-word query results.
     */
    private function processMultiWordResults(SearchContext $context): void
    {
        $finalResults = [];

        foreach ($context->results as $key => $result) {
            if (!$this->isValidResult($result)) {
                continue;
            }

            $model = $context->getModelInstance($key);
            if (!$model) {
                continue;
            }

            $score = $this->calculateMultiWordScore(
                $context,
                $result->modelType,
                $model->getIndexableId()
            );

            if ($score >= $context->options->minScore) {
                $result->score = $score;
                $finalResults[$key] = $result;
            }
        }

        $context->finalResults = new Collection($finalResults);
    }

    /**
     * Calculate multi-word score for a model.
     */
    private function calculateMultiWordScore(
        SearchContext $context,
        string $modelType,
        $modelId
    ): float {
        $indexEntries = $this->getIndexEntriesForModel($context, $modelType, $modelId);

        if (empty($indexEntries)) {
            return 0.0;
        }

        return $this->calculateScoreFromIndex($context, $indexEntries);
    }

    /**
     * Calculate score from index entries.
     */
    private function calculateScoreFromIndex(SearchContext $context, array $indexEntries): float
    {
        $matchMetrics = $this->calculateMatchMetrics($context, $indexEntries);

        if ($matchMetrics['matchedWordsCount'] === 0) {
            return 0.0;
        }

        return $this->computeFinalScore($matchMetrics, count($context->queryWords));
    }

    /**
     * Calculate match metrics for query words.
     */
    private function calculateMatchMetrics(SearchContext $context, array $indexEntries): array
    {
        $matchedWordsCount = 0;
        $totalWordScore = 0.0;
        $hasShortQuery = false;
        $crossWordMatchCount = 0;

        foreach ($context->queryWords as $queryWord) {
            $wordMetrics = $this->calculateWordMetrics($context, $indexEntries, $queryWord);

            if ($wordMetrics['bestWordScore'] > 0) {
                $matchedWordsCount++;
                $totalWordScore += $wordMetrics['bestWordScore'];

                if ($wordMetrics['isCrossWordMatch']) {
                    $crossWordMatchCount++;
                }
            }

            if (!$hasShortQuery && strlen($queryWord) < 4) {
                $hasShortQuery = true;
            }
        }

        return [
            'matchedWordsCount' => $matchedWordsCount,
            'totalWordScore' => $totalWordScore,
            'hasShortQuery' => $hasShortQuery,
            'crossWordMatchCount' => $crossWordMatchCount,
        ];
    }

    /**
     * Calculate metrics for a single query word.
     */
    private function calculateWordMetrics(
        SearchContext $context,
        array $indexEntries,
        string $queryWord
    ): array {
        $bestWordScore = 0.0;
        $isCrossWordMatch = false;

        foreach ($indexEntries as $entry) {
            foreach ($entry['normalized_words'] as $targetWord) {
                $wordScore = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    (string) $targetWord
                );

                if ($wordScore > 0 && $this->isLikelyCrossWordMatch($queryWord, (string) $targetWord)) {
                    $isCrossWordMatch = true;
                    $wordScore *= 0.7;
                }

                $bestWordScore = max($bestWordScore, $wordScore);
            }
        }

        return [
            'bestWordScore' => $bestWordScore,
            'isCrossWordMatch' => $isCrossWordMatch,
        ];
    }

    /**
     * Compute final score from match metrics.
     */
    private function computeFinalScore(array $matchMetrics, int $totalWords): float
    {
        $coverage = $matchMetrics['matchedWordsCount'] / $totalWords;
        $averageScore = $matchMetrics['totalWordScore'] / $matchMetrics['matchedWordsCount'];

        $coverageBonus = $this->calculateCoverageBonus($coverage);
        $penalties = $this->calculatePenalties($matchMetrics, $totalWords);

        $baseScore = $averageScore * (1 + $coverage) + $coverageBonus;
        $penalizedScore = $baseScore * (1 - $penalties['shortQuery'] - $penalties['crossWord']);

        return max(0.0, min($penalizedScore, 1.0));
    }

    /**
     * Calculate coverage bonus.
     */
    private function calculateCoverageBonus(float $coverage): float
    {
        return match (true) {
            $coverage === 1.0 => 0.3,
            $coverage >= 0.75 => 0.15,
            default => 0.0,
        };
    }

    /**
     * Calculate penalties for short queries and cross-word matches.
     */
    private function calculatePenalties(array $matchMetrics, int $totalWords): array
    {
        $config = config('fuzzy.scoring.penalties', [
            'short_query' => 0.4,
            'cross_word_match_multi' => 0.3,
        ]);

        $shortQueryPenalty = $matchMetrics['hasShortQuery'] ? $config['short_query'] : 0.0;
        $crossWordPenalty = ($matchMetrics['crossWordMatchCount'] / $totalWords) * $config['cross_word_match_multi'];

        return [
            'shortQuery' => $shortQueryPenalty,
            'crossWord' => $crossWordPenalty,
        ];
    }

    /**
     * Determine if a match is likely a cross-word match.
     */
    private function isLikelyCrossWordMatch(string $queryWord, string $targetWord): bool
    {
        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);

        if (abs($queryLength - $targetLength) > 3) {
            return true;
        }

        if (!str_contains($targetWord, $queryWord) && !str_contains($queryWord, $targetWord)) {
            $lcsAlgorithm = new \Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm();
            $similarity = $lcsAlgorithm->calculate($queryWord, $targetWord);
            return $similarity < 0.5;
        }

        return false;
    }
}
