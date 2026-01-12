<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;

/**
 * Interface for scoring strategies
 *
 * Defines the contract for all scoring algorithms used to rank search results.
 * Each strategy determines how well a search result matches the query based on
 * specific criteria and patterns.
 */
interface ScoringStrategy
{
    /**
     * Check if this strategy should be applied to the given search context and index entry
     *
     * @param SearchContext $context The current search context containing query and options
     * @param array $indexEntry The indexed data entry to evaluate
     * @return bool True if this strategy should be applied, false otherwise
     */
    public function supports(SearchContext $context, array $indexEntry): bool;

    /**
     * Calculate the relevance score for the index entry
     *
     * @param SearchContext $context The current search context containing query and options
     * @param array $indexEntry The indexed data entry to score
     * @return float The calculated relevance score (higher = more relevant)
     */
    public function calculate(SearchContext $context, array $indexEntry): float;

    /**
     * Get the priority of this strategy for ordering
     *
     * Strategies with higher priority values are evaluated first.
     * This allows certain scoring factors to take precedence over others.
     *
     * @return int The priority value (higher = evaluated earlier)
     */
    public function getPriority(): int;
}
