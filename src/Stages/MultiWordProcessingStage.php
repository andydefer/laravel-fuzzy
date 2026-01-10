<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;
use Illuminate\Support\Collection;

/**
 * Pipeline stage for processing multi-word queries.
 *
 * Calculates scores based on word coverage, cross-word matches,
 * and applies penalties for short queries and cross-word matches.
 */
class MultiWordProcessingStage
{
    private const SHORT_QUERY_PENALTY = 0.4;
    private const CROSS_WORD_MATCH_PENALTY_MULTI = 0.3;

    /**
     * Process search context for multi-word queries.
     *
     * @param SearchContext $context The search context containing query data and results
     * @param Closure $next The next pipeline handler
     * @return mixed Result from the next handler
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
     *
     * @param SearchContext $context The search context
     * @return void
     */
    private function filterSingleWordResults(SearchContext $context): void
    {
        $filteredResults = [];

        foreach ($context->results as $key => $result) {
            if ($this->isValidResult($result, $context->options->minScore)) {
                $filteredResults[$key] = $result;
            }
        }

        $context->finalResults = new Collection($filteredResults);
    }

    /**
     * Process multi-word query results.
     *
     * @param SearchContext $context The search context
     * @return void
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

            $score = $this->calculateMultiWordScoreForModel(
                $context,
                $result->modelType,
                $model->getIndexableId(),
                $context->queryWords
            );

            if ($score >= $context->options->minScore) {
                $result->score = $score;
                $finalResults[$key] = $result;
            }
        }

        $context->finalResults = new Collection($finalResults);
    }

    /**
     * Check if a result is valid.
     *
     * @param SearchResultData|null $result The result to check
     * @param float $minScore Minimum score threshold
     * @return bool True if valid
     */
    private function isValidResult(?SearchResultData $result, float $minScore = 0.0): bool
    {
        return $result !== null && $result->score >= $minScore;
    }

    /**
     * Calculate multi-word score for a specific model.
     *
     * @param SearchContext $context The search context
     * @param string $modelType The model type
     * @param mixed $modelId The model ID
     * @param array $queryWords Array of query words
     * @return float Calculated score
     */
    private function calculateMultiWordScoreForModel(
        SearchContext $context,
        string $modelType,
        $modelId,
        array $queryWords
    ): float {
        $indexEntries = $this->getModelIndexEntries($context, $modelType, $modelId);

        if (empty($indexEntries)) {
            return 0.0;
        }

        return $this->calculateScoreFromIndex($context, $indexEntries, $queryWords);
    }

    /**
     * Get all index entries for a specific model.
     *
     * @param SearchContext $context The search context
     * @param string $modelType The model type
     * @param mixed $modelId The model ID
     * @return array Array of index entries
     */
    private function getModelIndexEntries(SearchContext $context, string $modelType, $modelId): array
    {
        $indexEntries = [];

        foreach ($context->wordIndex as $word => $matches) {
            /** @var array<int, array> $matches */
            foreach ($matches as $match) {
                if ($match['indexable_type'] === $modelType && $match['indexable_id'] == $modelId) {
                    $indexEntries[] = $match;
                }
            }
        }

        return $indexEntries;
    }

    /**
     * Calculate score from index entries.
     *
     * @param SearchContext $context The search context
     * @param array $indexEntries Array of index entries
     * @param array $queryWords Array of query words
     * @return float Calculated score
     */
    private function calculateScoreFromIndex(SearchContext $context, array $indexEntries, array $queryWords): float
    {
        $matchMetrics = $this->calculateMatchMetrics($context, $indexEntries, $queryWords);

        if ($matchMetrics['matchedWordsCount'] === 0) {
            return 0.0;
        }

        return $this->computeFinalScore($matchMetrics, count($queryWords));
    }

    /**
     * Calculate match metrics for query words against index entries.
     *
     * @param SearchContext $context The search context
     * @param array $indexEntries Array of index entries
     * @param array $queryWords Array of query words
     * @return array Metrics including matched words, scores, and penalties
     */
    private function calculateMatchMetrics(SearchContext $context, array $indexEntries, array $queryWords): array
    {
        $matchedWordsCount = 0;
        $totalWordScore = 0.0;
        $hasShortQuery = false;
        $crossWordMatchCount = 0;

        foreach ($queryWords as $queryWord) {
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
     *
     * @param SearchContext $context The search context
     * @param array $indexEntries Array of index entries
     * @param string $queryWord The query word to evaluate
     * @return array Word metrics
     */
    private function calculateWordMetrics(SearchContext $context, array $indexEntries, string $queryWord): array
    {
        $bestWordScore = 0.0;
        $isCrossWordMatch = false;

        foreach ($indexEntries as $entry) {
            /** @var array<int, string> $normalizedWords */
            $normalizedWords = $entry['normalized_words'];

            foreach ($normalizedWords as $targetWord) {
                $wordScore = $context->similarityCalculator->calculateWordSimilarity($queryWord, (string) $targetWord);

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
     *
     * @param array $matchMetrics Match metrics
     * @param int $totalWords Total number of query words
     * @return float Final score
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
     * Calculate coverage bonus based on word coverage.
     *
     * @param float $coverage Word coverage ratio
     * @return float Coverage bonus
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
     *
     * @param array $matchMetrics Match metrics
     * @param int $totalWords Total number of query words
     * @return array Penalties
     */
    private function calculatePenalties(array $matchMetrics, int $totalWords): array
    {
        $shortQueryPenalty = $matchMetrics['hasShortQuery'] ? self::SHORT_QUERY_PENALTY : 0.0;
        $crossWordPenalty = ($matchMetrics['crossWordMatchCount'] / $totalWords) * self::CROSS_WORD_MATCH_PENALTY_MULTI;

        return [
            'shortQuery' => $shortQueryPenalty,
            'crossWord' => $crossWordPenalty,
        ];
    }

    /**
     * Determine if a match is likely a cross-word match.
     *
     * @param string $queryWord The query word
     * @param string $targetWord The target word from index
     * @return bool True if likely cross-word match
     */
    private function isLikelyCrossWordMatch(string $queryWord, string $targetWord): bool
    {
        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);

        if (abs($queryLength - $targetLength) > 3) {
            return true;
        }

        if (!str_contains($targetWord, $queryWord) && !str_contains($queryWord, $targetWord)) {
            $commonLength = $this->getCommonSubstringLength($queryWord, $targetWord);
            $maxLength = max($queryLength, $targetLength);

            return ($commonLength / $maxLength) < 0.5;
        }

        return false;
    }

    /**
     * Get length of longest common substring between two strings.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return int Length of longest common substring
     */
    private function getCommonSubstringLength(string $str1, string $str2): int
    {
        $maxLength = 0;
        $str1Length = strlen($str1);

        for ($i = 0; $i < $str1Length; $i++) {
            for ($j = $i + 1; $j <= $str1Length; $j++) {
                $substring = substr($str1, $i, $j - $i);
                if (str_contains($str2, $substring) && strlen($substring) > $maxLength) {
                    $maxLength = strlen($substring);
                }
            }
        }

        return $maxLength;
    }
}
