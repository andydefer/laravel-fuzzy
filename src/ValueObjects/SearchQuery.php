<?php

declare(strict_types=1);

namespace Fuzzy\ValueObjects;

use Fuzzy\Services\StringNormalizer;

/**
 * Search query value object
 *
 * Represents a normalized search query with its components preprocessed
 * for fuzzy search operations.
 */
class SearchQuery
{
    /**
     * @param string $originalQuery The raw search query as entered by the user
     * @param string $normalizedQuery The query after normalization (lowercase, trimmed, etc.)
     * @param array<int, string> $words Individual words extracted from the normalized query
     * @param bool $isMultiWord Whether the query contains multiple words
     */
    public function __construct(
        public readonly string $originalQuery,
        public readonly string $normalizedQuery,
        public readonly array $words,
        public readonly bool $isMultiWord
    ) {}

    /**
     * Create a SearchQuery from a raw user input
     *
     * @param string $query The raw search query
     * @param StringNormalizer $normalizer Service to normalize and split the query
     * @return self New SearchQuery instance
     */
    public static function create(string $query, StringNormalizer $normalizer): self
    {
        $normalizedQuery = $normalizer->normalizeQuery($query);
        $words = $normalizer->splitIntoWords($normalizedQuery);

        return new self(
            originalQuery: $query,
            normalizedQuery: $normalizedQuery,
            words: $words,
            isMultiWord: count($words) > 1
        );
    }

    /**
     * Check if the search query is effectively empty
     *
     * Determines if the query contains no searchable content after normalization.
     *
     * @return bool True if the query is empty or contains only non-searchable content
     */
    public function isEmpty(): bool
    {
        return $this->normalizedQuery === '' || $this->normalizedQuery === '0' || $this->words === [];
    }
}
