<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\Contracts\SearchContextInterface;

/**
 * Interface for scoring strategies
 *
 * Defines the contract for all scoring algorithms used to rank search results.
 * Each strategy determines how well a search result matches the query based on
 * specific criteria and patterns.
 * 
 * Scoring strategies follow the Strategy pattern and are used in a chain
 * of responsibility to calculate composite relevance scores.
 */
interface ScoringStrategyInterface
{
    /**
     * Check if this strategy should be applied to the given search context and index entry
     *
     * Determines whether this scoring strategy is applicable to the current
     * search context and index entry. For example, exact match strategy only
     * applies when the query exactly matches the indexed value.
     *
     * @param SearchContextInterface $context The current search context containing query and options
     * @param array<string, mixed> $indexEntry The indexed data entry to evaluate containing:
     *                                         - field: The field name
     *                                         - original_value: Original text value
     *                                         - normalized_words: Array of normalized words
     *                                         - weight: Field weight multiplier
     * @return bool True if this strategy should be applied, false otherwise
     */
    public function supports(SearchContextInterface $context, array $indexEntry): bool;

    /**
     * Calculate the relevance score for the index entry
     *
     * Computes a relevance score based on the strategy's specific algorithm.
     * The score should be normalized between 0.0 (no match) and 1.0 (perfect match).
     *
     * @param SearchContextInterface $context The current search context containing query and options
     * @param array<string, mixed> $indexEntry The indexed data entry to score
     * @return float The calculated relevance score between 0.0 and 1.0
     */
    public function calculate(SearchContextInterface $context, array $indexEntry): float;

    /**
     * Get the priority of this strategy for ordering
     *
     * Strategies with higher priority values are evaluated first.
     * This allows certain scoring factors to take precedence over others.
     * 
     * Typical priority values:
     * - 100: Exact match (highest priority)
     * - 90: Word match
     * - 80: Multi-word query
     * - 70: Fuzzy match
     * - 50: Default/fallback strategies
     *
     * @return int The priority value (higher = evaluated earlier)
     */
    public function getPriority(): int;
}
