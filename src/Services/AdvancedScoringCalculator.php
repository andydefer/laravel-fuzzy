<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Config\AdvancedScoringConfig;

/**
 * Advanced scoring calculator for bonus/penalty application
 *
 * Used exclusively by scoring strategies to calculate final match scores
 * with field weighting, positional bonuses, and query-based adjustments.
 * 
 * This calculator applies multiple transformations to a base similarity score:
 * - Field weighting (prioritize certain fields like 'name' over 'description')
 * - Consecutive character bonuses (reward matches that are contiguous)
 * - Positional bonuses (boost matches that appear early in the text)
 * - Short query penalties (reduce score for very short search terms)
 * - Coverage bonuses (boost multi-word query matches)
 */
class AdvancedScoringCalculator
{
    private AdvancedScoringConfig $config;

    /**
     * Constructor with optional configuration injection.
     *
     * @param AdvancedScoringConfig|null $config Scoring configuration
     */
    public function __construct(?AdvancedScoringConfig $config = null)
    {
        $this->config = $config ?? AdvancedScoringConfig::fromConfig();
    }

    /**
     * Calculate final score with all applicable bonuses and penalties
     *
     * Applies field weighting, consecutive character bonuses, positional bonuses,
     * short query penalties, and coverage bonuses to produce the final score.
     *
     * @param float $baseScore The initial similarity score
     * @param array<string, mixed> $match The match data including:
     *                                    - field: The field name
     *                                    - normalized_words: Array of normalized words
     *                                    - original_value: The original text value
     * @param SearchContextInterface $context The search context with query information
     * @param string|null $queryWord The specific query word being scored (optional)
     * @return float The final score clamped between 0.0 and 1.0
     */
    public function calculateFinalScore(
        float $baseScore,
        array $match,
        SearchContextInterface $context,
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

        return min(max($score, FUZZY_SCORE_NONE), FUZZY_SCORE_IDENTICAL);
    }

    /**
     * Apply field-specific weighting to the score
     *
     * Fields like 'name' have higher weight than 'description'
     *
     * @param float $score The current score
     * @param array<string, mixed> $match The match data containing the field name
     * @return float The score multiplied by the field weight
     */
    private function applyFieldWeighting(float $score, array $match): float
    {
        $fieldWeight = $this->config->getFieldWeight($match['field'] ?? 'default');
        return $score * $fieldWeight;
    }

    /**
     * Apply bonus for consecutive character matches between query and target
     *
     * Rewards matches where characters appear consecutively in the target text.
     * Longer consecutive matches receive progressively higher bonuses.
     *
     * @param float $score The current score
     * @param string $queryWord The query word being matched
     * @param array<string, mixed> $match The match data containing target words
     * @return float The score with consecutive match bonus applied
     */
    private function applyConsecutiveBonus(float $score, string $queryWord, array $match): float
    {
        $maxConsecutiveLength = $this->findMaxConsecutiveLength($queryWord, $match['normalized_words'] ?? []);

        if ($maxConsecutiveLength >= $this->config->getMinConsecutiveLength()) {
            $bonusMultiplier = $this->config->getConsecutiveBonus($maxConsecutiveLength);
            return $score * $bonusMultiplier;
        }

        return $score;
    }

    /**
     * Apply bonus based on the position of the match within the text
     *
     * Matches appearing earlier in the text are considered more relevant.
     * - Positions < threshold: Receive early position bonus
     * - Positions < mid threshold: Receive small bonus
     * - Positions >= mid threshold: No bonus
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
        $earlyPositionBonus = $this->config->getEarlyPositionBonus();

        if ($relativePosition < $this->config->getEarlyPositionThreshold()) {
            return $score * (FUZZY_BASE_FACTOR + $earlyPositionBonus);
        }

        if ($relativePosition < $this->config->getMidPositionThreshold()) {
            return $score * $this->config->getMidPositionBonus();
        }

        return $score;
    }

    /**
     * Apply penalty for short query words
     *
     * Very short queries (less than configured threshold) receive a penalty
     * as they tend to produce less relevant matches.
     *
     * @param float $score The current score
     * @param SearchContextInterface $context The search context containing query words
     * @return float The score with short query penalty applied if applicable
     */
    private function applyShortQueryPenalty(float $score, SearchContextInterface $context): float
    {
        $shortQueryThreshold = $this->config->getShortQueryThreshold();
        $shortQueryPenalty = $this->config->getShortQueryPenalty();

        foreach ($context->getQueryWords() as $word) {
            if (strlen($word) < $shortQueryThreshold) {
                return $score * (FUZZY_BASE_FACTOR - $shortQueryPenalty);
            }
        }

        return $score;
    }

    /**
     * Apply bonus for multi-word query coverage
     *
     * When multiple query words are matched, additional bonuses are applied.
     * Full coverage (all words matched) receives the highest bonus.
     *
     * @param float $score The current score
     * @return float The score with coverage bonus applied
     */
    private function applyCoverageBonus(float $score): float
    {
        // These bonuses are applied in ScoringEngine::calculateMultiWordScore
        // This method is a placeholder for future per-entry coverage logic
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
     * Returns a value between 0.0 (start of text) and 1.0 (end of text)
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
        $availableSpace = max($this->config->getMinAvailableSpace(), $textLength - $wordLength);

        return $position / $availableSpace;
    }

    /**
     * Find the length of the longest common substring between two strings
     *
     * Uses a brute-force approach O(n³) but on small strings this is acceptable.
     *
     * @param string $firstString The first string to compare
     * @param string $secondString The second string to compare
     * @return int The length of the longest common substring
     */
    private function findLongestCommonSubstring(string $firstString, string $secondString): int
    {
        $firstStringLength = strlen($firstString);
        $maxLength = 0;
        $minOffset = $this->config->getMinSubstringEndOffset();

        for ($start = 0; $start < $firstStringLength; ++$start) {
            for ($end = $start + $minOffset; $end <= $firstStringLength; ++$end) {
                $substring = substr($firstString, $start, $end - $start);
                if (str_contains($secondString, $substring)) {
                    $maxLength = max($maxLength, strlen($substring));
                }
            }
        }

        return $maxLength;
    }
}
