<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use Fuzzy\Config\LongestCommonSubstringConfig;

/**
 * Longest Common Substring (LCS) similarity algorithm.
 *
 * Calculates similarity between two strings based on the length of their
 * longest common contiguous substring. Uses dynamic programming for efficiency.
 *
 * The algorithm returns the ratio of the longest common substring length
 * to the length of the shorter input string.
 *
 * @package Fuzzy\Services\Algorithms
 */
class LongestCommonSubstringAlgorithm implements SimilarityAlgorithmInterface
{
    private LongestCommonSubstringConfig $config;

    /**
     * Constructor for LongestCommonSubstringAlgorithm.
     *
     * @param LongestCommonSubstringConfig|null $config Configuration for algorithm parameters
     */
    public function __construct(?LongestCommonSubstringConfig $config = null)
    {
        $this->config = $config ?? LongestCommonSubstringConfig::createDefault();
    }

    /**
     * Calculate similarity between two strings using longest common substring.
     *
     * Uses dynamic programming to find the longest contiguous substring
     * common to both input strings, then returns the ratio relative to
     * the shorter string's length.
     *
     * Algorithm steps:
     * 1. Handle edge cases (empty strings)
     * 2. Initialize DP table with zeros
     * 3. Fill DP table while tracking maximum common substring length
     * 4. Return similarity = maxCommonLength / min(string lengths)
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return float Similarity score between 0.0 (no similarity) and 1.0 (identical)
     */
    public function calculate(string $firstString, string $secondString): float
    {
        $baseIndex = $this->config->getBaseIndex();
        $matchIncrement = $this->config->getMatchIncrement();

        $firstLength = strlen($firstString);
        $secondLength = strlen($secondString);

        // Empty strings have no common substring
        if ($firstLength === $baseIndex || $secondLength === $baseIndex) {
            return FUZZY_SCORE_NONE;
        }

        $maxCommonLength = $baseIndex;
        $dpTable = $this->initializeDpTable($firstLength, $secondLength);

        // Fill DP table to find the longest common substring
        for ($row = $matchIncrement; $row <= $firstLength; ++$row) {
            for ($column = $matchIncrement; $column <= $secondLength; ++$column) {
                $firstCharIndex = $row - $matchIncrement;
                $secondCharIndex = $column - $matchIncrement;

                if ($firstString[$firstCharIndex] === $secondString[$secondCharIndex]) {
                    $dpTable[$row][$column] = $dpTable[$row - $matchIncrement][$column - $matchIncrement] + $matchIncrement;
                    $maxCommonLength = max($maxCommonLength, $dpTable[$row][$column]);
                }
            }
        }

        $minStringLength = min($firstLength, $secondLength);

        return $minStringLength > $baseIndex
            ? $maxCommonLength / $minStringLength
            : FUZZY_SCORE_NONE;
    }

    /**
     * Get the algorithm identifier name.
     *
     * @return string Algorithm name for configuration and debugging
     */
    public function getName(): string
    {
        return 'longest_common_substring';
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
     * Initialize the dynamic programming table for LCS calculation.
     *
     * Creates a (firstLength + 1) x (secondLength + 1) matrix filled with zeros.
     *
     * @param int $firstLength Length of the first string
     * @param int $secondLength Length of the second string
     * @return array<int, array<int, int>> DP table initialized with zeros
     */
    private function initializeDpTable(int $firstLength, int $secondLength): array
    {
        $baseIndex = $this->config->getBaseIndex();
        $matchIncrement = $this->config->getMatchIncrement();

        return array_fill(
            $baseIndex,
            $firstLength + $matchIncrement,
            array_fill($baseIndex, $secondLength + $matchIncrement, $baseIndex)
        );
    }
}
