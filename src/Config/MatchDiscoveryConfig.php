<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Configuration Value Object for the Match Discovery Stage.
 *
 * Encapsulates all configurable parameters for optimizing match discovery:
 * - Cache TTL for optimized index structures
 * - Index size thresholds (small vs large index strategies)
 * - Similarity thresholds for exact and fuzzy matching
 * - Word length categorization (small, medium, large)
 * - Performance limits (max checks per query, candidate limits)
 * - Trigram-based matching parameters
 *
 * All values are immutable and loaded from Laravel configuration with sensible defaults.
 */
final class MatchDiscoveryConfig implements ConfigInterface
{
    /** Default cache TTL in seconds (5 minutes). */
    private const DEFAULT_CACHE_TTL = 300;

    /** Threshold for considering an index as "small" (1000 words or less). */
    private const DEFAULT_SMALL_INDEX_THRESHOLD = 1000;

    /** High similarity threshold for very close matches (0.7 = 70% similarity). */
    private const DEFAULT_HIGH_THRESHOLD = 0.7;

    /** Maximum allowed length difference between query and indexed words (in characters). */
    private const DEFAULT_MAX_LENGTH_DIFFERENCE = 2;

    /** Maximum length for words considered "small" (≤ 3 characters). */
    private const DEFAULT_SMALL_WORD_LENGTH = 3;

    /** Maximum length for words considered "medium" (≤ 6 characters). */
    private const DEFAULT_MEDIUM_WORD_LENGTH = 6;

    /** Maximum number of candidate words to evaluate per query (performance bound). */
    private const DEFAULT_MAX_CHECKS_PER_QUERY = 500;

    /** Maximum number of trigram-based candidates to evaluate (performance bound). */
    private const DEFAULT_MAX_TRIGRAM_CANDIDATES = 100;

    /** Maximum number of words to check per length in contained matches. */
    private const DEFAULT_MAX_CONTAINED_CHECKS = 200;

    /** Maximum number of cache entries before triggering cleanup. */
    private const DEFAULT_MAX_CACHE_ENTRIES = 20;

    /** Probability factor for cache cleanup (1 in N chance to run). */
    private const DEFAULT_CACHE_CLEANUP_PROBABILITY = 100;

    /** Length range offset for small words (+3 characters). */
    private const DEFAULT_SMALL_WORD_OFFSET = 3;

    /** Length range offset for medium words (+2 characters). */
    private const DEFAULT_MEDIUM_WORD_OFFSET = 2;

    /** Length range offset for large words (+1 character). */
    private const DEFAULT_LARGE_WORD_OFFSET = 1;

    /** Minimum word length to consider for indexing and matching (≥ 2 characters). */
    private const DEFAULT_MIN_WORD_LENGTH = 2;

    /** Minimum trigram length (3 characters) for trigram-based matching. */
    private const DEFAULT_MIN_TRIGRAM_LENGTH = 3;

    public function __construct(
        private readonly int $cacheTtl,
        private readonly int $smallIndexThreshold,
        private readonly float $highThreshold,
        private readonly int $maxLengthDifference,
        private readonly int $smallWordLength,
        private readonly int $mediumWordLength,
        private readonly int $maxChecksPerQuery,
        private readonly int $maxTrigramCandidates,
        private readonly int $maxContainedChecks,
        private readonly int $maxCacheEntries,
        private readonly int $cacheCleanupProbability,
        private readonly int $smallWordOffset,
        private readonly int $mediumWordOffset,
        private readonly int $largeWordOffset,
        private readonly int $minWordLength,
        private readonly int $minTrigramLength
    ) {}

    /**
     * Create an instance from Laravel configuration.
     *
     * Loads values from 'fuzzy.match_discovery' config key and merges with defaults.
     *
     * @return self Configured instance
     */
    public static function fromConfig(): self
    {
        $config = config('fuzzy.match_discovery', []);

        return new self(
            cacheTtl: $config['cache_ttl'] ?? self::DEFAULT_CACHE_TTL,
            smallIndexThreshold: $config['small_index_threshold'] ?? self::DEFAULT_SMALL_INDEX_THRESHOLD,
            highThreshold: (float) ($config['high_threshold'] ?? self::DEFAULT_HIGH_THRESHOLD),
            maxLengthDifference: $config['max_length_difference'] ?? self::DEFAULT_MAX_LENGTH_DIFFERENCE,
            smallWordLength: $config['small_word_length'] ?? self::DEFAULT_SMALL_WORD_LENGTH,
            mediumWordLength: $config['medium_word_length'] ?? self::DEFAULT_MEDIUM_WORD_LENGTH,
            maxChecksPerQuery: $config['max_checks_per_query'] ?? self::DEFAULT_MAX_CHECKS_PER_QUERY,
            maxTrigramCandidates: $config['max_trigram_candidates'] ?? self::DEFAULT_MAX_TRIGRAM_CANDIDATES,
            maxContainedChecks: $config['max_contained_checks'] ?? self::DEFAULT_MAX_CONTAINED_CHECKS,
            maxCacheEntries: $config['max_cache_entries'] ?? self::DEFAULT_MAX_CACHE_ENTRIES,
            cacheCleanupProbability: $config['cache_cleanup_probability'] ?? self::DEFAULT_CACHE_CLEANUP_PROBABILITY,
            smallWordOffset: $config['small_word_offset'] ?? self::DEFAULT_SMALL_WORD_OFFSET,
            mediumWordOffset: $config['medium_word_offset'] ?? self::DEFAULT_MEDIUM_WORD_OFFSET,
            largeWordOffset: $config['large_word_offset'] ?? self::DEFAULT_LARGE_WORD_OFFSET,
            minWordLength: $config['min_word_length'] ?? self::DEFAULT_MIN_WORD_LENGTH,
            minTrigramLength: $config['min_trigram_length'] ?? self::DEFAULT_MIN_TRIGRAM_LENGTH
        );
    }

