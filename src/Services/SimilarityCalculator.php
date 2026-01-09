<?php

namespace Fuzzy\Services;

class SimilarityCalculator
{
    private const MIN_QUERY_LENGTH = 2;
    private const MIN_SIMILARITY_THRESHOLD = 0.3;

    public function calculateWordSimilarity(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower(trim($queryWord));
        $targetWord = strtolower(trim($targetWord));

        if (empty($queryWord) || empty($targetWord)) {
            return 0.0;
        }

        // 1. Exact match
        if ($queryWord === $targetWord) {
            return 1.0;
        }

        // 2. La requête est contenue dans la cible
        if (str_contains($targetWord, $queryWord)) {
            $ratio = strlen($queryWord) / strlen($targetWord);
            if ($ratio >= 0.8) {
                return 0.95; // Presque parfait
            }
            return min(0.9, 0.75 + ($ratio * 0.2)); // Entre 0.75 et 0.9
        }

        // 3. La cible est contenue dans la requête
        if (str_contains($queryWord, $targetWord)) {
            $ratio = strlen($targetWord) / strlen($queryWord);
            if ($ratio >= 0.8) {
                return 0.9; // Réduit de 0.95
            }
            return min(0.85, 0.65 + ($ratio * 0.25)); // Entre 0.65 et 0.85
        }

        // 4. Calculer la plus longue sous-chaîne commune
        $lcsLength = $this->longestCommonSubstringLength($queryWord, $targetWord);

        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);

        // CAS SPÉCIAL : La LCS représente une grande partie d'un des mots
        $minLength = min($queryLength, $targetLength);

        if ($lcsLength >= $minLength - 1 && $minLength >= 4) {
            // LCS presque complète (1 lettre de différence)
            $ratio = $lcsLength / max($queryLength, $targetLength);
            return min(0.85, 0.7 + ($ratio * 0.2));
        }

        if ($lcsLength >= $minLength - 2 && $minLength >= 5) {
            // LCS à 2 lettres près
            $ratio = $lcsLength / max($queryLength, $targetLength);
            return min(0.8, 0.6 + ($ratio * 0.25));
        }

        // 5. Calcul de similarité basé sur LCS pour les cas moyens
        if ($lcsLength >= 3) {
            $maxLength = max($queryLength, $targetLength);
            $lcsRatio = $lcsLength / $maxLength;

            if ($lcsRatio >= 0.7) {
                return min(0.8, $lcsRatio * 1.1);
            } elseif ($lcsRatio >= 0.5) {
                return min(0.7, $lcsRatio * 1.05);
            } elseif ($lcsRatio >= 0.3) {
                return $lcsRatio;
            }
        }

        // 6. Distance de Levenshtein (NOTRE ALGORITHME PRINCIPAL)
        $levenshteinScore = $this->normalizedLevenshtein($queryWord, $targetWord);

        // Appliquer des seuils stricts pour Levenshtein
        if ($levenshteinScore >= 0.7) {
            // Score élevé = bonne correspondance
            return $levenshteinScore;
        } elseif ($levenshteinScore >= 0.5 && $minLength >= 4) {
            // Score moyen = correspondance acceptable pour mots de 4+ lettres
            return $levenshteinScore;
        } elseif ($levenshteinScore >= 0.3 && $minLength >= 6) {
            // Score bas = seulement pour mots longs
            return $levenshteinScore * 0.8; // Réduire encore
        }

        // 7. Vérifier les débuts de mots similaires (préfixes communs)
        if ($this->hasCommonPrefix($queryWord, $targetWord, 3)) {
            $prefixLength = $this->commonPrefixLength($queryWord, $targetWord);
            $ratio = $prefixLength / max($queryLength, $targetLength);
            return min(0.6, 0.4 + ($ratio * 0.3));
        }

        return 0.0;
    }

    private function longestCommonSubstringLength(string $str1, string $str2): int
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

    private function normalizedLevenshtein(string $str1, string $str2): float
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

        // Normalisation simple
        $similarity = 1 - ($distance / $maxLen);

        // Pénalité supplémentaire pour les grandes distances
        if ($distance > 2) {
            $penaltyFactor = min(0.7, 1.0 - ($distance * 0.1));
            $similarity *= $penaltyFactor;
        }

        // Bonus pour les petites distances (1-2 caractères)
        if ($distance <= 2 && $maxLen >= 4) {
            $similarity = min($similarity + 0.1, 1.0);
        }

        return max($similarity, 0.0);
    }

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

    private function commonPrefixLength(string $str1, string $str2): int
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

        $averageScore = $totalScore / count($words1);

        // Bonus pour couverture - MAIS pénalité si moins de 50% des mots trouvés
        $coverage = $matchedWords / count($words1);
        if ($coverage >= 0.5) {
            $coverageBonus = $coverage * 0.15;
            $averageScore = min($averageScore + $coverageBonus, 1.0);
        } else {
            // Pénalité sévère si moins de la moitié des mots trouvés
            $averageScore *= $coverage * 1.5;
        }

        return min(max($averageScore, 0.0), 1.0);
    }

    private function normalizeForComparison(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        $normalized = preg_replace('/[^a-z0-9\s]/i', ' ', $str);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized;
    }
}
