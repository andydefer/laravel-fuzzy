<?php

declare(strict_types=1);

namespace Fuzzy\Traits;

use Illuminate\Support\Collection;

/**
 * Provides fuzzy search capabilities to Eloquent models
 *
 * This trait automatically indexes models for search operations and provides
 * methods for searching within the model's scope. It handles lifecycle
 * events (create, update, delete) to maintain search index consistency.
 */
trait FuzzySearchable
{
    /**
     * Boot the fuzzy searchable trait
     *
     * Registers model event listeners to automatically manage search index
     * during create, update, and delete operations.
     */
    protected static function bootFuzzySearchable(): void
    {
        static::created(static function ($model): void {
            if ($model->shouldBeIndexed() && method_exists($model, 'indexForSearch')) {
                $model->indexForSearch();
            }
        });

        static::updated(static function ($model): void {
            if ($model->shouldBeIndexed() && method_exists($model, 'updateIndexForSearch')) {
                $model->updateIndexForSearch();
            }
        });

        static::deleted(static function ($model): void {
            if (method_exists($model, 'removeFromIndex')) {
                $model->removeFromIndex();
            }
        });
    }

    /**
     * Determine if this model instance should be indexed
     *
     * Override this method in individual models to implement custom indexing logic.
     *
     * @return bool True if the model should be indexed, false otherwise
     */
    public function shouldBeIndexed(): bool
    {
        return true;
    }

    /**
     * Get the searchable fields for the model
     *
     * @return array<int, string> Array of field names to be indexed for search
     */
    public function getSearchableFields(): array
    {
        if (property_exists($this, 'searchableFields')) {
            /** @var array<int, string> */
            return $this->searchableFields;
        }

        return ['name', 'email', 'description'];
    }

    /**
     * Get the format class for custom search data transformation
     *
     * @return class-string|null Fully qualified class name implementing custom formatting,
     *                           or null to use default formatting
     */
    public function getFuzzyFormat(): ?string
    {
        if (property_exists($this, 'fuzzyFormat')) {
            /** @var class-string|null */
            return $this->fuzzyFormat;
        }

        return null;
    }

    /**
     * Get the identifier used for indexing
     *
     * @return string|int Unique identifier for the model in the search index
     */
    public function getIndexableId(): string|int
    {
        return $this->getKey();
    }

    /**
     * Index this model for search
     *
     * Adds the model to the search index for future search operations.
     */
    public function indexForSearch(): void
    {
        app('laravel-fuzzy.search')->indexModel(model: $this);
    }

    /**
     * Update the search index for this model
     *
     * Updates the model's entry in the search index with current data.
     */
    public function updateIndexForSearch(): void
    {
        app('laravel-fuzzy.search')->updateModelIndex(model: $this);
    }

    /**
     * Remove this model from the search index
     *
     * Removes the model from the search index permanently.
     */
    public function removeFromIndex(): void
    {
        app('laravel-fuzzy.search')->removeModelFromIndex(model: $this);
    }

    /**
     * Search within this model's scope
     *
     * Performs a fuzzy search query limited to this specific model type.
     *
     * @param string $query Search query string
     * @param array<string, mixed> $options Additional search options
     * @return Collection<int, static> Collection of matching model instances
     */
    public static function fuzzySearch(string $query, array $options = []): Collection
    {
        return app('laravel-fuzzy.search')->searchInModel(
            modelClass: static::class,
            query: $query,
            options: $options
        );
    }
}
