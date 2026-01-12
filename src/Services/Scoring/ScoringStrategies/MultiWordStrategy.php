<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\SearchContext;
use Fuzzy\Services\Scoring\ScoringStrategy;

/**
 * Scoring strategy for multi-word search queries
 *
 * This strategy is specifically designed to handle search queries containing
 * multiple words. It acts as a marker strategy that signals to the scoring
 * engine that multi-word processing is required.
 *
 * Multi-word scoring involves complex cross-word matching and requires
 * access to multiple index entries, which is handled by the ScoringEngine.
 *
 * @package Fuzzy\Services\Scoring\ScoringStrategies
 */
class MultiWordStrategy implements ScoringStrategy
{
    /**
     * Determine if this strategy supports the given search context
     *
     * This strategy supports only multi-word search queries where the user
     * has entered multiple search terms separated by spaces.
     *
     * @param SearchContext $context The search context containing query information
     * @param array<string, mixed> $indexEntry The current index entry being evaluated
     * @return bool True if the query contains multiple words, false otherwise
     */
    public function supports(SearchContext $context, array $indexEntry): bool
    {
        return $context->hasMultipleWords();
    }

    /**
     * Calculate the score for a multi-word query
     *
     * Note: This strategy doesn't perform actual calculation at the individual
     * entry level. Multi-word scoring requires access to multiple index entries
     * and complex cross-word matching, which is handled centrally by the
     * ScoringEngine.
     *
     * @param SearchContext $context The search context
     * @param array<string, mixed> $indexEntry The current index entry
     * @return float Always returns 0.0 - actual scoring is handled by ScoringEngine
     */
    public function calculate(SearchContext $context, array $indexEntry): float
    {
        return 0.0;
    }

    /**
     * Get the priority of this scoring strategy
     *
     * Higher priority strategies are evaluated first. Multi-word strategy
     * has high priority because multi-word queries require specialized
     * handling that affects how other strategies are applied.
     *
     * @return int The priority value (higher = evaluated earlier)
     */
    public function getPriority(): int
    {
        return 80;
    }
}
