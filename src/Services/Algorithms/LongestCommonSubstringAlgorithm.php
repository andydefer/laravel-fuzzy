<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Implements the Longest Common Substring algorithm for similarity calculation.
 *
 * This algorithm finds the longest substring that appears in both input strings
 * and calculates similarity as the ratio of this length to the minimum string length.
 * It is particularly effective for detecting partial matches and overlapping content.
 */
class LongestCommonSubstringAlgorithm implements SimilarityAlgorithmInterface
{
    /**
     * Calculate similarity between two strings using Longest Common Substring.
     *
     * The similarity score is the length of the longest common substring
     * divided by the length of the shorter string, resulting in a value
     * between 0.0 (no common substring) and 1.0 (identical strings).
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return float Similarity score between 0.0 and 1.0
     */
    public function calculate(string $firstString, string $secondString): float
    {
        $firstLength = strlen($firstString);
        $secondLength = strlen($secondString);

        if ($firstLength === 0 || $secondLength === 0) {
            return 0.0;
        }

        $maxCommonLength = 0;
        $dp = array_fill(0, $firstLength + 1, array_fill(0, $secondLength + 1, 0));

        for ($i = 1; $i <= $firstLength; ++$i) {
            for ($j = 1; $j <= $secondLength; ++$j) {
                if ($firstString[$i - 1] === $secondString[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                    $maxCommonLength = max($maxCommonLength, $dp[$i][$j]);
                }
            }
        }

        $minStringLength = min($firstLength, $secondLength);
        return $minStringLength > 0 ? $maxCommonLength / $minStringLength : 0.0;
    }

    /**
     * Get the algorithm identifier name.
     *
     * @return string Unique identifier for this algorithm
     */
    public function getName(): string
    {
        return 'longest_common_substring';
    }

    /**
     * Get the default weight of this algorithm in composite scoring.
     *
     * This weight is used when combining multiple similarity algorithms
     * to calculate an overall similarity score.
     *
     * @return float Weight between 0.0 and 1.0
     */
    public function getWeight(): float
    {
        return 0.4;
    }
}
