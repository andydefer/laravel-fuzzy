<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategyInterface;

/**
 * Scoring strategy for exact word matches
 *
 * Detects and scores exact matches between query words and indexed words.
 * This strategy has high priority as exact matches are typically most relevant.
 */
class WordMatchStrategy implements ScoringStrategyInterface
{
    /**
     * Priority for word match strategy (very high priority)
     */
    private const PRIORITY = 90;

    /**
     * Base score for word matches (slightly less than exact match)
     */
    private const BASE_SCORE_WORD_MATCH = 0.9;

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
     * Determines if any query word exactly matches any word in the target text.
     */
    public function supports(SearchContextInterface $context, array $indexEntry): bool
    {
        $queryWords = $context->getQueryWords();
        $targetWords = $indexEntry['normalized_words'] ?? [];

        foreach ($queryWords as $queryWord) {
            if (in_array($queryWord, $targetWords, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * Scores are based on exact matches between query words and target words.
     * Returns the highest score found among all matching query words.
     */
    public function calculate(SearchContextInterface $context, array $indexEntry): float
    {
        $bestScore = FUZZY_SCORE_NONE;
        $queryWords = $context->getQueryWords();
        $targetWords = $indexEntry['normalized_words'] ?? [];

        foreach ($queryWords as $queryWord) {
            if (in_array($queryWord, $targetWords, true)) {
                $baseScore = self::BASE_SCORE_WORD_MATCH * ($indexEntry['weight'] ?? FUZZY_BASE_FACTOR);
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
     * {@inheritDoc}
     *
     * Returns the priority of this scoring strategy.
     * Word matching has high priority as it typically indicates strong relevance.
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
