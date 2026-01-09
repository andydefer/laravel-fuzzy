<?php

declare(strict_types=1);

namespace Fuzzy\Traits;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Data\FuzzySearchableData;

trait FuzzySearchable
{
    /**
     * Boot the trait
     */
    protected static function bootFuzzySearchable(): void
    {
        static::created(function ($model) {
            if (method_exists($model, 'indexForSearch')) {
                $model->indexForSearch();
            }
        });

        static::updated(function ($model) {
            if (method_exists($model, 'updateIndexForSearch')) {
                $model->updateIndexForSearch();
            }
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'removeFromIndex')) {
                $model->removeFromIndex();
            }
        });
    }

    /**
     * Default searchable fields - can be overridden in model
     */
    public function getSearchableFields(): array
    {
        return property_exists($this, 'searchableFields')
            ? $this->searchableFields
            : ['name', 'email', 'description'];
    }

    /**
     * Default format class - can be overridden in model
     */
    public function getFuzzyFormat(): ?string
    {
        return property_exists($this, 'fuzzyFormat')
            ? $this->fuzzyFormat
            : null;
    }

    /**
     * Default searchable name - can be overridden in model
     */
    public function getSearchableName(): string
    {
        return $this->getAttribute('name') ?? class_basename($this);
    }

    /**
     * Default indexable ID
     */
    public function getIndexableId(): string|int
    {
        return $this->getKey();
    }

    /**
     * Default searchable type
     */
    public function getSearchableType(): string
    {
        return class_basename($this);
    }

    /**
     * Convert to searchable data
     */
    public function toSearchableData(): ?FuzzySearchableData
    {
        $formatClass = $this->getFuzzyFormat();

        if ($formatClass && class_exists($formatClass)) {
            return $formatClass::from($this);
        }

        return FuzzySearchableData::from([
            'id' => $this->getIndexableId(),
            'name' => $this->getSearchableName(),
            'type' => $this->getSearchableType(),
            'model' => $this,
            'data' => $this->toArray(),
        ]);
    }

    /**
     * Index this model for search
     */
    public function indexForSearch(): void
    {
        app('laravel-fuzzy.search')->indexModel($this);
    }

    /**
     * Update index for this model
     */
    public function updateIndexForSearch(): void
    {
        app('laravel-fuzzy.search')->updateModelIndex($this);
    }

    /**
     * Remove this model from index
     */
    public function removeFromIndex(): void
    {
        app('laravel-fuzzy.search')->removeModelFromIndex($this);
    }

    /**
     * Search in this model only
     */
    public static function fuzzySearch(string $query, array $options = []): \Illuminate\Support\Collection
    {
        return app('laravel-fuzzy.search')->searchInModel(static::class, $query, $options);
    }
}
