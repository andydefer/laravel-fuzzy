<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for scoring engine that calculates relevance scores for search results.
 *
 * Defines the contract for scoring implementations that can evaluate both
 * single index entries and multi-word query results across multiple entries.
 *
 * The scoring engine uses a chain of responsibility pattern with multiple
 * scoring strategies (exact match, word match, fuzzy match, multi-word)
 * to calculate the most relevant score for each search result.
 *
 * @package Fuzzy\Contracts
 */
interface ScoringEngineInterface
{
    /**
     * Calculate optimal score for a single index entry.
     *
     * Iterates through available strategies to find the best matching score.
     * Falls back to basic similarity calculation if no strategy supports the entry.
     * The strategies are evaluated in priority order (highest priority first).
     *
     * @param SearchContextInterface $context Search context containing query and search options
     * @param array<string, mixed> $indexEntry Index entry data containing:
     *                                         - field: The name of the indexed field (e.g., 'name', 'email')
     *                                         - original_value: Original text before normalization
     *                                         - normalized_words: Array of normalized word tokens
     *                                         - weight: Field weight multiplier (default: 1.0)
     * @return float Normalized score between 0.0 (no match) and 1.0 (perfect match)
     */
    public function calculateScore(SearchContextInterface $context, array $indexEntry): float;

    /**
     * Calculate score for a multi-word query across multiple index entries.
     *
     * Evaluates how well a set of index entries matches a multi-word query
     * by considering individual word matches and applying coverage bonuses.
     *
     * Calculation process:
     * 1. For each query word, find the best matching score across all entries
     * 2. Calculate the average score of all matched words
     * 3. Apply coverage bonus based on the proportion of query words matched
     * 4. Apply field weighting to the final aggregated score
     *
     * @param array<int, array<string, mixed>> $indexEntries Array of matching index entries,
     *                                                       each with the same structure
     *                                                       as $indexEntry in calculateScore()
     * @param SearchContextInterface $context Search context containing query and search options
     * @return float Normalized score between 0.0 (no match) and 1.0 (perfect match)
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContextInterface $context): float;
}
