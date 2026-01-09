<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class FuzzySearchableData extends Data
{
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
     * Create from model
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
