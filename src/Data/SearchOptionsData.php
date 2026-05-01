<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;

/**
 * Data object representing search options for fuzzy search queries.
 *
 * Encapsulates all configurable parameters for controlling search behavior,
 * including scoring thresholds, result limits, and fuzzy matching settings.
 *
 * @package Fuzzy\Data
 */
class SearchOptionsData extends Data
{
    /** Default minimum relevance score (0.0 to 1.0). */
    private const DEFAULT_MIN_SCORE = 0.1;

    /** Default maximum number of results to return. */
    private const DEFAULT_MAX_RESULTS = 20;

    /** Default fuzzy matching enabled status. */
    private const DEFAULT_FUZZY = true;

    /** Default similarity threshold for fuzzy matching (0.0 to 1.0). */
    private const DEFAULT_THRESHOLD = 0.3;

    /**
     * Constructor for SearchOptionsData.
     *
     * @param float $minScore Minimum relevance score for results (0.0 to 1.0)
     * @param int $maxResults Maximum number of results to return
     * @param bool $fuzzy Whether to use fuzzy matching algorithms
     * @param float $threshold Similarity threshold for fuzzy matching (0.0 to 1.0)
     */
    public function __construct(
        public float $minScore = self::DEFAULT_MIN_SCORE,
        public int $maxResults = self::DEFAULT_MAX_RESULTS,
        public bool $fuzzy = self::DEFAULT_FUZZY,
        public float $threshold = self::DEFAULT_THRESHOLD,
    ) {}

    /**
     * Create instance from array with support for both camelCase and snake_case keys.
     *
     * @param array<string, mixed> $data Input data with search options
     * @return self Configured SearchOptionsData instance
     */
    public static function fromArray(array $data): self
    {
        $minScore = $data['minScore'] ?? $data['min_score'] ?? self::DEFAULT_MIN_SCORE;
        $maxResults = $data['maxResults'] ?? $data['max_results'] ?? self::DEFAULT_MAX_RESULTS;
        $fuzzy = $data['fuzzy'] ?? self::DEFAULT_FUZZY;
        $threshold = $data['threshold'] ?? self::DEFAULT_THRESHOLD;

        return new self(
            minScore: (float) $minScore,
            maxResults: (int) $maxResults,
            fuzzy: (bool) $fuzzy,
            threshold: (float) $threshold,
        );
    }

    /**
     * Create instance from configuration with optional overrides.
     *
     * @param array<string, mixed> $override Options to override default configuration
     * @return self Configured SearchOptionsData instance
     */
    public static function fromConfig(array $override = []): self
    {
        $defaultConfig = config('fuzzy.default_options', [
            'min_score' => self::DEFAULT_MIN_SCORE,
            'max_results' => self::DEFAULT_MAX_RESULTS,
            'fuzzy' => self::DEFAULT_FUZZY,
            'threshold' => self::DEFAULT_THRESHOLD,
        ]);

        $mergedOptions = array_merge($defaultConfig, $override);

        return self::fromArray($mergedOptions);
    }
}
