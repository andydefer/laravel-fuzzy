<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Optimized Longest Common Substring algorithm.
 */
class LongestCommonSubstringAlgorithm implements SimilarityAlgorithmInterface
{
    public function calculate(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $maxLength = 0;
        $dp = array_fill(0, $len1 + 1, array_fill(0, $len2 + 1, 0));

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                if ($str1[$i - 1] === $str2[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                    $maxLength = max($maxLength, $dp[$i][$j]);
                }
            }
        }

        $maxPossible = min($len1, $len2);
        return $maxPossible > 0 ? $maxLength / $maxPossible : 0.0;
    }

    public function getName(): string
    {
        return 'longest_common_substring';
    }

    public function getWeight(): float
    {
        return 0.4;
    }
}