    /**
     * Create a default instance with built-in values.
     *
     * Useful for testing or when no configuration is available.
     *
     * @return self Default configured instance
     */
    public static function createDefault(): self
    {
        return new self(
            cacheTtl: self::DEFAULT_CACHE_TTL,
            smallIndexThreshold: self::DEFAULT_SMALL_INDEX_THRESHOLD,
            highThreshold: self::DEFAULT_HIGH_THRESHOLD,
            maxLengthDifference: self::DEFAULT_MAX_LENGTH_DIFFERENCE,
            smallWordLength: self::DEFAULT_SMALL_WORD_LENGTH,
            mediumWordLength: self::DEFAULT_MEDIUM_WORD_LENGTH,
            maxChecksPerQuery: self::DEFAULT_MAX_CHECKS_PER_QUERY,
            maxTrigramCandidates: self::DEFAULT_MAX_TRIGRAM_CANDIDATES,
            maxContainedChecks: self::DEFAULT_MAX_CONTAINED_CHECKS,
            maxCacheEntries: self::DEFAULT_MAX_CACHE_ENTRIES,
            cacheCleanupProbability: self::DEFAULT_CACHE_CLEANUP_PROBABILITY,
            smallWordOffset: self::DEFAULT_SMALL_WORD_OFFSET,
            mediumWordOffset: self::DEFAULT_MEDIUM_WORD_OFFSET,
            largeWordOffset: self::DEFAULT_LARGE_WORD_OFFSET,
            minWordLength: self::DEFAULT_MIN_WORD_LENGTH,
            minTrigramLength: self::DEFAULT_MIN_TRIGRAM_LENGTH
        );
    }

    /**
     * Get cache TTL for optimized index structures.
     *
     * @return int Time to live in seconds
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Get the threshold for considering an index as "small".
     *
     * @return int Maximum number of words for small index classification
     */
    public function getSmallIndexThreshold(): int
    {
        return $this->smallIndexThreshold;
    }

    /**
     * Get the high similarity threshold for very close matches.
     *
     * @return float Similarity threshold (0.0 to 1.0)
     */
    public function getHighThreshold(): float
    {
        return $this->highThreshold;
    }

    /**
     * Get the maximum allowed length difference for matching.
     *
     * @return int Maximum character difference between words
     */
    public function getMaxLengthDifference(): int
    {
        return $this->maxLengthDifference;
    }

    /**
     * Get the maximum length for "small" word classification.
     *
     * @return int Small word threshold in characters
     */
    public function getSmallWordLength(): int
    {
        return $this->smallWordLength;
    }

    /**
     * Get the maximum length for "medium" word classification.
     *
     * @return int Medium word threshold in characters
     */
    public function getMediumWordLength(): int
    {
        return $this->mediumWordLength;
    }

    /**
     * Get the maximum number of candidate checks per query.
     *
     * @return int Performance bound for match discovery
     */
    public function getMaxChecksPerQuery(): int
    {
        return $this->maxChecksPerQuery;
    }

    /**
     * Get the maximum number of trigram candidates to evaluate.
     *
     * @return int Performance bound for trigram-based matching
     */
    public function getMaxTrigramCandidates(): int
    {
        return $this->maxTrigramCandidates;
    }

    /**
     * Get the maximum number of words to check per length in contained matches.
     *
     * @return int Performance bound for contained word matching
     */
    public function getMaxContainedChecks(): int
    {
        return $this->maxContainedChecks;
    }

    /**
     * Get the maximum number of cache entries before cleanup.
     *
     * @return int Maximum cache entries threshold
     */
    public function getMaxCacheEntries(): int
    {
        return $this->maxCacheEntries;
    }

    /**
     * Get the probability factor for cache cleanup.
     *
     * @return int Denominator of 1-in-N chance to trigger cleanup
     */
    public function getCacheCleanupProbability(): int
    {
        return $this->cacheCleanupProbability;
    }

    /**
     * Get the length offset for small words.
     *
     * @return int Additional characters to check for small words
     */
    public function getSmallWordOffset(): int
    {
        return $this->smallWordOffset;
    }

    /**
     * Get the length offset for medium words.
     *
     * @return int Additional characters to check for medium words
     */
    public function getMediumWordOffset(): int
    {
        return $this->mediumWordOffset;
    }

    /**
     * Get the length offset for large words.
     *
     * @return int Additional characters to check for large words
     */
    public function getLargeWordOffset(): int
    {
        return $this->largeWordOffset;
    }

    /**
     * Get the minimum word length for indexing and matching.
     *
     * @return int Minimum word length in characters
     */
    public function getMinWordLength(): int
    {
        return $this->minWordLength;
    }

    /**
     * Get the minimum trigram length for trigram-based matching.
     *
     * @return int Minimum trigram length in characters
     */
    public function getMinTrigramLength(): int
    {
        return $this->minTrigramLength;
    }
}
