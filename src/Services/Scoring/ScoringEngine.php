<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\Contracts\ScoringEngineInterface;
use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\ScoringStrategyInterface;
use Fuzzy\Services\Scoring\ScoringStrategyInterface as ScoringScoringStrategyInterface;

/**
 * Unified scoring engine that orchestrates all scoring strategies
 *
 * Calculates optimal relevance scores for search matches using multiple strategies.
 * Supports both single-word and multi-word queries with configurable weighting.
 */
class ScoringEngine implements ScoringEngineInterface
{
    /**
     * Default field weights when config is missing
     */
    private const DEFAULT_FIELD_WEIGHTS = [
        'name' => 1.3,
        'title' => 1.2,
        'email' => 1.0,
        'description' => 0.8,
        'content' => 0.7,
        'default' => 0.6,
    ];

    /**
     * Coverage bonus constants
     */
    private const FUZZY_COVERAGE_FULL_BONUS = 0.3;
    private const FUZZY_COVERAGE_HIGH_BONUS = 0.15;
    private const FUZZY_COVERAGE_HIGH_THRESHOLD = 0.75;
    private const FUZZY_COVERAGE_FULL_THRESHOLD = 0.75;

    /**
     * Scoring strategies sorted by priority
     *
     * @var array<ScoringStrategyInterface>
     */
    private array $strategies;

    /**
     * Initialize scoring engine with available strategies
     *
     * @param ScoringStrategyInterface ...$strategies Scoring strategies to use
     */
    public function __construct(ScoringScoringStrategyInterface ...$strategies)
    {
        $this->strategies = $strategies;

        // Sort strategies by descending priority
        usort($this->strategies, fn($a, $b): int => $b->getPriority() <=> $a->getPriority());
    }

    /**
     * {@inheritDoc}
     */
    public function calculateScore(SearchContextInterface $context, array $indexEntry): float
    {
        $bestScore = FUZZY_SCORE_NONE;

        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context, $indexEntry)) {
                $score = $strategy->calculate($context, $indexEntry);
                $bestScore = max($bestScore, $score);

                // Stop early if perfect score achieved
                if ($bestScore >= FUZZY_SCORE_IDENTICAL) {
                    break;
                }
            }
        }

        // Use fallback if no strategy supports the entry
        if ($bestScore === FUZZY_SCORE_NONE) {
            $bestScore = $this->calculateFallbackScore($context, $indexEntry);
        }

        return min(max($bestScore, FUZZY_SCORE_NONE), FUZZY_SCORE_IDENTICAL);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContextInterface $context): float
    {
        if ($indexEntries === [] || !$context->hasMultipleWords()) {
            return FUZZY_SCORE_NONE;
        }

        $queryWords = $context->getQueryWords();
        $wordScores = [];

        foreach ($queryWords as $queryWord) {
            $bestWordScore = FUZZY_SCORE_NONE;

            foreach ($indexEntries as $indexEntry) {
                $targetWords = $indexEntry['normalized_words'] ?? [];

                foreach ($targetWords as $targetWord) {
                    $similarity = $context->similarityCalculator->calculateWordSimilarity(
                        $queryWord,
                        (string) $targetWord
                    );

                    if ($similarity >= $context->options->threshold) {
                        $bestWordScore = max($bestWordScore, $similarity);
                    }
                }
            }

            if ($bestWordScore > FUZZY_SCORE_NONE) {
                $wordScores[] = $bestWordScore;
            }
        }

        if (empty($wordScores)) {
            return FUZZY_SCORE_NONE;
        }

        // Calculate average score with coverage bonus
        $averageScore = array_sum($wordScores) / count($wordScores);
        $coverage = count($wordScores) / count($queryWords);
        $coverageBonus = $this->calculateCoverageBonus($coverage);

        $finalScore = $averageScore * (FUZZY_BASE_FACTOR + $coverage) + $coverageBonus;

        // Apply field weighting
        $firstEntry = reset($indexEntries);
        $finalScore = $this->applyFieldWeighting($finalScore, $firstEntry);

        return min(max($finalScore, FUZZY_SCORE_NONE), FUZZY_SCORE_IDENTICAL);
    }

    /**
     * Fallback score calculation based on basic similarity
     *
     * Used when no scoring strategy supports the index entry.
     *
     * @param SearchContextInterface $context Search context with query and options
     * @param array<string, mixed> $indexEntry Index entry data containing:
     *                                         - original_value: The original text value
     * @return float Basic similarity score
     */
    private function calculateFallbackScore(SearchContextInterface $context, array $indexEntry): float
    {
        $query = $context->getNormalizedQuery();
        $value = $indexEntry['original_value'] ?? '';

        if (empty($value)) {
            return FUZZY_SCORE_NONE;
        }

        return $context->similarityCalculator->calculateWordSimilarity($query, $value);
    }

    /**
     * Calculate coverage bonus for multi-word queries
     *
     * Provides bonus points based on how many query words are matched.
     *
     * @param float $coverage Coverage ratio (matched words / total query words)
     * @return float Bonus to add to the score
     */
    private function calculateCoverageBonus(float $coverage): float
    {
        $fullCoverageThreshold = self::FUZZY_COVERAGE_FULL_THRESHOLD;
        $highCoverageThreshold = self::FUZZY_COVERAGE_HIGH_THRESHOLD;

        $bonuses = config('fuzzy.scoring.bonuses', []);
        $fullCoverageBonus = $bonuses['full_coverage'] ?? self::FUZZY_COVERAGE_FULL_BONUS;
        $highCoverageBonus = $bonuses['high_coverage'] ?? self::FUZZY_COVERAGE_HIGH_BONUS;

        if ($coverage >= $fullCoverageThreshold) {
            return $fullCoverageBonus;
        }

        if ($coverage >= $highCoverageThreshold) {
            return $highCoverageBonus;
        }

        return FUZZY_SCORE_NONE;
    }

    /**
     * Apply field-specific weighting to score
     *
     * Adjusts score based on the importance of the matched field.
     *
     * @param float $score Original calculated score
     * @param array<string, mixed> $match Match data including field information
     * @return float Weighted score
     */
    private function applyFieldWeighting(float $score, array $match): float
    {
        $fieldWeights = config('fuzzy.scoring.field_weights', self::DEFAULT_FIELD_WEIGHTS);

        $fieldWeight = $fieldWeights[$match['field']] ?? $fieldWeights['default'];
        return $score * $fieldWeight;
    }
}
