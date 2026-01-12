<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Prefix-based similarity algorithm.
 */
class PrefixSimilarityAlgorithm implements SimilarityAlgorithmInterface
{
    private const MIN_PREFIX_LENGTH = 3;

    public function calculate(string $str1, string $str2): float
    {
        $minLen = min(strlen($str1), strlen($str2));

        if ($minLen < self::MIN_PREFIX_LENGTH) {
            return 0.0;
        }

        $prefixLength = 0;
        for ($i = 0; $i < $minLen; ++$i) {
            if ($str1[$i] !== $str2[$i]) {
                break;
            }

            ++$prefixLength;
        }

        if ($prefixLength < self::MIN_PREFIX_LENGTH) {
            return 0.0;
        }

        $maxLength = max(strlen($str1), strlen($str2));
        $ratio = $prefixLength / $maxLength;

        return min(0.6, 0.4 + ($ratio * 0.3));
    }

    public function getName(): string
    {
        return 'prefix';
    }

    public function getWeight(): float
    {
        return 0.2;
    }
}
