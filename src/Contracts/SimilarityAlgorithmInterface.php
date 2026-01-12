<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface defining the contract for similarity calculation algorithms.
 *
 * All similarity algorithms must implement this interface to ensure
 * consistency in the fuzzy search scoring system.
 */
interface SimilarityAlgorithmInterface
{
    /**
     * Calculate the similarity score between two strings.
     *
     * Returns a float between 0.0 (completely different) and 1.0 (identical).
     *
     * @param string $str1 First string to compare
     * @param string $str2 Second string to compare
     * @return float Similarity score between 0.0 and 1.0
     */
    public function calculate(string $str1, string $str2): float;

    /**
     * Get the unique name identifier for this algorithm.
     *
     * Used for logging, debugging, and configuration references.
     *
     * @return string Algorithm name identifier
     */
    public function getName(): string;

    /**
     * Get the weight coefficient used in composite similarity calculations.
     *
     * The weight determines how much this algorithm's score contributes
     * to the overall similarity calculation when multiple algorithms are combined.
     *
     * @return float Weight coefficient between 0.0 and 1.0
     */
    public function getWeight(): float;
}
