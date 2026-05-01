<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Fuzzy\Data\SearchResultData;
use Illuminate\Support\Collection;

/**
 * Interface for filtering and sorting search results.
 *
 * Defines the contract for processing raw search results by applying
 * score thresholds, removing invalid entries, and sorting by relevance.
 *
 * @package Fuzzy\Contracts
 */
interface ResultFilterInterface
{
    /**
     * Filter and sort search results by relevance score.
     *
     * Removes results that don't meet the minimum score threshold,
     * filters out null or invalid entries, and sorts the remaining
     * results in descending order by their score.
     *
     * @param Collection<int, SearchResultData> $results Raw search results collection
     * @param float $minScore Minimum score threshold (0.0 to 1.0)
     * @return Collection<int, SearchResultData> Filtered and sorted results
     */
    public function filterAndSort(Collection $results, float $minScore): Collection;
}
