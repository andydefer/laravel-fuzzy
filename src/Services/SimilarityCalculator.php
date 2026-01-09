<?php

declare(strict_types=1);

namespace Fuzzy\Services;

class SimilarityCalculator
{
    private const MIN_QUERY_LENGTH_FOR_STRICT_MATCH = 4;
    private const LENGTH_DIFFERENCE_PENALTY_FACTOR = 0.3;
    private const SHORT_MATCH_PENALTY = 0.5;
    private const CROSS_WORD_MATCH_PENALTY = 0.4;
    private const MIN_SIMILARITY_THRESHOLD = 0.2;

    public function calculateWordSimilarity(string $queryWord, string $targetWord): float
    {
        $targetWord = (string) $targetWord;
        $queryWord = (string) $queryWord;

        if (empty($queryWord) || empty($targetWord)) {
            return 0.0;
        }

        // Exact match
        if ($queryWord === $targetWord) {
            return 1.0;
        }

        // Contains match
        if (str_contains($targetWord, $queryWord)) {
            return strlen($queryWord) / strlen($targetWord);
        }

        if (str_contains($queryWord, $targetWord)) {
            return strlen($targetWord) / strlen($queryWord);
        }

        // Substring match
        $bestMatchLength = 0;
        $queryLength = strlen($queryWord);

        for ($i = 0; $i <= $queryLength - 2; $i++) {
            for ($j = $i + 2; $j <= $queryLength; $j++) {
                $substring = substr($queryWord, $i, $j - $i);
                if (str_contains($targetWord, $substring)) {
                    $bestMatchLength = max($bestMatchLength, strlen($substring));
                }
            }
        }

        if ($bestMatchLength >= 2) {
            $startsWithBonus = str_starts_with(
                $targetWord,
                substr($queryWord, 0, min(2, strlen($queryWord)))
            ) ? 0.1 : 0.0;

            $baseScore = $bestMatchLength / max(strlen($queryWord), strlen($targetWord));
            $score = min($baseScore + $startsWithBonus, 1.0);

            // Apply penalties
            return $this->applyPenalties($score, $queryWord, $targetWord);
        }

        return 0.0;
    }

    private function applyPenalties(float $baseScore, string $queryWord, string $targetWord): float
    {
        if ($baseScore === 0.0) {
            return 0.0;
        }

        $lengthPenalty = $this->calculateLengthPenalty($queryWord, $targetWord);
        $shortMatchPenalty = $this->calculateShortMatchPenalty($queryWord, $targetWord);
        $crossWordPenalty = $this->isCrossWordMatch($queryWord, $targetWord)
            ? self::CROSS_WORD_MATCH_PENALTY
            : 0.0;

        $totalPenalty = $lengthPenalty + $shortMatchPenalty + $crossWordPenalty;
        $totalPenalty = min($totalPenalty, 0.8);

        $finalScore = $baseScore * (1 - $totalPenalty);

        return max(0.0, min($finalScore, 1.0));
    }

    private function isCrossWordMatch(string $queryWord, string $targetWord): bool
    {
        if (str_contains($targetWord, $queryWord) || str_contains($queryWord, $targetWord)) {
            return false;
        }

        $commonSubstring = $this->findLongestCommonSubstring($queryWord, $targetWord);

        if (strlen($commonSubstring) >= 3) {
            $queryRatio = strlen($commonSubstring) / strlen($queryWord);
            $targetRatio = strlen($commonSubstring) / strlen($targetWord);

            return ($queryRatio < 0.6 && $targetRatio < 0.6);
        }

        return true;
    }

    public function findLongestCommonSubstring(string $str1, string $str2): string
    {
        $longest = '';
        $str1Length = strlen($str1);

        for ($i = 0; $i < $str1Length; $i++) {
            for ($j = $i + 1; $j <= $str1Length; $j++) {
                $substring = substr($str1, $i, $j - $i);
                if (str_contains($str2, $substring) && strlen($substring) > strlen($longest)) {
                    $longest = $substring;
                }
            }
        }

        return $longest;
    }


    /**
     * Calculate consecutive characters bonus
     */
    public function calculateConsecutiveBonus(string $queryWord, string $targetWord): float
    {
        $maxConsecutive = 0;
        $queryLength = strlen($queryWord);

        for ($i = 0; $i < $queryLength; $i++) {
            for ($j = $i + 2; $j <= $queryLength; $j++) {
                $substring = substr($queryWord, $i, $j - $i);
                if (str_contains($targetWord, $substring)) {
                    $maxConsecutive = max($maxConsecutive, strlen($substring));
                }
            }
        }

        if ($maxConsecutive >= 5) return 2.0;
        if ($maxConsecutive >= 4) return 1.6;
        if ($maxConsecutive >= 3) return 1.3;
        if ($maxConsecutive >= 2) return 1.1;

        return 1.0;
    }


    /**
     * Calculate common characters similarity
     */
    public function calculateCommonCharsSimilarity(string $str1, string $str2): float
    {
        $chars1 = array_unique(str_split(strtolower($str1)));
        $chars2 = array_unique(str_split(strtolower($str2)));

        $commonChars = array_intersect($chars1, $chars2);
        $totalUniqueChars = count(array_unique(array_merge($chars1, $chars2)));

        if ($totalUniqueChars === 0) {
            return 0.0;
        }

        return count($commonChars) / $totalUniqueChars;
    }

    private function calculateLengthPenalty(string $queryWord, string $targetWord): float
    {
        $queryLength = strlen($queryWord);

        if ($queryLength < self::MIN_QUERY_LENGTH_FOR_STRICT_MATCH) {
            $lengthDifference = abs(strlen($targetWord) - $queryLength);
            $maxLength = max($queryLength, strlen($targetWord));

            $penalty = ($lengthDifference / $maxLength) * self::LENGTH_DIFFERENCE_PENALTY_FACTOR;

            return min($penalty, 0.5);
        }

        return 0.0;
    }

    private function calculateShortMatchPenalty(string $queryWord, string $targetWord): float
    {
        $queryLength = strlen($queryWord);

        if ($queryLength < self::MIN_QUERY_LENGTH_FOR_STRICT_MATCH) {
            if ($queryWord !== $targetWord) {
                $matchLength = strlen($this->findLongestCommonSubstring($queryWord, $targetWord));
                $matchRatio = $matchLength / $queryLength;

                if ($matchRatio < 0.8) {
                    return self::SHORT_MATCH_PENALTY * (1 - $matchRatio);
                }
            }
        }

        return 0.0;
    }

    /**
     * Calculate similarity between two strings
     */
    public function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = $this->normalizeForComparison($str1);
        $str2 = $this->normalizeForComparison($str2);

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        if ($str1 === $str2) {
            return 1.0;
        }

        $words1 = explode(' ', $str1);
        $words2 = explode(' ', $str2);

        $totalScore = 0.0;
        $matchedWords = 0;

        foreach ($words1 as $word1) {
            $bestScore = 0.0;

            foreach ($words2 as $word2) {
                $score = $this->calculateWordSimilarity($word1, $word2);
                $bestScore = max($bestScore, $score);
            }

            if ($bestScore > self::MIN_SIMILARITY_THRESHOLD) {
                $totalScore += $bestScore;
                $matchedWords++;
            }
        }

        if ($matchedWords === 0) {
            return 0.0;
        }

        $averageScore = $totalScore / count($words1);
        $coverageBonus = ($matchedWords / count($words1)) * 0.2;

        return min($averageScore + $coverageBonus, 1.0);
    }

    private function normalizeForComparison(string $str): string
    {
        return preg_replace('/\s+/', ' ', trim(strtolower($str)));
    }
}
