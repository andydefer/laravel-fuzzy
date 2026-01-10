<?php

declare(strict_types=1);

namespace Fuzzy\Traits;

use Fuzzy\Contracts\MustFuzzySearch;
use Illuminate\Support\Collection;

/**
 * Provides fuzzy search capabilities to Eloquent models.
 *
 * This trait automatically indexes models for search operations and provides
 * methods for searching within the model's scope. It handles the lifecycle
 * events (create, update, delete) to maintain search index consistency.
 */
trait FuzzySearchable
{
    /**
     * Boot the fuzzy searchable trait.
     *
     * Sets up model event listeners to automatically manage search index
     * during create, update, and delete operations.
     *
     * @return void
     */
    protected static function bootFuzzySearchable(): void
    {
        static::created(function ($model) {
            if ($model->shouldBeIndexed() && method_exists($model, 'indexForSearch')) {
                $model->indexForSearch();
            }
        });

        static::updated(function ($model) {
            if ($model->shouldBeIndexed() && method_exists($model, 'updateIndexForSearch')) {
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
     * Determine if this model instance should be indexed.
     *
     * Override this method in individual models to implement custom indexing logic.
     *
     * @return bool
     */
    public function shouldBeIndexed(): bool
    {
        return true;
    }

    /**
     * Get the searchable fields for the model.
     *
     * @return array<string>
     */
    public function getSearchableFields(): array
    {
        return property_exists($this, 'searchableFields')
            ? $this->searchableFields
            : ['name', 'email', 'description'];
    }

    /**
     * Get the format class for custom search data transformation.
     *
     * @return string|null
     */
    public function getFuzzyFormat(): ?string
    {
        return property_exists($this, 'fuzzyFormat') ? $this->fuzzyFormat : null;
    }

    /**
     * Get the identifier used for indexing.
     *
     * @return string|int
     */
    public function getIndexableId(): string|int
    {
        return $this->getKey();
    }

    /**
     * Get the type identifier for search results.
     *
     * @return string
     */
    public function getSearchableType(): string
    {
        return class_basename($this);
    }

    /**
     * Index this model for search.
     *
     * @return void
     */
    public function indexForSearch(): void
    {
        app('laravel-fuzzy.search')->indexModel($this);
    }

    /**
     * Update the search index for this model.
     *
     * @return void
     */
    public function updateIndexForSearch(): void
    {
        app('laravel-fuzzy.search')->updateModelIndex($this);
    }

    /**
     * Remove this model from the search index.
     *
     * @return void
     */
    public function removeFromIndex(): void
    {
        app('laravel-fuzzy.search')->removeModelFromIndex($this);
    }

    /**
     * Search within this model's scope.
     *
     * @param string $query The search query
     * @param array $options Additional search options
     * @return Collection<int, mixed>
     */
    public static function fuzzySearch(string $query, array $options = []): Collection
    {
        return app('laravel-fuzzy.search')->searchInModel(static::class, $query, $options);
    }
}
