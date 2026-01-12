<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategy;

/**
 * Scoring strategy for exact word matches
 *
 * Detects and scores exact matches between query words and indexed words.
 * This strategy has high priority as exact matches are typically most relevant.
 */
class WordMatchStrategy implements ScoringStrategy
{
    /**
     * @param AdvancedScoringCalculator $advancedCalculator Service for calculating advanced scoring metrics
     */
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    /**
     * Check if this strategy applies to the current search context and index entry
     *
     * Determines if any query word exactly matches any word in the target text.
     *
     * @param SearchContext $context The current search context containing query and configuration
     * @param array $indexEntry The index entry being evaluated
     * @return bool True if there's at least one exact word match
     */
    public function supports(SearchContext $context, array $indexEntry): bool
    {
        $queryWords = $context->getQueryWords();
        $targetWords = $indexEntry['normalized_words'] ?? [];

        foreach ($queryWords as $queryWord) {
            if (in_array($queryWord, $targetWords)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate the relevance score for exact word matches
     *
     * Scores are based on exact matches between query words and target words.
     * Returns the highest score found among all matching query words.
     *
     * @param SearchContext $context The current search context
     * @param array $indexEntry The index entry being scored
     * @return float The calculated relevance score between 0.0 and 1.0
     */
    public function calculate(SearchContext $context, array $indexEntry): float
    {
        $bestScore = 0.0;
        $queryWords = $context->getQueryWords();
        $targetWords = $indexEntry['normalized_words'] ?? [];

        foreach ($queryWords as $queryWord) {
            if (in_array($queryWord, $targetWords)) {
                $baseScore = 0.9 * ($indexEntry['weight'] ?? 1.0);
                $score = $this->advancedCalculator->calculateFinalScore(
                    baseScore: $baseScore,
                    match: $indexEntry,
                    context: $context,
                    queryWord: $queryWord
                );
                $bestScore = max($bestScore, $score);
            }
        }

        return $bestScore;
    }

    /**
     * Get the priority of this scoring strategy
     *
     * Higher priority strategies are evaluated first.
     * Exact word matching has high priority as it typically indicates strong relevance.
     *
     * @return int The priority value (higher = more important)
     */
    public function getPriority(): int
    {
        return 90;
    }
}
