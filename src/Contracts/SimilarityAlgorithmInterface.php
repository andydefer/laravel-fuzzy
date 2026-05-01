<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface defining the contract for similarity calculation algorithms.
 *
 * All similarity algorithms must implement this interface to ensure
 * consistency in the fuzzy search scoring system.
 *
 * Implementations include algorithms such as:
 * - Levenshtein distance
 * - Longest Common Substring (LCS)
 * - Prefix matching
 * - Phonetic similarity (Metaphone, Soundex)
 *
 * @package Fuzzy\Contracts
 */
interface SimilarityAlgorithmInterface
{
    /**
     * Calculate the similarity score between two strings.
     *
     * Returns a normalized float where higher values indicate greater similarity.
     *
     * @param string $firstString First string to compare
     * @param string $secondString Second string to compare
     * @return float Similarity score between 0.0 (completely different) and 1.0 (identical)
     */
    public function calculate(string $firstString, string $secondString): float;

    /**
     * Get the unique name identifier for this algorithm.
     *
     * Used for logging, debugging, configuration references, and
     * algorithm selection in composite strategies.
     *
     * @return string Algorithm name identifier (e.g., 'levenshtein', 'lcs', 'prefix')
     */
    public function getName(): string;

    /**
     * Get the weight coefficient used in composite similarity calculations.
     *
     * The weight determines how much this algorithm's score contributes
     * to the overall similarity calculation when multiple algorithms are combined.
     *
     * For example, in a weighted average of multiple algorithms:
     * Final Score = (weight1 * score1 + weight2 * score2 + ...) / sum(weights)
     *
     * @return float Weight coefficient between 0.0 and 1.0
     */
    public function getWeight(): float;
}
