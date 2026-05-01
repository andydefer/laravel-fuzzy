<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategyInterface;

/**
 * Scoring strategy for fuzzy matching
 *
 * Handles scoring for approximate matches where query doesn't exactly match
 * the indexed content but is sufficiently similar.
 * 
 * This strategy is applied when:
 * - Fuzzy matching is enabled in search options
 * - Neither exact match nor word match strategies apply
 */
class FuzzyMatchStrategy implements ScoringStrategyInterface
{
    /**
     * Priority for fuzzy matching strategy (lower = higher priority)
     * Fuzzy matching has lower priority than exact/word matches
     */
    private const PRIORITY = 70;

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
     * Determines if this strategy should handle the scoring.
     * Returns true when:
     * - Fuzzy matching is enabled in search options
     * - Neither exact nor word match strategies would apply
     */
    public function supports(SearchContextInterface $context, array $indexEntry): bool
    {
        return $context->options->fuzzy && !$this->isExactOrWordMatch($context, $indexEntry);
    }

    /**
     * {@inheritDoc}
     *
     * Calculates score for fuzzy match based on similarity threshold.
     * Returns FUZZY_SCORE_NONE if similarity is below configured threshold.
     */
    public function calculate(SearchContextInterface $context, array $indexEntry): float
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $originalValue = $indexEntry['original_value'] ?? '';

        $similarity = $context->similarityCalculator->calculateWordSimilarity(
            $normalizedQuery,
            $originalValue
        );

        if ($similarity < $context->options->threshold) {
            return FUZZY_SCORE_NONE;
        }

        $baseScore = $similarity * ($indexEntry['weight'] ?? FUZZY_BASE_FACTOR);

        return $this->advancedCalculator->calculateFinalScore(
            baseScore: $baseScore,
            match: $indexEntry,
            context: $context
        );
    }

    /**
     * {@inheritDoc}
     *
     * Returns the strategy execution priority.
     * Fuzzy matching has lower priority than exact/word matches.
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * Check if exact or word match strategies would apply.
     *
     * This prevents fuzzy matching from scoring entries that would
     * already be handled by higher-priority strategies.
     *
     * @param SearchContextInterface $context The search context
     * @param array<string, mixed> $indexEntry The index entry to check
     * @return bool True if exact or word match would handle this entry
     */
    private function isExactOrWordMatch(SearchContextInterface $context, array $indexEntry): bool
    {
        $exactStrategy = new ExactMatchStrategy($this->advancedCalculator);
        $wordStrategy = new WordMatchStrategy($this->advancedCalculator);

        if ($exactStrategy->supports($context, $indexEntry)) {
            return true;
        }

        return $wordStrategy->supports($context, $indexEntry);
    }
}
