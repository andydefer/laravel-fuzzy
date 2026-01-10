<?php

declare(strict_types=1);

namespace Fuzzy\Services;

/**
 * Service for calculating similarity scores between strings using multiple algorithms.
 */
class SimilarityCalculator
{
    private const MIN_QUERY_LENGTH = 2;
    private const MIN_SIMILARITY_THRESHOLD = 0.3;

    /**
     * Calculate similarity score between two words using multiple algorithms.
     *
     * @param string $queryWord The search query word
     * @param string $targetWord The target word to compare against
     * @return float Similarity score between 0.0 (no match) and 1.0 (exact match)
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

        if (str_contains($targetWord, $queryWord)) {
            return $this->calculateContainedSimilarity($queryWord, $targetWord, true);
        }

        if (str_contains($queryWord, $targetWord)) {
            return $this->calculateContainedSimilarity($targetWord, $queryWord, false);
        }

        $lcsLength = $this->calculateLongestCommonSubstringLength($queryWord, $targetWord);
        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);
        $minLength = min($queryLength, $targetLength);

        $lcsScore = $this->calculateLcsBasedScore($lcsLength, $queryLength, $targetLength, $minLength);
        if ($lcsScore > 0.0) {
            return $lcsScore;
        }

        $levenshteinScore = $this->calculateNormalizedLevenshtein($queryWord, $targetWord);
        $levenshteinScore = $this->adjustLevenshteinScore($levenshteinScore, $minLength);

        if ($levenshteinScore > 0.0) {
            return $levenshteinScore;
        }

        if ($this->hasCommonPrefix($queryWord, $targetWord, 3)) {
            return $this->calculatePrefixSimilarity($queryWord, $targetWord);
        }

        return 0.0;
    }

    /**
     * Calculate similarity between two strings based on word-level comparisons.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Overall similarity score between 0.0 and 1.0
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
     * Calculate similarity when one word is contained within another.
     *
     * @param string $contained The word that is contained within the other
     * @param string $container The word that contains the other word
     * @param bool $isQueryInTarget Whether the query word is contained in the target word
     * @return float Similarity score with appropriate weighting
     */
    private function calculateContainedSimilarity(string $contained, string $container, bool $isQueryInTarget): float
    {
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
     * Calculate similarity score based on Longest Common Substring length.
     *
     * @param int $lcsLength Length of the longest common substring
     * @param int $queryLength Length of the query word
     * @param int $targetLength Length of the target word
     * @param int $minLength Minimum length between the two words
     * @return float Similarity score based on LCS analysis
     */
    private function calculateLcsBasedScore(int $lcsLength, int $queryLength, int $targetLength, int $minLength): float
    {
        if ($lcsLength >= $minLength - 1 && $minLength >= 4) {
            $ratio = $lcsLength / max($queryLength, $targetLength);
            return min(0.85, 0.7 + ($ratio * 0.2));
        }

        if ($lcsLength >= $minLength - 2 && $minLength >= 5) {
            $ratio = $lcsLength / max($queryLength, $targetLength);
            return min(0.8, 0.6 + ($ratio * 0.25));
        }

        if ($lcsLength >= 3) {
            $maxLength = max($queryLength, $targetLength);
            $lcsRatio = $lcsLength / $maxLength;

            if ($lcsRatio >= 0.7) {
                return min(0.8, $lcsRatio * 1.1);
            }

            if ($lcsRatio >= 0.5) {
                return min(0.7, $lcsRatio * 1.05);
            }

            if ($lcsRatio >= 0.3) {
                return $lcsRatio;
            }
        }

        return 0.0;
    }

    /**
     * Calculate normalized Levenshtein distance between two strings.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Normalized similarity score based on Levenshtein distance
     */
    private function calculateNormalizedLevenshtein(string $str1, string $str2): float
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

    /**
     * Adjust Levenshtein score based on word length for better accuracy.
     *
     * @param float $score The original Levenshtein score
     * @param int $minLength Minimum length of the two words being compared
     * @return float Adjusted score with length-based filtering
     */
    private function adjustLevenshteinScore(float $score, int $minLength): float
    {
        if ($score >= 0.7) {
            return $score;
        }

        if ($score >= 0.5 && $minLength >= 4) {
            return $score;
        }

        if ($score >= 0.3 && $minLength >= 6) {
            return $score * 0.8;
        }

        return 0.0;
    }

    /**
     * Calculate similarity based on common prefix between two strings.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score based on shared prefix
     */
    private function calculatePrefixSimilarity(string $str1, string $str2): float
    {
        $prefixLength = $this->calculateCommonPrefixLength($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        $ratio = $prefixLength / $maxLength;

        return min(0.6, 0.4 + ($ratio * 0.3));
    }

    /**
     * Calculate final similarity score with coverage-based bonus or penalty.
     *
     * @param float $totalScore Sum of all word similarity scores
     * @param int $matchedWords Number of words that found matches
     * @param int $totalWords Total number of words in the query
     * @return float Final score adjusted by coverage percentage
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
     * Calculate length of the longest common substring between two strings.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return int Length of the longest common substring
     */
    private function calculateLongestCommonSubstringLength(string $str1, string $str2): int
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
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

        return $maxLength;
    }

    /**
     * Check if two strings share a common prefix of at least the specified length.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @param int $minLength Minimum required prefix length
     * @return bool True if strings share a common prefix of minimum length
     */
    private function hasCommonPrefix(string $str1, string $str2, int $minLength = 3): bool
    {
        $minLen = min(strlen($str1), strlen($str2));

        if ($minLen < $minLength) {
            return false;
        }

        for ($i = 0; $i < $minLength; $i++) {
            if ($str1[$i] !== $str2[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate the exact length of the common prefix between two strings.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return int Number of characters that match from the start of both strings
     */
    private function calculateCommonPrefixLength(string $str1, string $str2): int
    {
        $minLen = min(strlen($str1), strlen($str2));
        $length = 0;

        for ($i = 0; $i < $minLen; $i++) {
            if ($str1[$i] !== $str2[$i]) {
                break;
            }
            $length++;
        }

        return $length;
    }

    /**
     * Normalize string for comparison by removing special characters and extra whitespace.
     *
     * @param string $str The string to normalize
     * @return string Cleaned string ready for comparison
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
