<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Services\Algorithms\WordSimilarityComparator;
use Illuminate\Support\Collection;

/**
 * Calculates and applies relevance scores to search results.
 *
 * Uses WordSimilarityComparator to calculate relevance between matched value
 * and search query, then sorts results by relevance.
 */
class RelevanceScoringStage
{
    /**
     * Constructor.
     *
     * @param WordSimilarityComparator $comparator Word similarity comparator
     */
    public function __construct(
        private WordSimilarityComparator $comparator
    ) {}

    /**
     * Process search results by calculating relevance scores.
     *
     * For each result, calculates relevance between matched value and query,
     * adds relevance property to result, and sorts by relevance (lower = more relevant).
     *
     * @param SearchContext $context Search context containing results and options
     * @return array<int, object> Results with relevance scores, sorted by relevance
     */
    public function handle(SearchContext $context): array
    {
        if (empty($context->results)) {
            return [];
        }

        $scoredResults = $this->calculateRelevanceScores($context);
        $sortedResults = $this->sortByRelevance($scoredResults);
        $limitedResults = $this->applyMaxResultsLimit($sortedResults, $context);

        $context->results = $limitedResults;

        return $context->results;
    }

    /**
     * Calculate relevance scores for all results.
     *
     * @param SearchContext $context Search context
     * @return Collection<int, object> Results with relevance scores
     */
    private function calculateRelevanceScores(SearchContext $context): Collection
    {
        return collect($context->results)
            ->map(function (object $result) use ($context) {
                $relevance = $this->calculateRelevanceForResult($result, $context);
                $result->relevance = $relevance;
                return $result;
            });
    }

    /**
     * Calculate relevance score for a single result.
     *
     * @param object $result Search result
     * @param SearchContext $context Search context
     * @return float Relevance score (lower = more relevant)
     */
    private function calculateRelevanceForResult(object $result, SearchContext $context): float
    {
        $matchedValue = $result->matchedValue ?? '';
        $query = $context->query->originalQuery;

        if (empty($matchedValue) || empty($query)) {
            return 10.0;
        }

        return $this->comparator->compare($matchedValue, $query);
    }

    /**
     * Sort results by relevance (ascending - lower score = more relevant).
     *
     * @param Collection<int, object> $results Results with relevance scores
     * @return Collection<int, object> Sorted results
     */
    private function sortByRelevance(Collection $results): Collection
    {
        return $results->sortBy('relevance');
    }

    /**
     * Apply maximum results limit based on configuration.
     *
     * @param Collection<int, object> $results Sorted results
     * @param SearchContext $context Search context
     * @return array<int, object> Limited results
     */
    private function applyMaxResultsLimit(Collection $results, SearchContext $context): array
    {
        $maxResults = $context->options->maxResults ??
            config('fuzzy.default_options.max_results', 20);

        return $results
            ->take($maxResults)
            ->values()
            ->all();
    }

    /**
     * Combine relevance with original score for final ranking.
     *
     * @param Collection<int, object> $results Results with relevance scores
     * @return Collection<int, object> Results with combined scores
     */
    private function combineScores(Collection $results): Collection
    {
        return $results
            ->map(function (object $result) {
                $normalizedRelevance = $this->normalizeRelevance($result->relevance);
                $combinedScore = ($result->score * 0.7) + ($normalizedRelevance * 0.3);

                $result->combinedScore = $combinedScore;
                $result->originalScore = $result->score;
                $result->relevanceScore = $normalizedRelevance;

                return $result;
            })
            ->sortByDesc('combinedScore');
    }

    /**
     * Normalize relevance score to 0-100 scale.
     *
     * @param float $relevance Relevance score from comparator
     * @return float Normalized score (100 = perfect, 0 = poor)
     */
    private function normalizeRelevance(float $relevance): float
    {
        if ($relevance <= 0) {
            return 100.0;
        }

        $normalized = max(0, 100 - ($relevance * 10));
        return min(100, $normalized);
    }
}
