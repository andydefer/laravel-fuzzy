<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for string normalization services.
 *
 * Defines the contract for normalizing strings for indexing,
 * search operations, and keyword extraction.
 *
 * @package Fuzzy\Contracts
 */
interface StringNormalizerInterface
{
    /**
     * Normalize a string by removing special characters, converting to lowercase,
     * and standardizing whitespace.
     *
     * @param string $input The input string to normalize
     * @return string Normalized string or empty string for invalid input
     */
    public function normalize(string $input): string;

    /**
     * Split a normalized string into individual words.
     *
     * @param string $input The string to split into words
     * @return array<int, string> Array of non-empty words
     */
    public function splitIntoWords(string $input): array;

    /**
     * Normalize a search query by applying full normalization and removing stop words.
     *
     * @param string $query The search query to normalize
     * @return string Normalized query with stop words removed
     */
    public function normalizeQuery(string $query): string;

    /**
     * Normalize a search query with length-based stop word removal.
     *
     * @param string $query The search query to normalize
     * @return string Normalized query with stop words removed when applicable
     */
    public function normalizeQueryWithLengthLimit(string $query): string;

    /**
     * Extract the most relevant keywords from a string based on frequency and relevance.
     *
     * @param string $input The string to analyze for keywords
     * @param int $maxKeywords Maximum number of keywords to return
     * @return array<int, string> Extracted keywords sorted by frequency (descending) then alphabetically
     */
    public function extractKeywords(string $input, int $maxKeywords = 10): array;
}
