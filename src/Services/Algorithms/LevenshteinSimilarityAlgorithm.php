<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use Fuzzy\Config\LevenshteinAlgorithmConfig;

/**
 * Optimized Levenshtein distance-based similarity algorithm.
 *
 * Calculates similarity between two strings using Levenshtein distance
 * with normalization and performance optimizations for fuzzy searching.
 *
 * The algorithm applies penalties for large distances and bonuses for
 * close matches, providing a balanced similarity score between 0.0 and 1.0.
 *
 * @package Fuzzy\Services\Algorithms
 */
class LevenshteinSimilarityAlgorithm implements SimilarityAlgorithmInterface
{
    private LevenshteinAlgorithmConfig $config;

    /**
     * Constructor for LevenshteinSimilarityAlgorithm.
     *
     * @param LevenshteinAlgorithmConfig|null $config Configuration for algorithm parameters
     */
    public function __construct(?LevenshteinAlgorithmConfig $config = null)
    {
        $this->config = $config ?? LevenshteinAlgorithmConfig::fromConfig();
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance.
     *
     * Calculation process:
     * 1. Handle edge cases (both empty strings, one empty string)
     * 2. Compute raw similarity: 1.0 - (distance / maxLength)
     * 3. Apply penalty for distances exceeding the threshold
     * 4. Apply bonus for very close matches on reasonably long strings
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return float Similarity score between 0.0 (no similarity) and 1.0 (identical)
     */
    public function calculate(string $firstString, string $secondString): float
    {
        $emptyStringLength = $this->config->getEmptyStringLength();

        $firstLength = strlen($firstString);
        $secondLength = strlen($secondString);

        // Both strings are empty -> perfect match
        if ($firstLength === $emptyStringLength && $secondLength === $emptyStringLength) {
            return FUZZY_SCORE_IDENTICAL;
        }

        // One string is empty -> no match
        if ($firstLength === $emptyStringLength || $secondLength === $emptyStringLength) {
            return FUZZY_SCORE_NONE;
        }

        $longestLength = max($firstLength, $secondLength);
        $levenshteinDistance = levenshtein($firstString, $secondString);
        $rawSimilarity = FUZZY_SCORE_IDENTICAL - ($levenshteinDistance / $longestLength);

        $similarity = $this->applyDistancePenalty($levenshteinDistance, $rawSimilarity);
        $similarity = $this->applyCloseMatchBonus($levenshteinDistance, $longestLength, $similarity);

        return max($similarity, FUZZY_SCORE_NONE);
    }

    /**
     * Get the algorithm identifier name.
     *
     * @return string Algorithm name for configuration and debugging
     */
    public function getName(): string
    {
        return 'levenshtein';
    }

    /**
     * Get the algorithm weight in composite similarity calculations.
     *
     * This weight determines how much this algorithm's score contributes
     * when combined with other similarity algorithms.
     *
     * @return float Weight between 0.0 and 1.0
     */
    public function getWeight(): float
    {
        return $this->config->getWeight();
    }

    /**
     * Apply penalty for large Levenshtein distances.
     *
     * Distances greater than the configured threshold receive progressively
     * larger penalties to better differentiate between close and distant matches.
     *
     * @param int $levenshteinDistance The computed Levenshtein distance
     * @param float $currentSimilarity Similarity score before penalty
     * @return float Similarity score after applying penalty
     */
    private function applyDistancePenalty(int $levenshteinDistance, float $currentSimilarity): float
    {
        $penaltyThreshold = $this->config->getDistancePenaltyThreshold();

        if ($levenshteinDistance > $penaltyThreshold) {
            $reductionPerDistance = $this->config->getPenaltyReductionPerDistance();
            $basePenaltyFactor = $this->config->getPenaltyFactorBase();

            $penaltyFactor = min($basePenaltyFactor, FUZZY_BASE_FACTOR - ($levenshteinDistance * $reductionPerDistance));
            $currentSimilarity *= $penaltyFactor;
        }

        return $currentSimilarity;
    }

    /**
     * Apply bonus for very close matches on longer strings.
     *
     * When strings have a small distance (below the threshold) and sufficient length,
     * they likely represent a meaningful match deserving a slight bonus.
     *
     * @param int $levenshteinDistance The computed Levenshtein distance
     * @param int $longestLength Length of the longer string
     * @param float $currentSimilarity Similarity score before bonus
     * @return float Similarity score after applying bonus
     */
    private function applyCloseMatchBonus(int $levenshteinDistance, int $longestLength, float $currentSimilarity): float
    {
        $closeMatchThreshold = $this->config->getCloseMatchBonusThreshold();
        $minimumLengthForBonus = $this->config->getMinLengthForBonus();
        $closeMatchBonus = $this->config->getCloseMatchBonus();

        if ($levenshteinDistance <= $closeMatchThreshold && $longestLength >= $minimumLengthForBonus) {
            $currentSimilarity = min($currentSimilarity + $closeMatchBonus, FUZZY_SCORE_IDENTICAL);
        }

        return $currentSimilarity;
    }
}
