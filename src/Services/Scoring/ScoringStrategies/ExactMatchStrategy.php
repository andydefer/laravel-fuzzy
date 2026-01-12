<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategy;

/**
 * Scoring strategy for exact matches
 *
 * Provides the highest priority scoring when the search query
 * exactly matches the indexed value.
 */
class ExactMatchStrategy implements ScoringStrategy
{
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    /**
     * Determines if this strategy applies to the current search context
     *
     * @param SearchContext $context The current search context
     * @param array<string, mixed> $indexEntry The index entry being evaluated
     * @return bool True if the normalized query exactly matches the original value
     */
    public function supports(SearchContext $context, array $indexEntry): bool
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $originalValue = $indexEntry['original_value'] ?? '';

        return $normalizedQuery === $originalValue;
    }

    /**
     * Calculates the score for an exact match
     *
     * @param SearchContext $context The current search context
     * @param array<string, mixed> $indexEntry The index entry being scored
     * @return float The calculated score, enhanced by advanced calculations
     */
    public function calculate(SearchContext $context, array $indexEntry): float
    {
        $baseScore = 1.0 * ($indexEntry['weight'] ?? 1.0);

        return $this->advancedCalculator->calculateFinalScore(
            baseScore: $baseScore,
            match: $indexEntry,
            context: $context,
            queryWord: $context->getNormalizedQuery()
        );
    }

    /**
     * Returns the priority of this strategy
     *
     * Exact matches have the highest priority in the scoring system.
     *
     * @return int Priority value (higher = more important)
     */
    public function getPriority(): int
    {
        return 100;
    }
}
