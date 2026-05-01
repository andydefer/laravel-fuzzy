<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object representing a searchable item in fuzzy search results.
 *
 * This object standardizes the format of search results across different model types,
 * providing a consistent interface for search result consumers.
 *
 * @package Fuzzy\Data
 */
class FuzzySearchableData extends Data
{
    /**
     * Constructor for FuzzySearchableData.
     *
     * @param string|int $id Unique identifier of the searchable item
     * @param string $name Display name of the item for presentation
     * @param string $type Type or class name of the item (e.g., 'User', 'Product')
     * @param object|null $model Original model instance when available
     * @param array<string, mixed> $data Raw data array from the model attributes
     * @param string|null $description Optional detailed description of the item
     * @param string|null $image Optional image URL or path for visual representation
     * @param string|null $url Optional URL linking to the item's detail page
     */
    public function __construct(
        public string|int $id,
        public string $name,
        public string $type,
        public ?object $model = null,
        public array $data = [],
        public ?string $description = null,
        public ?string $image = null,
        public ?string $url = null,
    ) {}

    /**
     * Create a FuzzySearchableData instance from an Eloquent model.
     *
     * Automatically extracts key properties from the model to populate the searchable data object.
     * Falls back to the class basename if no name attribute is present.
     *
     * @param Model $model The Eloquent model to convert to searchable format
     * @return self Configured FuzzySearchableData instance
     */
    public static function fromModel(Model $model): self
    {
        return new self(
            id: $model->getKey(),
            name: $model->getAttribute('name') ?? class_basename($model),
            type: class_basename($model),
            model: $model,
            data: $model->toArray(),
        );
    }
}
