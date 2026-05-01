<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\ContextualNormalizerInterface;
use Illuminate\Support\Str;

/**
 * Service for normalizing text strings with context-aware stop word removal.
 *
 * Provides consistent text processing across the fuzzy search system,
 * including normalization, tokenization, keyword extraction, and
 * context-aware stop word preservation for protected fields.
 *
 * @package Fuzzy\Services
 */
class StringNormalizer implements ContextualNormalizerInterface
{
    private const STOP_WORD_REMOVAL_THRESHOLD = 3;
    private const MIN_KEYWORD_LENGTH = 3;
    private const DEFAULT_MAX_KEYWORDS = 10;
    private const REGEX_REMOVE_SPECIAL_CHARS = '/[^a-z0-9\s_-]/';
    private const REGEX_COLLAPSE_SPACES = '/\s+/';
    private const REGEX_WORD_SPLITTER = '/\s+/';
    private const WORD_SEPARATORS = ['_', '-'];
    private const EMPTY_STRING = '';
    private const ZERO_STRING = '0';

    /**
     * Internal stop words loaded from language files.
     *
     * @var array<int, string>
     */
    private array $stopWords = [];

    /**
     * Protected fields that should preserve stop words.
     * These are provided by the model being indexed.
     *
     * @var array<int, string>
     */
    private array $protectedFields = [];

    /**
     * Current field being processed (for contextual normalization).
     *
     * @var string|null
     */
    private ?string $currentField = null;

    /**
     * Constructor - loads internal stop words from language files.
     *
     * Stop words are hardcoded in the package and cannot be overridden
     * by users to ensure consistent search behavior.
     */
    public function __construct()
    {
        $this->loadStopWords();
    }

    /**
     * Load stop words from internal language files based on application locale.
     *
     * @return void
     */
    private function loadStopWords(): void
    {
        $locale = $this->getLocale();

        if (str_starts_with($locale, 'fr')) {
            $this->stopWords = require __DIR__ . '/../StopWords/fr.php';
        } elseif (str_starts_with($locale, 'en')) {
            $this->stopWords = require __DIR__ . '/../StopWords/en.php';
        } else {
            // Fallback: merge both French and English stop words
            $french = require __DIR__ . '/../StopWords/fr.php';
            $english = require __DIR__ . '/../StopWords/en.php';
            $this->stopWords = array_merge($french, $english);
        }
    }

    /**
     * Get current locale from Laravel application.
     *
     * @return string The current application locale
     */
    private function getLocale(): string
    {
        if (function_exists('app') && method_exists(app(), 'getLocale')) {
            return app()->getLocale();
        }
        return $_ENV['APP_LOCALE'] ?? 'en';
    }

    /**
     * Set the protected fields that should preserve stop words.
     *
     * @param array<int, string> $protectedFields List of field names
     * @return self Returns self for method chaining
     */
    public function setProtectedFields(array $protectedFields): self
    {
        $this->protectedFields = $protectedFields;
        return $this;
    }

    /**
     * Set the current field being processed.
     *
     * @param string|null $field The field name or null for default behavior
     * @return self Returns self for method chaining
     */
    public function setCurrentField(?string $field): self
    {
        $this->currentField = $field;
        return $this;
    }

    /**
     * Get the current field being processed.
     *
     * @return string|null The current field name or null if not set
     */
    public function getCurrentField(): ?string
    {
        return $this->currentField;
    }

    /**
     * Get the protected fields array.
     *
     * @return array<int, string>
     */
    public function getProtectedFields(): array
    {
        return $this->protectedFields;
    }

    /**
     * Check if stop words should be removed for the current context.
     *
     * Stop words are preserved for:
     * - Protected fields (names, emails, etc.)
     * - When no field is set (default behavior removes stop words)
     *
     * @return bool True if stop words should be removed, false if preserved
     */
    private function shouldRemoveStopWords(): bool
    {
        // If no field is set, default to removing stop words (search queries)
        if ($this->currentField === null) {
            return true;
        }

        // Don't remove stop words for protected fields
        return !in_array($this->currentField, $this->protectedFields, true);
    }

    /**
     * Check if a field should preserve stop words.
     *
     * @param string $field The field name to check
     * @return bool True if stop words should be preserved, false otherwise
     */
    public function shouldPreserveStopWords(string $field): bool
    {
        return in_array($field, $this->protectedFields, true);
    }

