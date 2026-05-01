<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Contracts\StringNormalizerInterface;
use Fuzzy\Config\WordSimilarityComparatorConfig;
use Fuzzy\Services\Algorithms\WordSimilarity\WordMatchScorer;
use Fuzzy\Services\Algorithms\WordSimilarity\LetterDistanceCalculator;

/**
 * Advanced lexical similarity comparator for strings.
 *
 * Calculates a weighted lexical distance between two strings,
 * considering orthographic and phonetic similarities.
 * Lower scores indicate higher similarity, with 0 representing perfect identity.
 *
 * This comparator uses multiple strategies:
 * - Containment detection (one word inside another)
 * - Phonetic similarity (using Soundex algorithm)
 * - Letter-by-letter matching with position penalties
 * - Context-based phonetic reduction
 *
 * @package Fuzzy\Services\Algorithms
 */
class WordSimilarityComparator
{
    private StringNormalizerInterface $normalizer;
    private WordSimilarityComparatorConfig $config;
    private WordMatchScorer $wordMatchScorer;
    private LetterDistanceCalculator $letterDistanceCalculator;

    /**
     * Constructor for WordSimilarityComparator.
     *
     * @param StringNormalizerInterface $normalizer String normalizer service
     * @param WordSimilarityComparatorConfig|null $config Configuration for algorithm parameters
     */
    public function __construct(
        StringNormalizerInterface $normalizer,
        ?WordSimilarityComparatorConfig $config = null
    ) {
        $this->normalizer = $normalizer;
        $this->config = $config ?? WordSimilarityComparatorConfig::createDefault();
        $this->wordMatchScorer = new WordMatchScorer($this->config);
        $this->letterDistanceCalculator = new LetterDistanceCalculator($this->config);
    }

    /**
     * Compare two strings and return a similarity score.
     *
     * Lower scores indicate higher similarity. Returns 0 for identical strings.
     *
     * @param string $query First input string (the search query)
     * @param string $text Second input string (the target text)
     * @param float $sigma Weight factor for word distance influence (default: 1.0)
     * @return float Similarity score (lower = more similar, capped at maxScoreCap)
     */
    public function compare(string $query, string $text, float $sigma = null): float
    {
        $sigma = $sigma ?? $this->config->getSigma();

        $normalizedQuery = $this->normalizer->normalize($query);
        $normalizedText = $this->normalizer->normalize($text);

        $queryWords = $this->normalizer->splitIntoWords($normalizedQuery);
        $textWords = $this->normalizer->splitIntoWords($normalizedText);

        // Perfect match detection
        if ($normalizedQuery === $normalizedText) {
            return FUZZY_DISTANCE_IDENTICAL;
        }

        // Empty query handling
        if (empty($queryWords)) {
            return $this->config->getMaxScoreCap();
        }

        // Empty text handling - penalty based on query word count
        if (empty($textWords)) {
            $emptyTextPenalty = $this->calculateEmptyTextPenalty($queryWords);
            return min($this->config->getMaxScoreCap(), $emptyTextPenalty);
        }

        $filteredQueryWords = $this->filterShortWords($queryWords);

        if (empty($filteredQueryWords)) {
            return $this->config->getMaxScoreCap();
        }

        $globalScore = $this->wordMatchScorer->calculateQueryBasedScore($filteredQueryWords, $textWords, $sigma);

        return min($this->applyMinimalPenalty($globalScore), $this->config->getMaxScoreCap());
    }

    /**
     * Calculate penalty for empty target text.
     *
     * @param array<int, string> $queryWords Words from the query
     * @return float Calculated penalty score
     */
    private function calculateEmptyTextPenalty(array $queryWords): float
    {
        $wordCount = count($queryWords);
        $penaltyPerChar = $this->config->getWordPenaltyPerChar();
        $lengthMultiplier = $this->config->getLengthPenaltyMultiplier();
        $emptyTextPenaltyFactor = $this->config->getEmptyTextPenaltyFactor();

        return $wordCount * $penaltyPerChar * $lengthMultiplier * $emptyTextPenaltyFactor;
    }

    /**
     * Filter out words that are too short to be meaningful.
     *
     * @param array<int, string> $words Words to filter
     * @return array<int, string> Filtered words with minimum length
     */
    private function filterShortWords(array $words): array
    {
        $minimumLength = $this->config->getShortWordThreshold();

        return array_filter($words, function (string $word) use ($minimumLength): bool {
            return strlen($word) >= $minimumLength;
        });
    }

    /**
     * Apply minimal penalty to ensure no zero score for imperfect matches.
     *
     * @param float $score Raw score
     * @return float Score with minimal penalty applied
     */
    private function applyMinimalPenalty(float $score): float
    {
        return max($score, $this->config->getMinimalPenalty());
    }
}
