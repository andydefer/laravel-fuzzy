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
            // Score encore plus élevé
            if ($ratio >= 0.8) {
                return 0.98; // Presque parfait
            }
            return min(0.95, 0.8 + ($ratio * 0.2)); // Entre 0.8 et 0.95
        }

        // 3. La cible est contenue dans la requête (CAS CRITIQUE : "mannte" contient "mante")
        if (str_contains($queryWord, $targetWord)) {
            $ratio = strlen($targetWord) / strlen($queryWord);
            // Bonus IMPORTANT pour ce cas
            if ($ratio >= 0.8) {
                return 0.95; // "mante" dans "mannte" : 5/6 = 0.83 → 0.95
            }
            return min(0.9, 0.7 + ($ratio * 0.25)); // Entre 0.7 et 0.9
        }

        // 4. Vérifier si la cible est PRESQUE contenue dans la requête (1-2 lettres de différence)
        $lengthDiff = abs(strlen($queryWord) - strlen($targetWord));
        if ($lengthDiff <= 2 && min(strlen($queryWord), strlen($targetWord)) >= 4) {
            // Chercher la cible dans la requête avec tolérance d'une lettre
            if ($this->isAlmostContained($targetWord, $queryWord, 1)) {
                $ratio = strlen($targetWord) / strlen($queryWord);
                return min(0.9, 0.75 + ($ratio * 0.2)); // Bonus important
            }

            // Chercher la requête dans la cible avec tolérance d'une lettre
            if ($this->isAlmostContained($queryWord, $targetWord, 1)) {
                $ratio = strlen($queryWord) / strlen($targetWord);
                return min(0.9, 0.75 + ($ratio * 0.2)); // Bonus important
            }
        }

        // 5. Calculer la plus longue sous-chaîne commune
        $lcsLength = $this->longestCommonSubstringLength($queryWord, $targetWord);

        // CAS SPÉCIAL : La LCS est presque toute la requête ou presque toute la cible
        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);

        if ($lcsLength >= $queryLength - 1 && $queryLength >= 4) {
            // LCS presque toute la requête (ex: "mannte" vs "mante" : LCS="mant")
            $ratio = $lcsLength / max($queryLength, $targetLength);
            return min(0.85, 0.7 + ($ratio * 0.2));
        }

        if ($lcsLength >= $targetLength - 1 && $targetLength >= 4) {
            // LCS presque toute la cible
            $ratio = $lcsLength / max($queryLength, $targetLength);
            return min(0.85, 0.7 + ($ratio * 0.2));
        }

        if ($lcsLength >= 3) {
            $maxLength = max($queryLength, $targetLength);
            $lcsRatio = $lcsLength / $maxLength;

            // Bonus pour les sous-chaînes relativement longues
            if ($lcsRatio >= 0.7) {
                return min(0.8, $lcsRatio * 1.1);
            } elseif ($lcsRatio >= 0.5) {
                return min(0.7, $lcsRatio * 1.05);
            } elseif ($lcsRatio >= 0.3) {
                return $lcsRatio;
            }
        }

        // 6. Distance de Levenshtein pour les mots courts
        if ($queryLength <= 6 || $targetLength <= 6) {
            $levenshteinScore = $this->normalizedLevenshtein($queryWord, $targetWord);
            if ($levenshteinScore >= 0.6) {
                return $levenshteinScore;
            }
        }

        // 7. Similarité Jaro-Winkler pour les fautes de frappe
        $jaroWinklerScore = $this->jaroWinklerSimilarity($queryWord, $targetWord);

        if ($jaroWinklerScore >= 0.65) {
            return $jaroWinklerScore;
        }

        return 0.0;
    }

    /**
     * Vérifie si une chaîne est presque contenue dans une autre avec une tolérance d'erreur
     */
    private function isAlmostContained(string $needle, string $haystack, int $maxErrors = 1): bool
    {
        $needleLen = strlen($needle);
        $haystackLen = strlen($haystack);

        if ($needleLen > $haystackLen + $maxErrors) {
            return false;
        }

        // Chercher avec tolérance d'erreur
        for ($i = 0; $i <= $haystackLen - $needleLen + $maxErrors; $i++) {
            $errors = 0;

            for ($j = 0; $j < $needleLen; $j++) {
                $haystackPos = $i + $j;

                if ($haystackPos >= $haystackLen || $haystack[$haystackPos] !== $needle[$j]) {
                    $errors++;
                    if ($errors > $maxErrors) {
                        break 2; // Trop d'erreurs, passer à la position suivante
                    }
                }
            }

            if ($errors <= $maxErrors) {
                return true;
            }
        }

        return false;
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
        $maxLen = max($len1, $len2);

        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);

        // Normalisation avec bonus pour petites distances
        $similarity = 1 - ($distance / $maxLen);

        if ($distance <= 2 && $maxLen >= 4) {
            $similarity = min($similarity + 0.1, 1.0);
        }

        return max($similarity, 0.0);
    }

    private function jaroWinklerSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $matchDistance = (int) max(floor(max($len1, $len2) / 2) - 1, 0);

        $str1Matches = array_fill(0, $len1, false);
        $str2Matches = array_fill(0, $len2, false);
        $matches = 0;
        $transpositions = 0;

        // Trouver les correspondances
        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end = min($i + $matchDistance + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if (!$str2Matches[$j] && $str1[$i] === $str2[$j]) {
                    $str1Matches[$i] = true;
                    $str2Matches[$j] = true;
                    $matches++;
                    break;
                }
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        // Compter les transpositions
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if ($str1Matches[$i]) {
                while (!$str2Matches[$k]) {
                    $k++;
                }
                if ($str1[$i] !== $str2[$k]) {
                    $transpositions++;
                }
                $k++;
            }
        }

        $transpositions = $transpositions / 2;

        // Calculer Jaro
        $jaro = (($matches / $len1) + ($matches / $len2) + (($matches - $transpositions) / $matches)) / 3.0;

        // Ajouter Winkler bonus pour préfixe commun
        $prefixLength = 0;
        $maxPrefixLength = min($len1, $len2, 4);

        for ($i = 0; $i < $maxPrefixLength; $i++) {
            if ($str1[$i] === $str2[$i]) {
                $prefixLength++;
            } else {
                break;
            }
        }

        $winklerBonus = $prefixLength * 0.1 * (1.0 - $jaro);
        $jaroWinkler = $jaro + $winklerBonus;

        return min($jaroWinkler, 1.0);
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

        // Bonus pour couverture
        $coverageBonus = ($matchedWords / count($words1)) * 0.15;

        return min($averageScore + $coverageBonus, 1.0);
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
