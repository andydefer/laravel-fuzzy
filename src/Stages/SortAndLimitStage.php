<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Illuminate\Support\Collection;

/**
 * SortAndLimitStage - Results sorting and limiting stage
 *
 * Filters, sorts, and limits search results based on score thresholds
 * and maximum result count parameters.
 */
class SortAndLimitStage
{
    /**
     * Process search results by filtering, sorting, and limiting
     *
     * Removes null results and those below minimum score threshold,
     * sorts remaining results by score in descending order,
     * and limits to maximum configured result count.
     *
     * @param SearchContext $context Search context containing results and options
     * @return array<int, object> Filtered, sorted, and limited results
     */
    public function handle(SearchContext $context): array
    {
        $filteredResults = $this->filterResultsByScore(
            results: $context->results,
            minScore: $context->options->minScore
        );

        $sortedResults = $this->sortResultsByScore(results: $filteredResults);
        $limitedResults = $this->limitResults(results: $sortedResults, maxResults: $context->options->maxResults);

        $context->results = $limitedResults;

        return $context->results;
    }

    /**
     * Filter results by removing null entries and those below minimum score
     *
     * @param array<int, object|null> $results Raw search results
     * @param float $minScore Minimum score threshold for result inclusion
     * @return Collection<int, object> Filtered results meeting score criteria
     */
    private function filterResultsByScore(array $results, float $minScore): Collection
    {
        return collect($results)
            ->filter(fn(?object $result): bool => $result !== null && $result->score >= $minScore);
    }

    /**
     * Sort results by score in descending order
     *
     * @param Collection<int, object> $results Collection of result objects
     * @return Collection<int, object> Results sorted by score (highest first)
     */
    private function sortResultsByScore(Collection $results): Collection
    {
        return $results->sortByDesc('score');
    }

    /**
     * Limit results to maximum allowed count
     *
     * @param Collection<int, object> $results Sorted collection of results
     * @param int $maxResults Maximum number of results to return
     * @return array<int, object> Limited results as array
     */
    private function limitResults(Collection $results, int $maxResults): array
    {
        return $results
            ->take($maxResults)
            ->values()
            ->all();
    }
}
