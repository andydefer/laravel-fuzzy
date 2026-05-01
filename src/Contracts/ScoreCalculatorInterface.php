<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Defines the contract for score calculation strategies in fuzzy search.
 *
 * Implementations of this interface provide different algorithms and strategies
 * for calculating relevance scores between search queries and indexed content.
 *
 * The score calculation system uses a priority-based chain of responsibility
 * pattern to apply multiple scoring strategies in a specific order.
 *
 * @package Fuzzy\Contracts
 */
interface ScoreCalculatorInterface
{
    /**
     * Calculate the relevance score for a search result.
     *
     * Evaluates the similarity between a search query and an indexed entry
     * using the implementation's specific algorithm. Returns a normalized
     * score where higher values indicate stronger matches.
     *
     * @param SearchContextInterface $context The search context containing configuration and state
     * @param array<string, mixed> $indexEntry The indexed entry being evaluated containing:
     *                                         - field: The name of the indexed field
     *                                         - original_value: The original text value
     *                                         - normalized_words: Array of normalized word tokens
     *                                         - weight: Field weight multiplier for scoring
     * @param array<int, string> $queryWords Normalized query word tokens for comparison
     * @return float Calculated relevance score between 0.0 (no match) and 1.0 (perfect match)
     */
    public function calculate(SearchContextInterface $context, array $indexEntry, array $queryWords): float;

    /**
     * Get the execution priority of this calculator.
     *
     * Higher priority calculators are executed first in the calculation chain.
     * This allows strategic ordering where certain calculations should influence
     * or precede others in the overall scoring process.
     *
     * Standard priority values:
     * - 100: Exact match calculators (highest priority)
     * - 90:  Word match calculators
     * - 80:  Multi-word query calculators
     * - 70:  Fuzzy match calculators
     * - 50:  Default and fallback calculators (lowest priority)
     *
     * @return int Priority value where higher numbers execute earlier
     */
    public function getPriority(): int;

    /**
     * Determine if this calculator supports the given search context.
     *
     * Allows calculators to declare compatibility with specific search scenarios,
     * such as particular model types, field configurations, or search options.
     * This enables dynamic strategy selection based on the current search context.
     *
     * @param SearchContextInterface $context The search context to evaluate for compatibility
     * @return bool True if this calculator should be used for the given context
     */
    public function supports(SearchContextInterface $context): bool;
}
