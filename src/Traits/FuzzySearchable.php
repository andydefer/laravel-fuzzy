<?php

declare(strict_types=1);

namespace Fuzzy\Traits;

use Illuminate\Support\Collection;
use Fuzzy\Services\FuzzySearchService;

/**
 * Provides fuzzy search capabilities to Eloquent models
 *
 * This trait automatically indexes models for search operations and provides
 * methods for searching within the model's scope. It handles lifecycle
 * events (create, update, delete) to maintain search index consistency.
 *
 * The trait requires the model to implement MustFuzzySearch interface,
 * which forces explicit definition of searchable fields, formatting,
 * indexing logic, and conditional indexing.
 *
 * @package Fuzzy\Traits
 */
trait FuzzySearchable
{
    /**
     * Boot the fuzzy searchable trait
     *
     * Registers model event listeners to automatically manage search index
     * during create, update, and delete operations.
     *
     * @return void
     */
    protected static function bootFuzzySearchable(): void
    {
        static::created(static function ($model): void {
            if ($model->shouldBeIndexed()) {
                app(FuzzySearchService::class)->getIndexManager()->indexModel($model);
            }
        });

        static::updated(static function ($model): void {
            if ($model->shouldBeIndexed()) {
                app(FuzzySearchService::class)->getIndexManager()->updateModelIndex($model);
            }
        });

        static::deleted(static function ($model): void {
            app(FuzzySearchService::class)->getIndexManager()->removeModel($model);
        });
    }

    /**
     * Get the weight for a specific searchable field
     *
     * Returns the weight multiplier for a given field. Higher weights make
     * matches in that field more important in scoring.
     *
     * Override this method in your model to customize field weights.
     *
     * @param string $field The field name
     * @return float Weight multiplier (default: 1.0)
     */
    public function getFieldWeight(string $field): float
    {
        if (property_exists($this, 'fieldWeights') && isset($this->fieldWeights[$field])) {
            return (float) $this->fieldWeights[$field];
        }

        return 1.0;
    }

    /**
     * Get the unique identifier for this model instance
     *
     * Used to associate index entries with the correct model instance during
     * search result retrieval. Returns the model's primary key by default.
     *
     * Override this method if your model uses a custom key name.
     *
     * @return string|int Unique identifier for this model instance
     */
    public function getIndexableId(): string|int
    {
        return $this->getKey();
    }

    /**
     * Get protected fields that should preserve stop words.
     *
     * By default, returns an empty array (no fields protected).
     * Override this method in your model to specify which fields should
     * preserve stop words during indexing.
     *
     * Example:
     * ```php
     * public function getProtectedFields(): array
     * {
     *     return ['name', 'email', 'firstname', 'lastname'];
     * }
     * ```
     *
     * @return array<int, string> List of field names that should preserve stop words
     */
    public function getProtectedFields(): array
    {
        if (property_exists($this, 'protectedFields') && is_array($this->protectedFields)) {
            return $this->protectedFields;
        }

        return [];
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
        return app(FuzzySearchService::class)->searchInModel(
            modelClass: static::class,
            query: $query,
            options: $options
        );
    }
}
