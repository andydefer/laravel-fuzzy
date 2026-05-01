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

        // Si la requête normalisée contient des mots, mais que tous sont des stop words,
        // normalizeQuery peut retourner une chaîne non vide avec des stop words
        // Cela dépend de la logique de normalizeQuery
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
        // Une requête est vide si :
        // 1. La chaîne normalisée est vide
        // 2. La chaîne normalisée est "0"
        // 3. Il n'y a aucun mot après normalisation
        // 4. Tous les mots sont des stop words (détecté par le fait que normalizedQuery peut être non vide
        //    mais words est vide après filtrage - selon l'implémentation de normalizeQuery)
        return $this->normalizedQuery === ''
            || $this->normalizedQuery === '0'
            || $this->words === [];
    }
}
