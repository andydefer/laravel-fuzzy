<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategyInterface;

/**
 * Scoring strategy for exact matches
 *
 * Provides the highest priority scoring when the search query
 * exactly matches the indexed value.
 * 
 * This strategy is applied when the normalized query string
 * is identical to the original indexed value.
 */
class ExactMatchStrategy implements ScoringStrategyInterface
{
    /**
     * Priority for exact match strategy (highest priority)
     * 
     * Exact matches should always be evaluated first as they
     * represent the most relevant results.
     */
    private const PRIORITY = 100;

    /**
     * Constructor.
     *
     * @param AdvancedScoringCalculator $advancedCalculator Service for advanced scoring calculations
     */
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    /**
     * {@inheritDoc}
     *
     * Determines if this strategy applies to the current search context.
     * Returns true when the normalized query exactly matches the original
     * value from the index entry.
     */
    public function supports(SearchContextInterface $context, array $indexEntry): bool
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $originalValue = $indexEntry['original_value'] ?? '';

        return $normalizedQuery === $originalValue;
    }

    /**
     * {@inheritDoc}
     *
     * Calculates the score for an exact match.
     * Uses the base score of 1.0 multiplied by field weight,
     * then applies additional advanced scoring calculations.
     */
    public function calculate(SearchContextInterface $context, array $indexEntry): float
    {
        $baseScore = FUZZY_SCORE_IDENTICAL * ($indexEntry['weight'] ?? FUZZY_BASE_FACTOR);

        return $this->advancedCalculator->calculateFinalScore(
            baseScore: $baseScore,
            match: $indexEntry,
            context: $context,
            queryWord: $context->getNormalizedQuery()
        );
    }

    /**
     * {@inheritDoc}
     *
     * Returns the priority of this strategy.
     * Exact matches have the highest priority in the scoring system
     * to ensure they are considered first.
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
