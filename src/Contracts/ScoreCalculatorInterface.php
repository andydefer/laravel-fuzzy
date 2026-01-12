<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Fuzzy\SearchContext;

/**
 * Defines the contract for score calculation strategies in fuzzy search.
 *
 * Implementations of this interface provide different algorithms and strategies
 * for calculating relevance scores between search queries and indexed content.
 * The score calculation system uses a priority-based chain of responsibility
 * pattern to apply multiple scoring strategies.
 */
interface ScoreCalculatorInterface
{
    /**
     * Calculate the relevance score for a search result.
     *
     * @param SearchContext $context The search context containing configuration and state
     * @param array $indexEntry The indexed entry being evaluated
     * @param array $queryWords Normalized query words for comparison
     * @return float Calculated relevance score between 0.0 and 1.0
     */
    public function calculate(SearchContext $context, array $indexEntry, array $queryWords): float;

    /**
     * Get the execution priority of this calculator.
     *
     * Higher priority calculators are executed first in the calculation chain.
     * This allows strategic ordering where certain calculations should influence
     * or precede others in the overall scoring process.
     *
     * @return int Priority value (higher = executed earlier)
     */
    public function getPriority(): int;

    /**
     * Determine if this calculator supports the given search context.
     *
     * Allows calculators to declare compatibility with specific search scenarios,
     * such as certain model types, field configurations, or search options.
     *
     * @param SearchContext $context The search context to evaluate
     * @return bool True if this calculator should be used for the given context
     */
    public function supports(SearchContext $context): bool;
}
