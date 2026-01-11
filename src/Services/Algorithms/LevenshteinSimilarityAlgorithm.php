<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Optimized Levenshtein similarity algorithm.
 */
class LevenshteinSimilarityAlgorithm implements SimilarityAlgorithmInterface
{
    public function calculate(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 && $len2 === 0) {
            return 1.0;
        }

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $maxLen = max($len1, $len2);
        $distance = levenshtein($str1, $str2);

        // Normalization with penalty for large distances
        $similarity = 1 - ($distance / $maxLen);

        if ($distance > 2) {
            $penaltyFactor = min(0.7, 1.0 - ($distance * 0.1));
            $similarity *= $penaltyFactor;
        }

        if ($distance <= 2 && $maxLen >= 4) {
            $similarity = min($similarity + 0.1, 1.0);
        }

        return max($similarity, 0.0);
    }

    public function getName(): string
    {
        return 'levenshtein';
    }

    public function getWeight(): float
    {
        return 0.3;
    }
}
