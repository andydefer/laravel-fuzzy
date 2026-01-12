<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;

/**
 * Data object representing search options for fuzzy search queries.
 *
 * Encapsulates all configurable parameters for controlling search behavior,
 * including scoring thresholds, result limits, and fuzzy matching settings.
 */
class SearchOptionsData extends Data
{
    /**
     * @param float $minScore Minimum relevance score for results (0.0 to 1.0)
     * @param int $maxResults Maximum number of results to return
     * @param bool $fuzzy Whether to use fuzzy matching algorithms
     * @param float $threshold Similarity threshold for fuzzy matching (0.0 to 1.0)
     */
    public function __construct(
        public float $minScore = 0.1,
        public int $maxResults = 20,
        public bool $fuzzy = true,
        public float $threshold = 0.3,
    ) {}

    /**
     * Create instance from array with support for both camelCase and snake_case keys.
     *
     * @param array<string, mixed> $data Input data with search options
     */
    public static function fromArray(array $data): self
    {
        $minScore = $data['minScore'] ?? $data['min_score'] ?? 0.1;
        $maxResults = $data['maxResults'] ?? $data['max_results'] ?? 20;
        $fuzzy = $data['fuzzy'] ?? true;
        $threshold = $data['threshold'] ?? 0.3;

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
     * @param array $override Options to override default configuration
     */
    public static function fromConfig(array $override = []): self
    {
        $defaultConfig = config('fuzzy.default_options', [
            'min_score' => 0.1,
            'max_results' => 20,
            'fuzzy' => true,
            'threshold' => 0.3,
        ]);

        $mergedOptions = array_merge($defaultConfig, $override);

        return self::fromArray($mergedOptions);
    }
}
