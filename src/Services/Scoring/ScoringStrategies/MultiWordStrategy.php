<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Services\Scoring\ScoringStrategyInterface;

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
class MultiWordStrategy implements ScoringStrategyInterface
{
    /**
     * Priority for multi-word strategy (medium-high priority)
     */
    private const PRIORITY = 80;

    /**
     * {@inheritDoc}
     *
     * Determines if this strategy supports the given search context.
     * This strategy supports only multi-word search queries where the user
     * has entered multiple search terms separated by spaces.
     */
    public function supports(SearchContextInterface $context, array $indexEntry): bool
    {
        return $context->hasMultipleWords();
    }

    /**
     * {@inheritDoc}
     *
     * Calculate the score for a multi-word query.
     * 
     * Note: This strategy doesn't perform actual calculation at the individual
     * entry level. Multi-word scoring requires access to multiple index entries
     * and complex cross-word matching, which is handled centrally by the
     * ScoringEngine.
     * 
     * Returns FUZZY_SCORE_NONE to indicate that the actual scoring should be
     * performed by the ScoringEngine::calculateMultiWordScore method.
     */
    public function calculate(SearchContextInterface $context, array $indexEntry): float
    {
        return FUZZY_SCORE_NONE;
    }

    /**
     * {@inheritDoc}
     *
     * Returns the priority of this scoring strategy.
     * 
     * Higher priority strategies are evaluated first. Multi-word strategy
     * has high priority because multi-word queries require specialized
     * handling that affects how other strategies are applied.
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
