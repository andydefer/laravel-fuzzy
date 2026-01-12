<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Prefix-based similarity algorithm.
 *
 * Calculates similarity based on the length of the common prefix between two strings.
 * This algorithm is particularly effective for matching strings that share beginnings,
 * such as abbreviations, acronyms, or similar naming patterns.
 */
class PrefixSimilarityAlgorithm implements SimilarityAlgorithmInterface
{
    /**
     * Minimum prefix length required to consider a match.
     *
     * Prefixes shorter than this are considered insignificant for similarity calculation.
     */
    private const MIN_PREFIX_LENGTH = 3;

    /**
     * Calculate similarity between two strings based on their common prefix.
     *
     * The algorithm evaluates the length of the matching prefix relative to the
     * maximum string length, with a cap to prevent disproportionately high scores
     * for very short strings with coincidental matches.
     *
     * @param string $str1 First string to compare
     * @param string $str2 Second string to compare
     * @return float Similarity score between 0.0 and 0.6
     */
    public function calculate(string $str1, string $str2): float
    {
        $minLength = min(strlen($str1), strlen($str2));

        if ($minLength < self::MIN_PREFIX_LENGTH) {
            return 0.0;
        }

        $prefixLength = $this->calculatePrefixLength($str1, $str2, $minLength);

        if ($prefixLength < self::MIN_PREFIX_LENGTH) {
            return 0.0;
        }

        return $this->calculateSimilarityScore($prefixLength, strlen($str1), strlen($str2));
    }

    /**
     * Get the algorithm's identifier.
     *
     * @return string Algorithm identifier used in configuration and reporting
     */
    public function getName(): string
    {
        return 'prefix';
    }

    /**
     * Get the algorithm's default weight in composite similarity calculations.
     *
     * @return float Weight value between 0.0 and 1.0
     */
    public function getWeight(): float
    {
        return 0.2;
    }

    /**
     * Calculate the length of the common prefix between two strings.
     *
     * @param string $firstString First string to compare
     * @param string $secondString Second string to compare
     * @param int $maxComparisonLength Maximum length to compare
     * @return int Length of the common prefix
     */
    private function calculatePrefixLength(string $firstString, string $secondString, int $maxComparisonLength): int
    {
        $prefixLength = 0;

        for ($i = 0; $i < $maxComparisonLength; ++$i) {
            if ($firstString[$i] !== $secondString[$i]) {
                break;
            }

            ++$prefixLength;
        }

        return $prefixLength;
    }

    /**
     * Calculate the final similarity score based on prefix length.
     *
     * @param int $prefixLength Length of the common prefix
     * @param int $firstStringLength Length of the first string
     * @param int $secondStringLength Length of the second string
     * @return float Normalized similarity score
     */
    private function calculateSimilarityScore(int $prefixLength, int $firstStringLength, int $secondStringLength): float
    {
        $maxLength = max($firstStringLength, $secondStringLength);
        $prefixRatio = $prefixLength / $maxLength;

        $baseScore = 0.4;
        $variableScore = $prefixRatio * 0.3;
        $cappedScore = min(0.6, $baseScore + $variableScore);

        return $cappedScore;
    }
}
