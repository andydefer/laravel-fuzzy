<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for similarity calculation algorithms.
 */
interface SimilarityAlgorithmInterface
{
    /**
     * Calculate similarity between two strings.
     */
    public function calculate(string $str1, string $str2): float;

    /**
     * Get algorithm name.
     */
    public function getName(): string;

    /**
     * Get algorithm weight in composite calculation.
     */
    public function getWeight(): float;
}
