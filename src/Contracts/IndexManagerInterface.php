<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for managing the search index operations.
 *
 * Defines the contract for creating, updating, and removing index entries
 * for searchable models. Implementations handle the persistence layer
 * for fuzzy search indexing with support for single models, bulk operations,
 * and index statistics.
 *
 * @package Fuzzy\Contracts
 */
interface IndexManagerInterface
{
    /**
     * Add a model instance to the search index.
     *
     * Creates index entries for all searchable fields of the given model.
     * Should respect the model's `shouldBeIndexed()` method.
     *
     * @param MustFuzzySearch $model The model instance to index
     * @return void
     */
    public function indexModel(MustFuzzySearch $model): void;

    /**
     * Update the search index for a model instance.
     *
     * Removes existing index entries for the model and recreates them
     * with current data. Useful after model updates.
     *
     * @param MustFuzzySearch $model The model instance to update in the index
     * @return void
     */
    public function updateModelIndex(MustFuzzySearch $model): void;

    /**
     * Remove a model instance from the search index.
     *
     * Deletes all index entries associated with the given model.
     * Called automatically when a model is deleted.
     *
     * @param MustFuzzySearch $model The model instance to remove from the index
     * @return void
     */
    public function removeModel(MustFuzzySearch $model): void;

    /**
     * Reindex all searchable models in the system.
     *
     * Truncates the entire index and rebuilds it from scratch
     * for all models that implement MustFuzzySearch.
     *
     * @return void
     */
    public function reindexAll(): void;

    /**
     * Reindex all instances of a specific model class.
     *
     * Deletes all index entries for the given model type and recreates them
     * by iterating through all instances of that model.
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     */
    public function reindexModel(string $modelClass): void;

    /**
     * Get global index statistics.
     *
     * Returns an array containing aggregated information about the index,
     * such as total entries, entries per model type, and field distribution.
     *
     * @return array<string, mixed> Global index statistics
     */
    public function getStats(): array;

    /**
     * Get detailed indexing statistics for a specific model.
     *
     * Provides precise information including total records, indexable records,
     * indexed entries count, estimated indexed models, and coverage percentage.
     *
     * @param string $modelClass Fully qualified model class name
     * @return array<string, mixed> Detailed statistics for the specified model
     */
    public function getPreciseModelStats(string $modelClass): array;
}
