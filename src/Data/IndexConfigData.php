<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;

/**
 * Data object representing configuration for indexing a specific model.
 *
 * Defines which fields of a model should be indexed and under what conditions
 * the model should be included in the search index.
 */
class IndexConfigData extends Data
{
    /**
     * @param string $model Fully qualified class name of the model to index
     * @param array $fields List of field names to include in the search index
     * @param string|null $format Optional format string for displaying results
     * @param float $weight Weight multiplier for this model's search relevance
     * @param array $conditions Conditions that must be met for model to be indexed
     */
    public function __construct(
        public string $model,
        public array $fields,
        public ?string $format = null,
        public float $weight = 1.0,
        public array $conditions = [],
    ) {}

    /**
     * Check if a model instance matches the indexing conditions.
     *
     * @param object $model The model instance to check
     * @return bool True if model matches all conditions, false otherwise
     */
    public function matchesConditions(object $model): bool
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
