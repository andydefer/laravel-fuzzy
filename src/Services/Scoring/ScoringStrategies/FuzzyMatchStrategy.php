<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategy;

/**
 * Scoring strategy for fuzzy matching
 *
 * Handles scoring for approximate matches where query doesn't exactly match
 * the indexed content but is sufficiently similar.
 */
class FuzzyMatchStrategy implements ScoringStrategy
{
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    /**
     * Determine if this strategy should handle the scoring
     *
     * @param SearchContext $context The search context containing query and options
     * @param array $indexEntry The index entry being evaluated
     * @return bool True if fuzzy matching is enabled and no exact/word match applies
     */
    public function supports(SearchContext $context, array $indexEntry): bool
    {
        return $context->options->fuzzy && !$this->isExactOrWordMatch($context, $indexEntry);
    }

    /**
     * Calculate score for fuzzy match
     *
     * @param SearchContext $context The search context
     * @param array $indexEntry The index entry with original value and weight
     * @return float Calculated score (0.0 if below threshold)
     */
    public function calculate(SearchContext $context, array $indexEntry): float
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $originalValue = $indexEntry['original_value'] ?? '';

        $similarity = $context->similarityCalculator->calculateWordSimilarity(
            $normalizedQuery,
            $originalValue
        );

        if ($similarity < $context->options->threshold) {
            return 0.0;
        }

        $baseScore = $similarity * ($indexEntry['weight'] ?? 1.0);

        return $this->advancedCalculator->calculateFinalScore(
            $baseScore,
            $indexEntry,
            $context
        );
    }

    /**
     * Get strategy execution priority
     *
     * Lower priority strategies execute first. Fuzzy matching has lower
     * priority than exact/word matches.
     *
     * @return int Priority value
     */
    public function getPriority(): int
    {
        return 70;
    }

    /**
     * Check if exact or word match strategies would apply
     *
     * @param SearchContext $context The search context
     * @param array $indexEntry The index entry to check
     * @return bool True if exact or word match would handle this entry
     */
    private function isExactOrWordMatch(SearchContext $context, array $indexEntry): bool
    {
        $exactStrategy = new ExactMatchStrategy($this->advancedCalculator);
        $wordStrategy = new WordMatchStrategy($this->advancedCalculator);

        if ($exactStrategy->supports($context, $indexEntry)) {
            return true;
        }

        return $wordStrategy->supports($context, $indexEntry);
    }
}
