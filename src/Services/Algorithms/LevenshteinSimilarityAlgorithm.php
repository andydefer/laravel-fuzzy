<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Optimized Levenshtein distance-based similarity algorithm.
 *
 * Calculates similarity between two strings using Levenshtein distance
 * with normalization and performance optimizations for fuzzy searching.
 *
 * The algorithm applies penalties for large distances and bonuses for
 * close matches, providing a balanced similarity score between 0.0 and 1.0.
 */
class LevenshteinSimilarityAlgorithm implements SimilarityAlgorithmInterface
{
    /**
     * Calculate similarity between two strings using Levenshtein distance.
     *
     * The algorithm:
     * 1. Normalizes Levenshtein distance to a 0-1 scale
     * 2. Applies penalties for distances greater than 2
     * 3. Adds bonuses for very close matches on longer strings
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return float Similarity score between 0.0 (no similarity) and 1.0 (identical)
     */
    public function calculate(string $firstString, string $secondString): float
    {
        $firstLength = strlen($firstString);
        $secondLength = strlen($secondString);

        if ($firstLength === 0 && $secondLength === 0) {
            return 1.0;
        }

        if ($firstLength === 0 || $secondLength === 0) {
            return 0.0;
        }

        $maxLength = max($firstLength, $secondLength);
        $distance = levenshtein($firstString, $secondString);
        $similarity = 1 - ($distance / $maxLength);

        $similarity = $this->applyDistancePenalty($distance, $similarity);
        $similarity = $this->applyCloseMatchBonus($distance, $maxLength, $similarity);

        return max($similarity, 0.0);
    }

    /**
     * Apply penalty for large Levenshtein distances.
     *
     * Distances greater than 2 receive progressively larger penalties
     * to better differentiate between close and distant matches.
     *
     * @param int $distance Levenshtein distance between strings
     * @param float $currentSimilarity Current similarity score before penalty
     * @return float Similarity score after applying penalty
     */
    private function applyDistancePenalty(int $distance, float $currentSimilarity): float
    {
        if ($distance > 2) {
            $penaltyFactor = min(0.7, 1.0 - ($distance * 0.1));
            $currentSimilarity *= $penaltyFactor;
        }

        return $currentSimilarity;
    }

    /**
     * Apply bonus for very close matches on longer strings.
     *
     * When strings have a small distance (≤2) and reasonable length (≥4),
     * they likely represent a meaningful match deserving a slight bonus.
     *
     * @param int $distance Levenshtein distance between strings
     * @param int $maxLength Length of the longer string
     * @param float $currentSimilarity Current similarity score before bonus
     * @return float Similarity score after applying bonus
     */
    private function applyCloseMatchBonus(int $distance, int $maxLength, float $currentSimilarity): float
    {
        if ($distance <= 2 && $maxLength >= 4) {
            $currentSimilarity = min($currentSimilarity + 0.1, 1.0);
        }

        return $currentSimilarity;
    }

    /**
     * Get the algorithm identifier name.
     *
     * @return string Algorithm name used for configuration and debugging
     */
    public function getName(): string
    {
        return 'levenshtein';
    }

    /**
     * Get the algorithm weight in composite similarity calculations.
     *
     * This weight is used when combining multiple algorithm scores.
     * It should reflect the algorithm's reliability for the domain.
     *
     * @return float Weight between 0.0 and 1.0
     */
    public function getWeight(): float
    {
        return 0.3;
    }
}
