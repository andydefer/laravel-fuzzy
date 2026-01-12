<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm;
use Fuzzy\Services\Algorithms\LevenshteinSimilarityAlgorithm;
use Fuzzy\Services\Algorithms\PrefixSimilarityAlgorithm;
use Fuzzy\Contracts\SimilarityAlgorithmInterface;

/**
 * Composite similarity calculator that uses multiple algorithms
 * to compute similarity scores between strings.
 *
 * Aggregates results from registered algorithms using weighted averaging
 * to produce robust similarity measurements for fuzzy search operations.
 */
class SimilarityCalculator
{
    private const MIN_QUERY_LENGTH = 2;

    /** @var array<int, SimilarityAlgorithmInterface> Registered similarity algorithms */
    private array $algorithms = [];

    /**
     * Initialize the calculator with default similarity algorithms.
     */
    public function __construct()
    {
        $this->registerDefaultAlgorithms();
    }

    /**
     * Register the default set of similarity algorithms.
     */
    private function registerDefaultAlgorithms(): void
    {
        $this->algorithms = [
            new LongestCommonSubstringAlgorithm(),
            new LevenshteinSimilarityAlgorithm(),
            new PrefixSimilarityAlgorithm(),
        ];
    }

    /**
     * Register a custom similarity algorithm.
     *
     * @param SimilarityAlgorithmInterface $algorithm The algorithm to register
     */
    public function addAlgorithm(SimilarityAlgorithmInterface $algorithm): void
    {
        $this->algorithms[] = $algorithm;
    }

    /**
     * Calculate similarity score between two individual words.
     *
     * @param string $queryWord The search query word
     * @param string $targetWord The target word to compare against
     * @return float Similarity score between 0.0 and 1.0
     */
    public function calculateWordSimilarity(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower(trim($queryWord));
        $targetWord = strtolower(trim($targetWord));

        if ($queryWord === '' || $targetWord === '') {
            return 0.0;
        }

        if ($queryWord === $targetWord) {
            return 1.0;
        }

        if (str_contains($targetWord, $queryWord)) {
            return $this->calculateContainedWordSimilarity(
                containedWord: $queryWord,
                containerWord: $targetWord,
                isQueryInTarget: true
            );
        }

        if (str_contains($queryWord, $targetWord)) {
            return $this->calculateContainedWordSimilarity(
                containedWord: $targetWord,
                containerWord: $queryWord,
                isQueryInTarget: false
            );
        }

        return $this->calculateCompositeWordSimilarity($queryWord, $targetWord);
    }

    /**
     * Calculate similarity score using all registered algorithms.
     *
     * @param string $firstWord First word to compare
     * @param string $secondWord Second word to compare
     * @return float Weighted average similarity score
     */
    private function calculateCompositeWordSimilarity(string $firstWord, string $secondWord): float
    {
        $totalScore = 0.0;
        $totalWeight = 0.0;

        foreach ($this->algorithms as $algorithm) {
            $score = $algorithm->calculate($firstWord, $secondWord);
            $weight = $algorithm->getWeight();

            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalScore / $totalWeight : 0.0;
    }

    /**
     * Calculate similarity when one word is fully contained within another.
     *
     * @param string $containedWord The shorter word contained within the longer
     * @param string $containerWord The longer word containing the shorter
     * @param bool $isQueryInTarget Whether the query is contained in the target
     * @return float Similarity score with containment bonus
     */
    private function calculateContainedWordSimilarity(
        string $containedWord,
        string $containerWord,
        bool $isQueryInTarget
    ): float {
        $containmentRatio = strlen($containedWord) / strlen($containerWord);

        if ($containmentRatio >= 0.8) {
            return $isQueryInTarget ? 0.95 : 0.9;
        }

        $baseScore = $isQueryInTarget ? 0.75 : 0.65;
        $multiplier = $isQueryInTarget ? 0.2 : 0.25;
        $maxScore = $isQueryInTarget ? 0.9 : 0.85;

        return min($maxScore, $baseScore + ($containmentRatio * $multiplier));
    }

    /**
     * Calculate similarity between two strings based on word-level comparisons.
     *
     * @param string $firstString First string to compare
     * @param string $secondString Second string to compare
     * @return float Overall similarity score between 0.0 and 1.0
     */
    public function calculateSimilarity(string $firstString, string $secondString): float
    {
        $normalizedFirstString = $this->normalizeForComparison($firstString);
        $normalizedSecondString = $this->normalizeForComparison($secondString);

        if ($normalizedFirstString === $normalizedSecondString) {
            return 1.0;
        }

        if ($normalizedFirstString === '' || $normalizedSecondString === '') {
            return 0.0;
        }

        $firstWords = $this->splitIntoWords($normalizedFirstString);
        $secondWords = $this->splitIntoWords($normalizedSecondString);

        if (empty($firstWords) || empty($secondWords)) {
            return 0.0;
        }

        $totalScore = 0.0;
        $matchedWords = 0;

        foreach ($firstWords as $firstWord) {
            if (strlen($firstWord) < self::MIN_QUERY_LENGTH) {
                continue;
            }

            $bestScore = $this->findBestWordMatchScore($firstWord, $secondWords);

            if ($bestScore > 0) {
                $totalScore += $bestScore;
                ++$matchedWords;
            }
        }

        if ($totalScore === 0.0) {
            return 0.0;
        }

        return $this->calculateNormalizedScore($totalScore, $matchedWords, count($firstWords));
    }

    /**
     * Find the best matching score for a word against an array of candidate words.
     *
     * @param string $searchWord The word to find matches for
     * @param array<int, string> $candidateWords Array of words to compare against
     * @return float Best similarity score found
     */
    private function findBestWordMatchScore(string $searchWord, array $candidateWords): float
    {
        $bestScore = 0.0;

        foreach ($candidateWords as $candidateWord) {
            $score = $this->calculateWordSimilarity($searchWord, $candidateWord);

            if ($score > $bestScore) {
                $bestScore = $score;
            }
        }

        return $bestScore;
    }

    /**
     * Calculate final normalized score with coverage-based adjustments.
     *
     * @param float $totalScore Sum of all individual word scores
     * @param int $matchedWords Number of words that found matches
     * @param int $totalWords Total number of words in the query
     * @return float Final normalized similarity score
     */
    private function calculateNormalizedScore(float $totalScore, int $matchedWords, int $totalWords): float
    {
        $averageScore = $totalScore / $totalWords;
        $coverageRatio = $matchedWords / $totalWords;

        if ($coverageRatio >= 0.5) {
            $coverageBonus = $coverageRatio * 0.15;
            return min($averageScore + $coverageBonus, 1.0);
        }

        return $averageScore * $coverageRatio * 1.5;
    }

    /**
     * Normalize a string for comparison by removing special characters
     * and collapsing multiple spaces.
     *
     * @param string $string The string to normalize
     * @return string Normalized string
     */
    private function normalizeForComparison(string $string): string
    {
        if ($string === '') {
            return '';
        }

        $normalized = preg_replace('/[^a-z0-9\s]/i', ' ', $string);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Split a string into individual words.
     *
     * @param string $string The string to split
     * @return array<int, string> Array of words
     */
    private function splitIntoWords(string $string): array
    {
        $words = preg_split('/[\s\-_,\.]+/', $string);
        return $words !== false ? $words : [];
    }
}
