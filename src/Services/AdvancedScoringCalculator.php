<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\SearchContext;

/**
 * Advanced scoring calculator for bonus/penalty application
 *
 * Used exclusively by scoring strategies to calculate final match scores
 * with field weighting, positional bonuses, and query-based adjustments.
 */
class AdvancedScoringCalculator
{
    /**
     * Bonus multipliers for consecutive character matches
     *
     * @var array<int, float>
     */
    private const CONSECUTIVE_BONUS = [
        2 => 1.05,
        3 => 1.15,
        4 => 1.30,
        5 => 1.50,
    ];

    /**
     * Calculate final score with all applicable bonuses and penalties
     *
     * Applies field weighting, consecutive character bonuses, positional bonuses,
     * short query penalties, and coverage bonuses to produce the final score.
     *
     * @param float $baseScore The initial similarity score
     * @param array<string, mixed> $match The match data including field and words
     * @param SearchContext $context The search context with query information
     * @param string|null $queryWord The specific query word being scored (optional)
     * @return float The final score clamped between 0.0 and 1.0
     */
    public function calculateFinalScore(
        float $baseScore,
        array $match,
        SearchContext $context,
        ?string $queryWord = null
    ): float {
        $score = $baseScore;

        $score = $this->applyFieldWeighting($score, $match);

        if ($queryWord !== null) {
            $score = $this->applyConsecutiveBonus($score, $queryWord, $match);
        }

        $score = $this->applyPositionBonus($score, $match);
        $score = $this->applyShortQueryPenalty($score, $context);

        if ($context->hasMultipleWords()) {
            $score = $this->applyCoverageBonus($score);
        }

        return min(max($score, 0.0), 1.0);
    }

    /**
     * Apply field-specific weighting to the score
     *
     * @param float $score The current score
     * @param array<string, mixed> $match The match data containing the field name
     * @return float The score multiplied by the field weight
     */
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

    /**
     * Apply bonus for consecutive character matches between query and target
     *
     * @param float $score The current score
     * @param string $queryWord The query word being matched
     * @param array<string, mixed> $match The match data containing target words
     * @return float The score with consecutive match bonus applied
     */
    private function applyConsecutiveBonus(float $score, string $queryWord, array $match): float
    {
        $maxConsecutiveLength = $this->findMaxConsecutiveLength($queryWord, $match['normalized_words']);

        if ($maxConsecutiveLength >= 2) {
            $bonusMultiplier = self::CONSECUTIVE_BONUS[min($maxConsecutiveLength, 5)] ?? 1.0;
            return $score * $bonusMultiplier;
        }

        return $score;
    }

    /**
     * Apply bonus based on the position of the match within the text
     *
     * @param float $score The current score
     * @param array<string, mixed> $match The match data containing the original text
     * @return float The score with positional bonus applied
     */
    private function applyPositionBonus(float $score, array $match): float
    {
        $normalizedText = strtolower((string) ($match['original_value'] ?? ''));
        $words = $match['normalized_words'] ?? [];

        if (empty($words)) {
            return $score;
        }

        $firstWord = (string) reset($words);
        $position = strpos($normalizedText, $firstWord);

        if ($position === false) {
            return $score;
        }

        $relativePosition = $this->calculateRelativePosition($normalizedText, $firstWord, $position);

        if ($relativePosition < 0.2) {
            $earlyPositionBonus = config('fuzzy.scoring.bonuses.early_position', 0.2);
            return $score * (1 + $earlyPositionBonus);
        }

        if ($relativePosition < 0.4) {
            return $score * 1.1;
        }

        return $score;
    }

    /**
     * Apply penalty for short query words
     *
     * @param float $score The current score
     * @param SearchContext $context The search context containing query words
     * @return float The score with short query penalty applied if applicable
     */
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

    /**
     * Apply bonus for multi-word query coverage
     *
     * @param float $score The current score
     * @return float The score with coverage bonus applied
     */
    private function applyCoverageBonus(float $score): float
    {
        // Implementation specific logic can be added here
        return $score;
    }

    /**
     * Find the maximum consecutive substring length between query and target words
     *
     * @param string $queryWord The query word to search for
     * @param array<int, string|mixed> $targetWords The target words to search in
     * @return int The maximum consecutive substring length found
     */
    private function findMaxConsecutiveLength(string $queryWord, array $targetWords): int
    {
        $maxConsecutiveLength = 0;

        foreach ($targetWords as $targetWord) {
            $consecutiveLength = $this->findLongestCommonSubstring($queryWord, (string) $targetWord);
            $maxConsecutiveLength = max($maxConsecutiveLength, $consecutiveLength);
        }

        return $maxConsecutiveLength;
    }

    /**
     * Calculate the relative position of a word within text
     *
     * @param string $text The full normalized text
     * @param string $word The word to find position for
     * @param int $position The character position of the word
     * @return float The relative position (0.0 = start, 1.0 = end)
     */
    private function calculateRelativePosition(string $text, string $word, int $position): float
    {
        $textLength = strlen($text);
        $wordLength = strlen($word);
        $availableSpace = max(1, $textLength - $wordLength);

        return $position / $availableSpace;
    }

    /**
     * Find the length of the longest common substring between two strings
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return int The length of the longest common substring
     */
    private function findLongestCommonSubstring(string $firstString, string $secondString): int
    {
        $firstStringLength = strlen($firstString);
        $maxLength = 0;

        for ($start = 0; $start < $firstStringLength; ++$start) {
            for ($end = $start + 2; $end <= $firstStringLength; ++$end) {
                $substring = substr($firstString, $start, $end - $start);
                if (str_contains($secondString, $substring)) {
                    $maxLength = max($maxLength, strlen($substring));
                }
            }
        }

        return $maxLength;
    }
}
