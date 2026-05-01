<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm;
use Fuzzy\Services\Algorithms\LevenshteinSimilarityAlgorithm;
use Fuzzy\Services\Algorithms\PrefixSimilarityAlgorithm;
use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use Fuzzy\Config\SimilarityCalculatorConfig;

class SimilarityCalculator
{
    /** @var array<int, SimilarityAlgorithmInterface> */
    private array $algorithms = [];
    private SimilarityCalculatorConfig $config;

    public function __construct(?SimilarityCalculatorConfig $config = null)
    {
        $this->config = $config ?? SimilarityCalculatorConfig::createDefault();
        // Ne plus enregistrer les algorithmes par défaut ici
        // Ils seront ajoutés par le ServiceRegistrar
    }

    public function addAlgorithm(SimilarityAlgorithmInterface $algorithm): void
    {
        $this->algorithms[] = $algorithm;
    }

    public function calculateWordSimilarity(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower(trim($queryWord));
        $targetWord = strtolower(trim($targetWord));

        if ($queryWord === '' || $targetWord === '') {
            return FUZZY_SCORE_NONE;
        }

        if ($queryWord === $targetWord) {
            return FUZZY_SCORE_IDENTICAL;
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

    private function calculateCompositeWordSimilarity(string $firstWord, string $secondWord): float
    {
        if (empty($this->algorithms)) {
            return FUZZY_SCORE_NONE;
        }

        $totalScore = FUZZY_SCORE_NONE;
        $totalWeight = FUZZY_SCORE_NONE;

        foreach ($this->algorithms as $algorithm) {
            $score = $algorithm->calculate($firstWord, $secondWord);
            $weight = $algorithm->getWeight();

            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > FUZZY_SCORE_NONE ? $totalScore / $totalWeight : FUZZY_SCORE_NONE;
    }

    private function calculateContainedWordSimilarity(
        string $containedWord,
        string $containerWord,
        bool $isQueryInTarget
    ): float {
        $containmentRatio = strlen($containedWord) / strlen($containerWord);

        if ($containmentRatio >= $this->config->getContainmentHighRatio()) {
            return $isQueryInTarget
                ? $this->config->getContainmentQueryInTargetHighScore()
                : $this->config->getContainmentTargetInQueryHighScore();
        }

        $baseScore = $isQueryInTarget
            ? $this->config->getContainmentBaseScoreQueryInTarget()
            : $this->config->getContainmentBaseScoreTargetInQuery();

        $multiplier = $isQueryInTarget
            ? $this->config->getContainmentMultiplierQueryInTarget()
            : $this->config->getContainmentMultiplierTargetInQuery();

        $maxScore = $isQueryInTarget
            ? $this->config->getContainmentMaxScoreQueryInTarget()
            : $this->config->getContainmentMaxScoreTargetInQuery();

        return min($maxScore, $baseScore + ($containmentRatio * $multiplier));
    }

    public function calculateSimilarity(string $firstString, string $secondString): float
    {
        $normalizedFirstString = $this->normalizeForComparison($firstString);
        $normalizedSecondString = $this->normalizeForComparison($secondString);

        if ($normalizedFirstString === $normalizedSecondString) {
            return FUZZY_SCORE_IDENTICAL;
        }

        if ($normalizedFirstString === '' || $normalizedSecondString === '') {
            return FUZZY_SCORE_NONE;
        }

        $firstWords = $this->splitIntoWords($normalizedFirstString);
        $secondWords = $this->splitIntoWords($normalizedSecondString);

        if (empty($firstWords) || empty($secondWords)) {
            return FUZZY_SCORE_NONE;
        }

        $totalScore = FUZZY_SCORE_NONE;
        $matchedWords = 0;

        foreach ($firstWords as $firstWord) {
            if (strlen($firstWord) < $this->config->getMinQueryLength()) {
                continue;
            }

            $bestScore = $this->findBestWordMatchScore($firstWord, $secondWords);

            if ($bestScore > FUZZY_SCORE_NONE) {
                $totalScore += $bestScore;
                ++$matchedWords;
            }
        }

        if ($totalScore === FUZZY_SCORE_NONE) {
            return FUZZY_SCORE_NONE;
        }

        return $this->calculateNormalizedScore($totalScore, $matchedWords, count($firstWords));
    }

    private function findBestWordMatchScore(string $searchWord, array $candidateWords): float
    {
        $bestScore = FUZZY_SCORE_NONE;

        foreach ($candidateWords as $candidateWord) {
            $score = $this->calculateWordSimilarity($searchWord, $candidateWord);

            if ($score > $bestScore) {
                $bestScore = $score;
            }
        }

        return $bestScore;
    }

    private function calculateNormalizedScore(float $totalScore, int $matchedWords, int $totalWords): float
    {
        $averageScore = $totalScore / $totalWords;
        $coverageRatio = $matchedWords / $totalWords;

        if ($coverageRatio >= $this->config->getCoverageBonusThreshold()) {
            $coverageBonus = $coverageRatio * $this->config->getCoverageBonusMultiplier();
            return min($averageScore + $coverageBonus, FUZZY_SCORE_IDENTICAL);
        }

        return $averageScore * $coverageRatio * $this->config->getLowCoverageMultiplier();
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

        // Remove special characters (keep letters, numbers, spaces)
        $normalized = preg_replace($this->config->getRegexRemoveSpecialChars(), ' ', $string);

        // Collapse multiple spaces into single space
        $normalized = preg_replace($this->config->getRegexCollapseSpaces(), ' ', $normalized);

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
        $words = preg_split($this->config->getRegexWordSplitter(), $string);
        return $words !== false ? $words : [];
    }
}
