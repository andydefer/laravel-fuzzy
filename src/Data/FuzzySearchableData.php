<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * Data object representing a searchable item in fuzzy search results.
 *
 * Contains metadata and data for items that can be searched through the fuzzy search system.
 * This object is used to standardize the format of search results across different model types.
 */
class FuzzySearchableData extends Data
{
    /**
     * @param string|int $id Unique identifier of the searchable item
     * @param string $name Display name of the item
     * @param string $type Type/class of the item
     * @param object|null $model Original model instance (if available)
     * @param array $data Raw data array from the model
     * @param string|null $description Optional description of the item
     * @param string|null $image Optional image URL or path
     * @param string|null $url Optional URL for the item
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
     * @param Model $model The model to convert to searchable data
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
