<?php

declare(strict_types=1);

namespace Fuzzy\Services;

class SimilarityCalculator
{
    private const MIN_QUERY_LENGTH_FOR_STRICT_MATCH = 3;
    private const LENGTH_DIFFERENCE_PENALTY_FACTOR = 0.2;
    private const SHORT_MATCH_PENALTY = 0.7; // Réduit de 0.5 à 0.7
    private const CROSS_WORD_MATCH_PENALTY = 0.6; // Réduit de 0.4 à 0.6
    private const MIN_SIMILARITY_THRESHOLD = 0.25; // Augmenté de 0.2 à 0.25

    public function calculateWordSimilarity(string $queryWord, string $targetWord): float
    {
        $targetWord = (string) $targetWord;
        $queryWord = (string) $queryWord;

        if (empty($queryWord) || empty($targetWord)) {
            return 0.0;
        }

        // 1. Correspondance exacte
        if ($queryWord === $targetWord) {
            return 1.0;
        }

        // 2. Contient la requête (bon score)
        if (str_contains($targetWord, $queryWord)) {
            $containmentScore = strlen($queryWord) / strlen($targetWord);
            return min($containmentScore * 1.2, 0.95); // Max 0.95 pour ne pas être parfait
        }

        // 3. Requête contient le mot cible
        if (str_contains($queryWord, $targetWord)) {
            $containmentScore = strlen($targetWord) / strlen($queryWord);
            return min($containmentScore * 1.1, 0.9);
        }

        // 4. Sous-chaîne commune
        $bestMatchLength = $this->findLongestCommonSubstringLength($queryWord, $targetWord);

        if ($bestMatchLength >= 2) {
            $baseScore = $bestMatchLength / max(strlen($queryWord), strlen($targetWord));

            // Bonus si ça commence pareil
            $startsWithBonus = str_starts_with($targetWord, substr($queryWord, 0, min(2, strlen($queryWord)))) ? 0.15 : 0.0;

            $score = min($baseScore + $startsWithBonus, 0.85); // Max 0.85

            // Appliquer les pénalités
            return $this->applyPenalties($score, $queryWord, $targetWord);
        }

        return 0.0;
    }

    private function findLongestCommonSubstringLength(string $str1, string $str2): int
    {
        $maxLength = 0;
        $str1Length = strlen($str1);

        for ($i = 0; $i < $str1Length; $i++) {
            for ($j = $i + 2; $j <= $str1Length; $j++) {
                $substring = substr($str1, $i, $j - $i);
                if (str_contains($str2, $substring)) {
                    $maxLength = max($maxLength, strlen($substring));
                }
            }
        }

        return $maxLength;
    }

    private function applyPenalties(float $baseScore, string $queryWord, string $targetWord): float
    {
        if ($baseScore === 0.0) {
            return 0.0;
        }

        $penalties = 0.0;

        // Pénalité de différence de longueur
        $lengthDiff = abs(strlen($targetWord) - strlen($queryWord));
        $maxLength = max(strlen($queryWord), strlen($targetWord));

        if ($maxLength > 0) {
            $lengthPenalty = ($lengthDiff / $maxLength) * self::LENGTH_DIFFERENCE_PENALTY_FACTOR;
            $penalties += min($lengthPenalty, 0.3);
        }

        // Pénalité pour requête courte avec match faible
        if (strlen($queryWord) < self::MIN_QUERY_LENGTH_FOR_STRICT_MATCH) {
            $matchRatio = $this->findLongestCommonSubstringLength($queryWord, $targetWord) / strlen($queryWord);
            if ($matchRatio < 0.8) {
                $penalties += self::SHORT_MATCH_PENALTY * (1 - $matchRatio);
            }
        }

        // Pénalité pour match croisé
        if ($this->isCrossWordMatch($queryWord, $targetWord)) {
            $penalties += self::CROSS_WORD_MATCH_PENALTY;
        }

        $totalPenalty = min($penalties, 0.7); // Max 70% de pénalité
        $finalScore = $baseScore * (1 - $totalPenalty);

        return max(self::MIN_SIMILARITY_THRESHOLD, min($finalScore, 1.0));
    }

    private function isCrossWordMatch(string $queryWord, string $targetWord): bool
    {
        // Si un contient l'autre, ce n'est pas un cross-word match
        if (str_contains($targetWord, $queryWord) || str_contains($queryWord, $targetWord)) {
            return false;
        }

        $commonSubstring = $this->getLongestCommonSubstring($queryWord, $targetWord);

        if (strlen($commonSubstring) >= 3) {
            $queryRatio = strlen($commonSubstring) / strlen($queryWord);
            $targetRatio = strlen($commonSubstring) / strlen($targetWord);

            // Considéré comme cross-word si la sous-chaîne commune représente moins de 70% des deux mots
            return ($queryRatio < 0.7 && $targetRatio < 0.7);
        }

        return true;
    }

    private function getLongestCommonSubstring(string $str1, string $str2): string
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

        // Bonus de couverture plus modéré
        $coverageBonus = ($matchedWords / count($words1)) * 0.15;

        return min($averageScore + $coverageBonus, 1.0);
    }

    private function normalizeForComparison(string $str): string
    {
        return preg_replace('/\s+/', ' ', trim(strtolower($str)));
    }
}
