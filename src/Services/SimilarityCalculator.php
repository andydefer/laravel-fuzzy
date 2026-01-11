<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use Fuzzy\Services\Algorithms;

/**
 * Composite similarity calculator using multiple algorithms.
 */
class SimilarityCalculator
{
    private const MIN_QUERY_LENGTH = 2;
    private array $algorithms = [];

    public function __construct()
    {
        $this->registerDefaultAlgorithms();
    }

    /**
     * Register default similarity algorithms.
     */
    private function registerDefaultAlgorithms(): void
    {
        $this->algorithms = [
            new Algorithms\LongestCommonSubstringAlgorithm(),
            new Algorithms\LevenshteinSimilarityAlgorithm(),
            new Algorithms\PrefixSimilarityAlgorithm(),
        ];
    }

    /**
     * Add a custom algorithm.
     */
    public function addAlgorithm(SimilarityAlgorithmInterface $algorithm): void
    {
        $this->algorithms[] = $algorithm;
    }

    /**
     * Calculate similarity score between two words using multiple algorithms.
     */
    public function calculateWordSimilarity(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower(trim($queryWord));
        $targetWord = strtolower(trim($targetWord));

        if (empty($queryWord) || empty($targetWord)) {
            return 0.0;
        }

        if ($queryWord === $targetWord) {
            return 1.0;
        }

        // Check for contained words (fast path)
        if (str_contains($targetWord, $queryWord)) {
            return $this->calculateContainedSimilarity($queryWord, $targetWord, true);
        }

        if (str_contains($queryWord, $targetWord)) {
            return $this->calculateContainedSimilarity($targetWord, $queryWord, false);
        }

        // Use composite algorithm score
        return $this->calculateCompositeSimilarity($queryWord, $targetWord);
    }

    /**
     * Calculate composite similarity using registered algorithms.
     */
    private function calculateCompositeSimilarity(string $str1, string $str2): float
    {
        $totalScore = 0.0;
        $totalWeight = 0.0;

        foreach ($this->algorithms as $algorithm) {
            $score = $algorithm->calculate($str1, $str2);
            $weight = $algorithm->getWeight();

            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalScore / $totalWeight : 0.0;
    }

    /**
     * Calculate similarity when one word is contained within another.
     */
    private function calculateContainedSimilarity(
        string $contained,
        string $container,
        bool $isQueryInTarget
    ): float {
        $ratio = strlen($contained) / strlen($container);

        if ($ratio >= 0.8) {
            return $isQueryInTarget ? 0.95 : 0.9;
        }

        $baseScore = $isQueryInTarget ? 0.75 : 0.65;
        $multiplier = $isQueryInTarget ? 0.2 : 0.25;
        $maxScore = $isQueryInTarget ? 0.9 : 0.85;

        return min($maxScore, $baseScore + ($ratio * $multiplier));
    }

    /**
     * Calculate similarity between two strings based on word-level comparisons.
     */
    public function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = $this->normalizeForComparison($str1);
        $str2 = $this->normalizeForComparison($str2);

        if ($str1 === $str2) {
            return 1.0;
        }

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        $words1 = preg_split('/[\s\-_,\.]+/', $str1);
        $words2 = preg_split('/[\s\-_,\.]+/', $str2);

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        $totalScore = 0.0;
        $matchedWords = 0;

        foreach ($words1 as $word1) {
            $word1 = (string) $word1;

            if (strlen($word1) < self::MIN_QUERY_LENGTH) {
                continue;
            }

            $bestScore = 0.0;

            foreach ($words2 as $word2) {
                $word2 = (string) $word2;
                $score = $this->calculateWordSimilarity($word1, $word2);

                if ($score > $bestScore) {
                    $bestScore = $score;
                }
            }

            if ($bestScore > 0) {
                $totalScore += $bestScore;
                $matchedWords++;
            }
        }

        if ($totalScore === 0.0) {
            return 0.0;
        }

        return $this->calculateFinalScore($totalScore, $matchedWords, count($words1));
    }

    /**
     * Calculate final similarity score with coverage-based bonus or penalty.
     */
    private function calculateFinalScore(float $totalScore, int $matchedWords, int $totalWords): float
    {
        $averageScore = $totalScore / $totalWords;
        $coverage = $matchedWords / $totalWords;

        if ($coverage >= 0.5) {
            $coverageBonus = $coverage * 0.15;
            return min($averageScore + $coverageBonus, 1.0);
        }

        return $averageScore * $coverage * 1.5;
    }

    /**
     * Normalize string for comparison.
     */
    private function normalizeForComparison(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        $normalized = preg_replace('/[^a-z0-9\s]/i', ' ', $str);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }
}
