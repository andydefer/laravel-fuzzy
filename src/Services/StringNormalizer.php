<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Illuminate\Support\Str;

/**
 * Service for normalizing text strings for indexing and search operations.
 */
class StringNormalizer
{
    /**
     * Normalize a string by removing special characters, converting to lowercase,
     * and standardizing whitespace.
     *
     * @param string $str The input string to normalize
     * @return string Normalized string
     */
    public function normalize(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        return (string) Str::of($str)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s_-]/', '')
            ->replaceMatches('/\s+/', ' ')
            ->toString();
    }

    /**
     * Split a string into individual words.
     *
     * @param string $str The string to split
     * @return array<int, string> Array of words
     */
    public function splitIntoWords(string $str): array
    {
        if (empty($str)) {
            return [];
        }

        $str = str_replace(['_', '-'], ' ', $str);
        $words = preg_split('/\s+/', $str, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn($word) => strlen($word) > 0));
    }

    /**
     * Normalize a search query by removing stop words from longer queries.
     *
     * @param string $query The search query to normalize
     * @return string Normalized query without stop words
     */
    public function normalizeQuery(string $query): string
    {
        $query = $this->normalize($query);
        $words = $this->splitIntoWords($query);

        if (count($words) > 3) {
            $stopWords = config('fuzzy.stop_words', []);
            $words = array_filter($words, fn($word) => !in_array($word, $stopWords));
        }

        return implode(' ', $words);
    }

    /**
     * Extract the most relevant keywords from a string.
     *
     * @param string $str The string to analyze
     * @param int $maxKeywords Maximum number of keywords to return
     * @return array<int, string> Extracted keywords sorted by frequency
     */
    public function extractKeywords(string $str, int $maxKeywords = 10): array
    {
        $normalized = $this->normalize($str);
        $words = $this->splitIntoWords($normalized);

        $stopWords = config('fuzzy.stop_words', []);
        $keywords = array_filter(
            $words,
            fn($word) => strlen($word) >= 3 && !in_array($word, $stopWords)
        );

        $frequencies = array_count_values($keywords);
        arsort($frequencies);

        return array_slice(array_keys($frequencies), 0, $maxKeywords);
    }
}
