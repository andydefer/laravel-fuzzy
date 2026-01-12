<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Illuminate\Support\Str;

/**
 * Service for normalizing text strings for indexing and search operations.
 *
 * Provides consistent text processing across the fuzzy search system,
 * including normalization, tokenization, and keyword extraction.
 */
class StringNormalizer
{
    /**
     * Normalize a string by removing special characters, converting to lowercase,
     * and standardizing whitespace.
     *
     * @param string $input The input string to normalize
     * @return string Normalized string or empty string for invalid input
     */
    public function normalize(string $input): string
    {
        if ($input === '' || $input === '0') {
            return '';
        }

        return (string) Str::of($input)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s_-]/', '')
            ->replaceMatches('/\s+/', ' ')
            ->toString();
    }

    /**
     * Split a normalized string into individual words.
     *
     * @param string $input The string to split into words
     * @return array<int, string> Array of non-empty words
     */
    public function splitIntoWords(string $input): array
    {
        if ($input === '' || $input === '0') {
            return [];
        }

        $normalized = str_replace(['_', '-'], ' ', $input);
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn($word): bool => (string) $word !== ''));
    }

    /**
     * Normalize a search query by applying full normalization and removing stop words.
     * Stop words are only removed from queries longer than 3 words.
     *
     * @param string $query The search query to normalize
     * @return string Normalized query with stop words removed when applicable
     */
    public function normalizeQuery(string $query): string
    {
        $normalizedQuery = $this->normalize($query);
        $words = $this->splitIntoWords($normalizedQuery);

        if (count($words) > 3) {
            $stopWords = config('fuzzy.stop_words', []);
            $filteredWords = array_filter($words, fn($word): bool => !in_array($word, $stopWords, true));
            $words = array_values($filteredWords);
        }

        return implode(' ', $words);
    }

    /**
     * Extract the most relevant keywords from a string based on frequency and relevance.
     *
     * @param string $input The string to analyze for keywords
     * @param int $maxKeywords Maximum number of keywords to return (default: 10)
     * @return array<int, string> Extracted keywords sorted by frequency (descending) then alphabetically
     */
    public function extractKeywords(string $input, int $maxKeywords = 10): array
    {
        $normalizedText = $this->normalize($input);
        $words = $this->splitIntoWords($normalizedText);

        $stopWords = config('fuzzy.stop_words', []);
        $keywords = array_filter(
            $words,
            fn(string $word): bool => strlen($word) >= 3 && !in_array($word, $stopWords, true)
        );

        $wordFrequencies = array_count_values($keywords);

        uksort($wordFrequencies, function (string $wordA, string $wordB) use ($wordFrequencies): int {
            $frequencyComparison = $wordFrequencies[$wordB] <=> $wordFrequencies[$wordA];

            return $frequencyComparison !== 0
                ? $frequencyComparison
                : strcmp($wordA, $wordB);
        });

        return array_slice(array_keys($wordFrequencies), 0, $maxKeywords);
    }
}
