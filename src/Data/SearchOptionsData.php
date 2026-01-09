<?php

declare(strict_types=1);

namespace LaravelFuzzy\Data;

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
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            minScore: $data['minScore'] ?? 0.1,
            maxResults: $data['maxResults'] ?? 20,
            fuzzy: $data['fuzzy'] ?? true,
            threshold: $data['threshold'] ?? 0.3,
        );
    }
}
