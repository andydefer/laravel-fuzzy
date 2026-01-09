<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;

class SearchOptionsData extends Data
{
    public function __construct(
        public float $minScore = 0.1,
        public int $maxResults = 20,
        public bool $fuzzy = true,
        public float $threshold = 0.3,
    ) {}

    /**
     * Create from array - support both snake_case and camelCase
     */
    public static function fromArray(array $data): self
    {
        // Extract values with proper priority: camelCase first, then snake_case
        $minScore = isset($data['minScore']) ? (float)$data['minScore'] : (isset($data['min_score']) ? (float)$data['min_score'] : 0.1);

        $maxResults = isset($data['maxResults']) ? (int)$data['maxResults'] : (isset($data['max_results']) ? (int)$data['max_results'] : 20);

        $fuzzy = $data['fuzzy'] ?? true;
        $threshold = isset($data['threshold']) ? (float)$data['threshold'] : 0.3;

        return new self(
            minScore: $minScore,
            maxResults: $maxResults,
            fuzzy: $fuzzy,
            threshold: $threshold,
        );
    }

    /**
     * Create from config with proper defaults
     */
    public static function fromConfig(array $override = []): self
    {
        $defaultConfig = config('fuzzy.default_options', [
            'min_score' => 0.1,
            'max_results' => 20,
            'fuzzy' => true,
            'threshold' => 0.3,
        ]);

        // Merge override with config defaults
        $merged = array_merge($defaultConfig, $override);

        return self::fromArray($merged);
    }
}
