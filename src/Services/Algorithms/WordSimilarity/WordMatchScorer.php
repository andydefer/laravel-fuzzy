<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms\WordSimilarity;

use Fuzzy\Config\WordSimilarityComparatorConfig;

/**
 * Handles word matching and scoring between query and text words.
 */
class WordMatchScorer
{
    private WordSimilarityComparatorConfig $config;
    private WordSimilarityCalculator $wordSimilarityCalculator;

    public function __construct(WordSimilarityComparatorConfig $config)
    {
        $this->config = $config;
        $this->wordSimilarityCalculator = new WordSimilarityCalculator($config);
    }

    /**
     * Calculate overall score based on query words matching against text words.
     *
     * @param array<int, string> $queryWords Query words
     * @param array<int, string> $textWords Text words
     * @param float $sigma Weight factor
     * @return float Global similarity score
     */
    public function calculateQueryBasedScore(array $queryWords, array $textWords, float $sigma): float
    {
        $emptyTextPenalty = $this->calculateEmptyTextPenalty($queryWords);
        $baseIncrement = $this->config->getBaseIncrement();

        if (empty($textWords)) {
            return $emptyTextPenalty * $sigma;
        }

        $bestScores = $this->findBestScoresForQuery($queryWords, $textWords);

        if (empty($bestScores)) {
            return $emptyTextPenalty * $sigma;
        }

        $averageScore = array_sum($bestScores) / count($bestScores);
        $veryBadMatchCount = $this->countVeryBadMatches($bestScores);
        $veryBadPenalty = $veryBadMatchCount * $this->config->getVeryBadMatchPenalty();

        $finalScore = $averageScore + $veryBadPenalty;

        // Apply strictness factor for multi-word queries
        if (count($queryWords) > $baseIncrement) {
            $strictnessFactor = FUZZY_BASE_FACTOR + (count($queryWords) * $this->config->getStrictnessFactorPerWord());
            $finalScore *= $strictnessFactor;
        }

        $finalScore *= $sigma;

        return max($this->config->getMinimalPenalty(), $finalScore);
    }

    /**
     * Find the best matching score for each query word.
     *
     * @param array<int, string> $queryWords Query words
     * @param array<int, string> $textWords Text words
     * @return array<int, float> Best scores for each query word
     */
    private function findBestScoresForQuery(array $queryWords, array $textWords): array
    {
        $bestScores = [];

        foreach ($queryWords as $queryWord) {
            $bestScore = $this->findBestWordMatchScore($queryWord, $textWords);
            $bestScore = $this->validateWordMatchScore($queryWord, $textWords, $bestScore);
            $bestScores[] = $bestScore;
        }

        return $bestScores;
    }

    /**
     * Find the best match score for a single query word against text words.
     *
     * @param string $queryWord Query word
     * @param array<int, string> $textWords Text words
     * @return float Best match score (lower is better)
     */
    private function findBestWordMatchScore(string $queryWord, array $textWords): float
    {
        $bestScore = PHP_FLOAT_MAX;

        foreach ($textWords as $textWord) {
            $score = $this->wordSimilarityCalculator->calculateWordSimilarity($queryWord, $textWord);
            $score = $this->validateAndAdjustScore($queryWord, $textWord, $score);

            if ($score < $bestScore) {
                $bestScore = $score;
            }
        }

        return $bestScore;
    }

    /**
     * Validate and adjust a word match score if necessary.
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @param float $score Calculated score
     * @return float Adjusted score
     */
    private function validateAndAdjustScore(string $wordA, string $wordB, float $score): float
    {
        $threshold = $this->config->getRealSimilarityThreshold();
        $basePenalty = $this->config->getRealSimilarityBasePenalty();
        $multiplier = $this->config->getRealSimilarityMultiplier();

        if ($score < $basePenalty) {
            $realSimilarity = $this->wordSimilarityCalculator->calculateWordRealSimilarity($wordA, $wordB);

            if ($realSimilarity < $threshold && $score < $basePenalty) {
                return max($score, $basePenalty + (FUZZY_BASE_FACTOR - $realSimilarity) * $multiplier);
            }
        }

        return $score;
    }

    /**
     * Validate a word match score against text words.
     *
     * @param string $queryWord Query word
     * @param array<int, string> $textWords Text words
     * @param float $score Current score
     * @return float Validated score
     */
    private function validateWordMatchScore(string $queryWord, array $textWords, float $score): float
    {
        $threshold = $this->config->getLowSimilarityThreshold();
        $penalty = $this->config->getLowSimilarityPenalty();
        $basePenalty = $this->config->getRealSimilarityBasePenalty();

        if ($score < $basePenalty) {
            $similarity = $this->calculateBestRealSimilarity($queryWord, $textWords);
            if ($similarity < $threshold) {
                return max($score, $penalty);
            }
        }

        return $score;
    }

    /**
     * Calculate the best real similarity of a word against a set of words.
     *
     * @param string $queryWord Query word
     * @param array<int, string> $textWords Text words
     * @return float Best similarity ratio
     */
    private function calculateBestRealSimilarity(string $queryWord, array $textWords): float
    {
        $bestSimilarity = FUZZY_SCORE_NONE;

        foreach ($textWords as $textWord) {
            $similarity = $this->wordSimilarityCalculator->calculateWordRealSimilarity($queryWord, $textWord);
            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
            }
        }

        return $bestSimilarity;
    }

    /**
     * Count how many scores exceed the "very bad match" threshold.
     *
     * @param array<int, float> $scores Match scores
     * @return int Number of very bad matches
     */
    private function countVeryBadMatches(array $scores): int
    {
        $startIndex = $this->config->getStartIndex();
        $badMatchCount = $startIndex;
        $threshold = $this->config->getVeryBadMatchThreshold();

        foreach ($scores as $score) {
            if ($score > $threshold) {
                $badMatchCount++;
            }
        }

        return $badMatchCount;
    }

    private function calculateEmptyTextPenalty(array $queryWords): float
    {
        $wordCount = count($queryWords);
        $penaltyPerChar = $this->config->getWordPenaltyPerChar();
        $lengthMultiplier = $this->config->getLengthPenaltyMultiplier();

        return $wordCount * $penaltyPerChar * $lengthMultiplier;
    }
}
