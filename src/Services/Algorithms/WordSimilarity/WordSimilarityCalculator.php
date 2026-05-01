<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms\WordSimilarity;

use Fuzzy\Config\WordSimilarityComparatorConfig;

/**
 * Calculates similarity between two individual words.
 */
class WordSimilarityCalculator
{
    private WordSimilarityComparatorConfig $config;
    private LetterDistanceCalculator $letterDistanceCalculator;

    public function __construct(WordSimilarityComparatorConfig $config)
    {
        $this->config = $config;
        $this->letterDistanceCalculator = new LetterDistanceCalculator($config);
    }

    /**
     * Calculate similarity between two words (0 = identical, higher = more different).
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float Similarity score (lower is better)
     */
    public function calculateWordSimilarity(string $wordA, string $wordB): float
    {
        // Normalize case
        $wordA = strtolower($wordA);
        $wordB = strtolower($wordB);

        // Perfect match
        if ($wordA === $wordB) {
            return FUZZY_DISTANCE_IDENTICAL;
        }

        // Check for containment (one word inside another)
        $containmentScore = $this->calculateContainmentScore($wordA, $wordB);
        if ($containmentScore !== null) {
            return $containmentScore;
        }

        $basicSimilarity = $this->calculateBasicSimilarity($wordA, $wordB);
        $threshold = $this->config->getBasicSimilarityThreshold();
        $fallback = $this->config->getBasicSimilarityFallback();

        if ($basicSimilarity < $threshold) {
            return max($fallback, $this->calculateDetailedSimilarity($wordA, $wordB));
        }

        return $this->calculateDetailedSimilarity($wordA, $wordB);
    }

    /**
     * Calculate real similarity ratio between two words (0-1).
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float Similarity ratio (1.0 = identical letters)
     */
    public function calculateWordRealSimilarity(string $wordA, string $wordB): float
    {
        $startIndex = $this->config->getStartIndex();

        // Normalize case for consistent comparison
        $normalizedA = strtolower($wordA);
        $normalizedB = strtolower($wordB);

        $lettersA = array_unique(str_split($normalizedA));
        $lettersB = array_unique(str_split($normalizedB));

        $commonLetters = $this->countCommonLetters($lettersA, $lettersB);
        $totalUniqueLetters = count(array_unique(array_merge($lettersA, $lettersB)));

        return $totalUniqueLetters > $startIndex
            ? $commonLetters / $totalUniqueLetters
            : FUZZY_SCORE_NONE;
    }

    /**
     * Calculate containment score when one word is contained within another.
     */
    private function calculateContainmentScore(string $wordA, string $wordB): ?float
    {
        $isContained = str_contains($wordA, $wordB) || str_contains($wordB, $wordA);

        if (!$isContained) {
            return null;
        }

        $minLengthForDivision = $this->config->getMinLengthForDivision();
        $highContainmentRatio = $this->config->getHighContainmentRatio();
        $mediumContainmentRatio = $this->config->getMediumContainmentRatio();
        $highMultiplier = $this->config->getContainmentHighMultiplier();
        $mediumMultiplier = $this->config->getContainmentMediumMultiplier();

        $shorterLength = min(strlen($wordA), strlen($wordB));
        $longerLength = max(strlen($wordA), strlen($wordB));
        $containmentRatio = $shorterLength / max($minLengthForDivision, $longerLength);

        if ($containmentRatio >= $highContainmentRatio) {
            return (FUZZY_BASE_FACTOR - $containmentRatio) * $highMultiplier;
        }

        if ($containmentRatio >= $mediumContainmentRatio) {
            return (FUZZY_BASE_FACTOR - $containmentRatio) * $mediumMultiplier;
        }

        return null;
    }

    /**
     * Calculate basic similarity ratio using unique letter comparison.
     */
    private function calculateBasicSimilarity(string $wordA, string $wordB): float
    {
        $startIndex = $this->config->getStartIndex();
        $lettersA = array_unique(str_split($wordA));
        $lettersB = array_unique(str_split($wordB));

        $commonLetters = $this->countCommonLetters($lettersA, $lettersB);
        $totalUniqueLetters = count(array_unique(array_merge($lettersA, $lettersB)));

        return $totalUniqueLetters > $startIndex
            ? $commonLetters / $totalUniqueLetters
            : FUZZY_SCORE_NONE;
    }

    /**
     * Calculate detailed similarity score using position and phonetic analysis.
     */
    private function calculateDetailedSimilarity(string $wordA, string $wordB): float
    {
        $minLengthForDivision = $this->config->getMinLengthForDivision();
        $scoreMultiplier = $this->config->getScoreMultiplier();
        $startIndex = $this->config->getStartIndex();

        $letterDistance = $this->letterDistanceCalculator->calculateLetterDistance($wordA, $wordB);
        $maxLength = max(strlen($wordA), strlen($wordB), $minLengthForDivision);
        $baseScore = $letterDistance / $maxLength * $this->config->getWordPenaltyPerChar() * $scoreMultiplier;

        // Apply phonetic reduction if words sound similar
        if ($this->areWordsPhoneticallySimilar($wordA, $wordB)) {
            $baseScore *= $this->config->getPhoneticReductionFactor();
        }

        // Apply length difference penalty
        $lengthDifference = abs(strlen($wordA) - strlen($wordB));
        if ($lengthDifference > $startIndex) {
            $baseScore += $lengthDifference * $this->config->getLengthDifferencePenalty();
        }

        return max($this->config->getMinimalPenalty(), $baseScore);
    }

    /**
     * Check if two words are phonetically similar using Soundex algorithm.
     */
    private function areWordsPhoneticallySimilar(string $wordA, string $wordB): bool
    {
        return soundex($wordA) === soundex($wordB);
    }

    /**
     * Count common letters between two letter sets.
     */
    private function countCommonLetters(array $lettersA, array $lettersB): int
    {
        $startIndex = $this->config->getStartIndex();
        $commonCount = $startIndex;

        foreach ($lettersA as $letterA) {
            if (in_array($letterA, $lettersB, true)) {
                $commonCount++;
            }
        }

        return $commonCount;
    }
}
