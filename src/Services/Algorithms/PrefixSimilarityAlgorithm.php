<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use Fuzzy\Config\PrefixAlgorithmConfig;

/**
 * Prefix-based similarity algorithm for string comparison.
 *
 * Calculates similarity between two strings based on their common prefix length.
 * This algorithm is particularly effective for:
 * - Autocomplete and type-ahead suggestions
 * - Search-as-you-type functionality
 * - Matching strings that share common beginnings
 *
 * The algorithm gives higher scores to strings with longer matching prefixes,
 * with diminishing returns beyond the base score threshold.
 *
 * @package Fuzzy\Services\Algorithms
 */
class PrefixSimilarityAlgorithm implements SimilarityAlgorithmInterface
{
    private PrefixAlgorithmConfig $config;

    /**
     * Constructor for PrefixSimilarityAlgorithm.
     *
     * @param PrefixAlgorithmConfig|null $config Configuration for algorithm parameters
     */
    public function __construct(?PrefixAlgorithmConfig $config = null)
    {
        $this->config = $config ?? PrefixAlgorithmConfig::fromConfig();
    }

    /**
     * Calculate similarity between two strings based on common prefix.
     *
     * Calculation process:
     * 1. Verify both strings meet minimum length requirements
     * 2. Count the number of matching characters from the start
     * 3. Calculate similarity score based on prefix ratio
     * 4. Apply configured base score and variable multiplier
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return float Similarity score between 0.0 (no similarity) and 1.0 (identical)
     */
    public function calculate(string $firstString, string $secondString): float
    {
        $shorterLength = min(strlen($firstString), strlen($secondString));
        $minimumRequiredPrefixLength = $this->config->getMinPrefixLength();

        // Strings are too short for meaningful prefix matching
        if ($shorterLength < $minimumRequiredPrefixLength) {
            return FUZZY_SCORE_NONE;
        }

        $matchingPrefixLength = $this->countMatchingPrefixCharacters($firstString, $secondString, $shorterLength);

        // Not enough matching prefix characters to be considered a match
        if ($matchingPrefixLength < $minimumRequiredPrefixLength) {
            return FUZZY_SCORE_NONE;
        }

        return $this->calculateNormalizedScore($matchingPrefixLength, strlen($firstString), strlen($secondString));
    }

    /**
     * Get the algorithm identifier name.
     *
     * @return string Algorithm name for configuration and debugging
     */
    public function getName(): string
    {
        return 'prefix';
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
     * Count the number of matching characters from the start of both strings.
     *
     * Iterates character by character until a mismatch is found or
     * the maximum comparison length is reached.
     *
     * @param string $firstString First string to compare
     * @param string $secondString Second string to compare
     * @param int $maxComparisonLength Maximum number of characters to compare
     * @return int Number of consecutive matching prefix characters
     */
    private function countMatchingPrefixCharacters(string $firstString, string $secondString, int $maxComparisonLength): int
    {
        $matchingCount = 0;

        for ($position = 0; $position < $maxComparisonLength; ++$position) {
            if ($firstString[$position] !== $secondString[$position]) {
                break;
            }

            ++$matchingCount;
        }

        return $matchingCount;
    }

    /**
     * Calculate the normalized similarity score based on prefix length.
     *
     * Formula: score = min(maxScore, baseScore + (prefixRatio × variableMultiplier))
     * Where prefixRatio = matchingPrefixLength / max(stringLengths)
     *
     * The result is capped at maxScore to prevent over-scoring.
     *
     * @param int $matchingPrefixLength Number of matching prefix characters
     * @param int $firstStringLength Length of the first string
     * @param int $secondStringLength Length of the second string
     * @return float Normalized similarity score between 0.0 and maxScore
     */
    private function calculateNormalizedScore(int $matchingPrefixLength, int $firstStringLength, int $secondStringLength): float
    {
        $longestStringLength = max($firstStringLength, $secondStringLength);
        $prefixRatio = $matchingPrefixLength / $longestStringLength;

        $baseScore = $this->config->getPrefixBaseScore();
        $variableMultiplier = $this->config->getPrefixVariableMultiplier();
        $maxScore = $this->config->getPrefixMaxScore();

        $variableComponent = $prefixRatio * $variableMultiplier;
        $rawScore = $baseScore + $variableComponent;

        return min($maxScore, $rawScore);
    }
}
