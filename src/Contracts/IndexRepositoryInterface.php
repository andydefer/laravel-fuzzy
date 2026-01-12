<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Fuzzy\SearchContext;
use Illuminate\Support\Collection;

/**
 * Defines the contract for index repository operations.
 *
 * Responsible for managing and retrieving indexed data for searchable models,
 * optimizing queries, and providing statistical information about the index.
 */
interface IndexRepositoryInterface
{
    /**
     * Retrieves indexed data for a specific model class.
     *
     * Returns structured index data that can be filtered by specific model IDs.
     * Used to fetch pre-processed data for search operations.
     *
     * @param string $modelClass Fully qualified model class name
     * @param array<int> $modelIds Optional array of model IDs to filter results
     * @return array<string, mixed> Structured index data for the model
     */
    public function getIndexDataForModel(string $modelClass, array $modelIds = []): array;

    /**
     * Retrieves models in batches to optimize database queries.
     *
     * Prevents N+1 query problems by loading multiple models at once.
     *
     * @param string $modelClass Fully qualified model class name
     * @param array<int> $ids Array of model IDs to retrieve
     * @return Collection<int, \Illuminate\Database\Eloquent\Model> Collection of retrieved models
     */
    public function getModelsBatch(string $modelClass, array $ids): Collection;

    /**
     * Returns a map of preloaded models for efficient access.
     *
     * Provides quick lookup of models that have already been loaded
     * to avoid redundant database queries.
     *
     * @return array<string, array<int, \Illuminate\Database\Eloquent\Model>>
     *         Map keyed by model class with arrays of model instances
     */
    public function getPreloadedModelsMap(): array;

    /**
     * Preloads all models referenced in the search context.
     *
     * Optimizes performance by loading all required models before
     * processing search results.
     *
     * @param SearchContext $context Search context containing model references
     */
    public function preloadModels(SearchContext $context): void;

    /**
     * Returns statistical information about the index.
     *
     * Provides insights into index size, model coverage, and performance metrics.
     * Useful for monitoring and debugging.
     *
     * @return array<string, mixed> Statistical data about the index
     */
    public function getStats(): array;
}
