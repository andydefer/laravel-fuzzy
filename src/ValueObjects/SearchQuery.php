<?php

declare(strict_types=1);

namespace Fuzzy\ValueObjects;

use Fuzzy\Services\StringNormalizer;

/**
 * Value Object représentant une requête de recherche
 */
class SearchQuery
{
    public function __construct(
        public string $originalQuery,
        public string $normalizedQuery,
        public array $words,
        public bool $isMultiWord
    ) {}

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

    public function isEmpty(): bool
    {
        return $this->normalizedQuery === '' || $this->normalizedQuery === '0' || $this->words === [];
    }
}
