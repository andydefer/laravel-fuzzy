<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Illuminate\Support\Str;

class StringNormalizer
{
    /**
     * Normalize a string for indexing and comparison
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
     * Split string into words
     */
    public function splitIntoWords(string $str): array
    {
        if (empty($str)) {
            return [];
        }

        $str = str_replace(['_', '-'], ' ', $str);
        $words = preg_split('/\s+/', $str, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, function ($word) {
            return strlen($word) > 0;
        }));
    }

    /**
     * Normalize for search query
     */
    public function normalizeQuery(string $query): string
    {
        $query = $this->normalize($query);

        // Remove very common words if query is long
        $words = $this->splitIntoWords($query);

        if (count($words) > 3) {
            $stopWords = config('fuzzy.stop_words', [
                'the',
                'and',
                'or',
                'a',
                'an',
                'in',
                'on',
                'at',
                'to',
                'for',
                'of',
                'with',
                'by',
                'is',
                'are',
                'was',
                'were',
                'be',
                'been',
                'being',
                'have',
                'has',
                'had',
                'do',
                'does',
                'did',
                'but',
                'if',
                'then',
                'else',
                'when',
                'where',
                'why',
                'how',
                'all',
                'any',
                'both',
                'each',
                'few',
                'more',
                'most',
                'other',
                'some',
                'such',
                'no',
                'nor',
                'not',
                'only',
                'own',
                'same',
                'so',
                'than',
                'too',
                'very',
                'can',
                'will',
                'just',
                'should',
                'now'
            ]);

            $words = array_filter($words, function ($word) use ($stopWords) {
                return !in_array($word, $stopWords);
            });
        }

        return implode(' ', $words);
    }

    /**
     * Extract keywords from string
     */
    public function extractKeywords(string $str, int $maxKeywords = 10): array
    {
        $normalized = $this->normalize($str);
        $words = $this->splitIntoWords($normalized);

        // Filter out stop words and very short words
        $stopWords = config('fuzzy.stop_words', []);
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) >= 3 && !in_array($word, $stopWords);
        });

        // Count frequency
        $frequencies = array_count_values($keywords);
        arsort($frequencies);

        return array_slice(array_keys($frequencies), 0, $maxKeywords);
    }
}