    /**
     * Normalize a string by removing special characters, converting to lowercase,
     * and standardizing whitespace.
     *
     * @param string $input The input string to normalize
     * @return string Normalized string or empty string for invalid input
     */
    public function normalize(string $input): string
    {
        if ($input === self::EMPTY_STRING || $input === self::ZERO_STRING) {
            return self::EMPTY_STRING;
        }

        return (string) Str::of($input)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches(self::REGEX_REMOVE_SPECIAL_CHARS, self::EMPTY_STRING)
            ->replaceMatches(self::REGEX_COLLAPSE_SPACES, ' ')
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
        if ($input === self::EMPTY_STRING || $input === self::ZERO_STRING) {
            return [];
        }

        $normalized = str_replace(self::WORD_SEPARATORS, ' ', $input);
        $words = preg_split(self::REGEX_WORD_SPLITTER, $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn($word): bool => (string) $word !== self::EMPTY_STRING));
    }

    /**
     * Normalize a value specifically for a field during indexing.
     *
     * This method respects the field's protected status and applies
     * appropriate normalization (with or without stop word removal).
     *
     * @param string $value The raw value to normalize
     * @param string $field The field name being indexed
     * @return string Normalized value appropriate for the field
     */
    public function normalizeForField(string $value, string $field): string
    {
        $this->setCurrentField($field);
        $result = $this->normalizeQuery($value);
        $this->setCurrentField(null);

        return $result;
    }

    /**
     * Normalize a search query by applying full normalization and removing stop words.
     *
     * @param string $query The search query to normalize
     * @return string Normalized query with stop words removed (or preserved for protected fields)
     */
    public function normalizeQuery(string $query): string
    {
        $normalizedQuery = $this->normalize($query);
        $words = $this->splitIntoWords($normalizedQuery);

        // Only remove stop words if context allows it
        if ($this->shouldRemoveStopWords()) {
            $filteredWords = array_filter(
                $words,
                fn($word): bool => !in_array($word, $this->stopWords, true)
            );
        } else {
            // Keep all words for protected fields (names, emails, etc.)
            $filteredWords = $words;
        }

        return implode(' ', array_values($filteredWords));
    }

    /**
     * Normalize a search query with length-based stop word removal.
     *
     * For short queries (≤ STOP_WORD_REMOVAL_THRESHOLD words), stop words are preserved
     * even for non-protected fields to maintain search context.
     *
     * @param string $query The search query to normalize
     * @return string Normalized query with stop words removed when applicable
     */
    public function normalizeQueryWithLengthLimit(string $query): string
    {
        $normalizedQuery = $this->normalize($query);
        $words = $this->splitIntoWords($normalizedQuery);

        // For short queries, preserve all words regardless of protection status
        if (count($words) <= self::STOP_WORD_REMOVAL_THRESHOLD) {
            return implode(' ', $words);
        }

        // For longer queries, apply standard normalization
        if ($this->shouldRemoveStopWords()) {
            $filteredWords = array_filter(
                $words,
                fn($word): bool => !in_array($word, $this->stopWords, true)
            );
            $words = array_values($filteredWords);
        }

        return implode(' ', $words);
    }

    /**
     * Extract the most relevant keywords from a string based on frequency and relevance.
     *
     * @param string $input The string to analyze for keywords
     * @param int $maxKeywords Maximum number of keywords to return
     * @return array<int, string> Extracted keywords sorted by frequency (descending) then alphabetically
     */
    public function extractKeywords(string $input, int $maxKeywords = self::DEFAULT_MAX_KEYWORDS): array
    {
        $normalizedText = $this->normalize($input);
        $words = $this->splitIntoWords($normalizedText);

        $keywords = array_filter(
            $words,
            fn(string $word): bool => strlen($word) >= self::MIN_KEYWORD_LENGTH
        );

        // Only filter stop words if context allows
        if ($this->shouldRemoveStopWords()) {
            $keywords = array_filter(
                $keywords,
                fn(string $word): bool => !in_array($word, $this->stopWords, true)
            );
        }

        $wordFrequencies = array_count_values($keywords);

        uksort($wordFrequencies, function (string $wordA, string $wordB) use ($wordFrequencies): int {
            $frequencyComparison = $wordFrequencies[$wordB] <=> $wordFrequencies[$wordA];
            return $frequencyComparison !== 0 ? $frequencyComparison : strcmp($wordA, $wordB);
        });

        return array_slice(array_keys($wordFrequencies), 0, $maxKeywords);
    }

    /**
     * Get the internal stop words array (useful for testing).
     *
     * @return array<int, string>
     */
    public function getStopWords(): array
    {
        return $this->stopWords;
    }
}
