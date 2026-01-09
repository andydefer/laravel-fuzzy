<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;

class IndexConfigData extends Data
{
    public function __construct(
        public string $model,
        public array $fields,
        public ?string $format = null,
        public float $weight = 1.0,
        public array $conditions = [],
    ) {}

    /**
     * Check if model matches conditions
     */
    public function matchesConditions(object $model): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $field => $value) {
            if ($model->getAttribute($field) != $value) {
                return false;
            }
        }

        return true;
    }
}
