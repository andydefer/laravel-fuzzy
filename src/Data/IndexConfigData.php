<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * Data object representing configuration for indexing a specific model.
 *
 * Defines which fields of a model should be indexed and under what conditions
 * the model should be included in the search index.
 *
 * @package Fuzzy\Data
 */
class IndexConfigData extends Data
{
    /** Default weight value when not specified. */
    private const DEFAULT_WEIGHT = 1.0;

    /**
     * Constructor for IndexConfigData.
     *
     * @param string $model Fully qualified model class name
     * @param array<int, string> $fields List of field names to index
     * @param string|null $format Optional formatter class name
     * @param float $weight Weight multiplier for this model's scores
     * @param array<string, mixed> $conditions Conditions that must be met for indexing
     */
    public function __construct(
        public string $model,
        public array $fields,
        public ?string $format = null,
        public float $weight = self::DEFAULT_WEIGHT,
        public array $conditions = [],
    ) {}

    /**
     * Check if a model instance matches the indexing conditions.
     *
     * If no conditions are defined, the model always matches.
     *
     * @param Model $model The model instance to check
     * @return bool True if model matches all conditions, false otherwise
     */
    public function matchesConditions(Model $model): bool
    {
        if ($this->conditions === []) {
            return true;
        }

        foreach ($this->conditions as $field => $expectedValue) {
            if ($model->getAttribute($field) != $expectedValue) {
                return false;
            }
        }

        return true;
    }
}
