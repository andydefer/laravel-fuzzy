<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\SearchContext;

class AdvancedScoringCalculator
{
    private const CONSECUTIVE_BONUS = [
        2 => 1.05,
        3 => 1.15,
        4 => 1.30,
        5 => 1.50,
    ];

    /**
     * Calcule le score final avec tous les bonus/penalties
     */
    public function calculateFinalScore(
        float $baseScore,
        array $match,
        SearchContext $context,
        ?string $queryWord = null
    ): float {
        $score = $baseScore;

        // 1. Pondération par champ
        $score = $this->applyFieldWeighting($score, $match);

        // 2. Bonus caractères consécutifs
        if ($queryWord) {
            $score = $this->applyConsecutiveBonus($score, $queryWord, $match);
        }

        // 3. Bonus position
        $score = $this->applyPositionBonus($score, $match);

        // 4. Pénalités requêtes courtes
        $score = $this->applyShortQueryPenalty($score, $context);

        // 5. Bonus couverture (pour multi-mots)
        if ($context->hasMultipleWords()) {
            $score = $this->applyCoverageBonus($score, $context, $match);
        }

        return min(max($score, 0.0), 1.0);
    }

    /**
     * Score spécialisé pour les requêtes multi-mots
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContext $context): float
    {
        if (empty($indexEntries) || !$context->hasMultipleWords()) {
            return 0.0;
        }

        $queryWords = $context->getQueryWords();
        $wordScores = [];

        foreach ($queryWords as $queryWord) {
            $bestWordScore = 0.0;

            foreach ($indexEntries as $indexEntry) {
                foreach ($indexEntry['normalized_words'] as $targetWord) {
                    $similarity = $context->similarityCalculator->calculateWordSimilarity(
                        $queryWord,
                        (string) $targetWord
                    );

                    if ($similarity >= $context->options->threshold) {
                        if ($this->isCrossWordMatch($queryWord, (string) $targetWord)) {
                            $similarity *= 0.7; // -30% pénalité
                        }

                        $bestWordScore = max($bestWordScore, $similarity);
                    }
                }
            }

            if ($bestWordScore > 0) {
                $wordScores[] = $bestWordScore;
            }
        }

        if (empty($wordScores)) {
            return 0.0;
        }

        // Score moyen avec bonus de couverture
        $averageScore = array_sum($wordScores) / count($wordScores);
        $coverage = count($wordScores) / count($queryWords);
        $coverageBonus = $this->calculateCoverageBonus($coverage);

        $finalScore = $averageScore * (1 + $coverage) + $coverageBonus;

        // Appliquer la pondération du champ
        $firstEntry = reset($indexEntries);
        $finalScore = $this->applyFieldWeighting($finalScore, $firstEntry);

        return min(max($finalScore, 0.0), 1.0);
    }

    // ... [garder toutes les méthodes privées existantes] ...
    private function applyFieldWeighting(float $score, array $match): float
    {
        $fieldWeights = config('fuzzy.scoring.field_weights', [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ]);

        $fieldWeight = $fieldWeights[$match['field']] ?? $fieldWeights['default'];
        return $score * $fieldWeight;
    }

    private function applyConsecutiveBonus(float $score, string $queryWord, array $match): float
    {
        $maxConsecutive = 0;

        foreach ($match['normalized_words'] as $targetWord) {
            $consecutive = $this->findLongestCommonSubstring($queryWord, (string) $targetWord);
            $maxConsecutive = max($maxConsecutive, $consecutive);
        }

        if ($maxConsecutive >= 2) {
            $bonus = self::CONSECUTIVE_BONUS[min($maxConsecutive, 5)] ?? 1.0;
            return $score * $bonus;
        }

        return $score;
    }

    private function applyPositionBonus(float $score, array $match): float
    {
        $fullText = strtolower($match['original_value'] ?? '');
        $words = $match['normalized_words'] ?? [];

        if (empty($words)) {
            return $score;
        }

        $firstWord = reset($words);
        $position = strpos($fullText, (string) $firstWord);

        if ($position === false) {
            return $score;
        }

        $textLength = strlen($fullText);
        $wordLength = strlen((string) $firstWord);
        $relativePosition = $position / max(1, $textLength - $wordLength);

        if ($relativePosition < 0.2) return $score * 1.2;
        if ($relativePosition < 0.4) return $score * 1.1;

        return $score;
    }

    private function applyShortQueryPenalty(float $score, SearchContext $context): float
    {
        foreach ($context->getQueryWords() as $word) {
            if (strlen($word) < 4) {
                $penalty = config('fuzzy.scoring.penalties.short_query', 0.4);
                return $score * (1 - $penalty);
            }
        }

        return $score;
    }

    private function applyCoverageBonus(float $score, SearchContext $context, array $match): float
    {
        // Cette méthode peut être appelée si besoin spécifique
        return $score;
    }

    private function calculateCoverageBonus(float $coverage): float
    {
        if ($coverage === 1.0) return 0.3;
        if ($coverage >= 0.75) return 0.15;
        return 0.0;
    }

    private function isCrossWordMatch(string $queryWord, string $targetWord): bool
    {
        if (abs(strlen($queryWord) - strlen($targetWord)) > 3) return true;
        if (str_contains($targetWord, $queryWord) || str_contains($queryWord, $targetWord)) {
            return false;
        }

        $lcsLength = $this->findLongestCommonSubstring($queryWord, $targetWord);
        $minLength = min(strlen($queryWord), strlen($targetWord));

        return ($lcsLength / $minLength) < 0.5;
    }

    private function findLongestCommonSubstring(string $str1, string $str2): int
    {
        $len1 = strlen($str1);
        $maxLength = 0;

        for ($i = 0; $i < $len1; $i++) {
            for ($j = $i + 2; $j <= $len1; $j++) {
                $substring = substr($str1, $i, $j - $i);
                if (str_contains($str2, $substring)) {
                    $maxLength = max($maxLength, strlen($substring));
                }
            }
        }

        return $maxLength;
    }
}
