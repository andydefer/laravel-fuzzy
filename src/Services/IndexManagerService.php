<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\IndexManagerInterface;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Exceptions\FuzzySearchException;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;

/**
 * Manages the fuzzy search index lifecycle for Eloquent models.
 *
 * This service is responsible for creating, updating, removing, and rebuilding
 * search index entries for models that implement MustFuzzySearch interface.
 * It ensures index consistency by only indexing records that should be searchable.
 */
class IndexManagerService implements IndexManagerInterface
{
    /**
     * Number of models to process per database chunk during reindexing.
     */
    private const REINDEX_CHUNK_SIZE = 100;

    /**
     * Factor for converting decimal ratios to percentages.
     */
    private const PERCENTAGE_FACTOR = 100;

    public function __construct(
        private IndexBuilder $indexBuilder,
        private IndexRepositoryInterface $indexRepository,
        private ModelDiscoveryInterface $modelDiscovery
    ) {}

    /**
     * Index a model or remove it from the index based on its shouldBeIndexed() state.
     *
     * This method ensures index consistency by checking if the model should be
     * searchable before attempting to index it. If the model should not be indexed,
     * it is immediately removed from the search index.
     *
     * @param MustFuzzySearch $model The model to index or remove
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);
        } else {
            $this->removeModel($model);
        }
    }

    /**
     * Update a model's index by removing then re-indexing it.
     *
     * Models that should no longer be indexed will be removed and not re-added.
     * This method is useful when a model's searchable content has changed
     * significantly or its indexable status may have changed.
     *
     * @param MustFuzzySearch $model The model to update in the index
     */
    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModel($model);

        if ($model->shouldBeIndexed()) {
            $this->indexModel($model);
        }
    }

    /**
     * Remove a model completely from the fuzzy search index.
     *
     * @param MustFuzzySearch $model The model to remove from the index
     */
    public function removeModel(MustFuzzySearch $model): void
    {
        $modelType = $this->getModelClass($model);
        $modelId = $model->getIndexableId();

        FuzzyIndex::forModelInstance($modelType, $modelId)->delete();
    }

    /**
     * Reindex all registered searchable models.
     *
     * This method clears all existing index entries for each model class and
     * rebuilds them using only records that should be indexed according to
     * their shouldBeIndexed() method.
     */
    public function reindexAll(): void
    {
        $models = $this->modelDiscovery->getSearchableModels();

        foreach ($models as $modelClass) {
            $this->reindexModel($modelClass);
        }
    }

    /**
     * Reindex a specific model class.
     *
     * Clears all existing index entries for the given model class and rebuilds
     * them using only records that should be indexed according to their
     * shouldBeIndexed() method.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The fully qualified class name of the model to reindex
     * 
     * @throws FuzzySearchException If the model is not searchable
     */
    public function reindexModel(string $modelClass): void
    {
        $this->validateSearchableModel($modelClass);

        $this->clearModelIndex($modelClass);
        $this->rebuildModelIndex($modelClass);
    }

    /**
     * Get overall index statistics.
     *
     * @return array<string, mixed> Statistics about the entire search index
     */
    public function getStats(): array
    {
        return $this->indexRepository->getStats();
    }

    /**
     * Get detailed precision statistics for a specific model class.
     *
     * Calculates index coverage by comparing estimated indexed models against
     * the number of records that should be indexable.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The fully qualified class name of the model
     * 
     * @return array<string, int|float> Statistics including total records, indexable records,
     *                                   indexed entries, estimated indexed models, fields per model,
     *                                   and coverage percentage
     * 
     * @throws FuzzySearchException If the model is not searchable
     */
    public function getPreciseModelStats(string $modelClass): array
    {
        $this->validateSearchableModel($modelClass);

        $indexedEntries = $this->getIndexedEntriesCount($modelClass);
        $fieldsPerModel = $this->getSearchableFieldsCount($modelClass);

        $recordCounts = $this->countModelRecords($modelClass);
        $totalRecords = $recordCounts['total'];
        $indexableRecords = $recordCounts['indexable'];

        $estimatedIndexedModels = $this->calculateEstimatedIndexedModels(
            indexedEntries: $indexedEntries,
            fieldsPerModel: $fieldsPerModel,
            maxPossible: $indexableRecords
        );

        $coveragePercentage = $this->calculateCoveragePercentage(
            indexedModels: $estimatedIndexedModels,
            totalIndexable: $indexableRecords
        );

        return [
            'total_records' => $totalRecords,
            'indexable_records' => $indexableRecords,
            'indexed_entries' => $indexedEntries,
            'estimated_indexed_models' => $estimatedIndexedModels,
            'fields_per_model' => $fieldsPerModel,
            'coverage_percentage' => $coveragePercentage,
        ];
    }

    /**
     * Validate that a model class is searchable (implements MustFuzzySearch).
     *
     * @param class-string $modelClass The model class to validate
     * 
     * @throws FuzzySearchException If the model does not implement MustFuzzySearch
     */
    private function validateSearchableModel(string $modelClass): void
    {
        $this->modelDiscovery->validateModel($modelClass);

        if (!is_subclass_of($modelClass, MustFuzzySearch::class)) {
            throw FuzzySearchException::modelNotSearchable($modelClass);
        }
    }

    /**
     * Get the fully qualified class name of a model.
     *
     * @param MustFuzzySearch $model The model instance
     * 
     * @return class-string<Model&MustFuzzySearch> The model class name
     */
    private function getModelClass(MustFuzzySearch $model): string
    {
        return get_class($model);
    }

    /**
     * Clear all index entries for a specific model class.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The model class to clear from index
     */
    private function clearModelIndex(string $modelClass): void
    {
        FuzzyIndex::forModel($modelClass)->delete();
    }

    /**
     * Rebuild the index for a specific model class.
     *
     * Only records that should be indexed (shouldBeIndexed() returns true)
     * will be added to the index.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The model class to rebuild
     */
    private function rebuildModelIndex(string $modelClass): void
    {
        /** @var class-string<Model&MustFuzzySearch> $modelClass */
        $modelClass::chunk(self::REINDEX_CHUNK_SIZE, function ($models): void {
            foreach ($models as $model) {
                $this->validateAndIndexModel($model);
            }
        });
    }

    /**
     * Validate a model implements MustFuzzySearch before indexing.
     *
     * This prevents index corruption by ensuring only searchable models
     * are added to the search index.
     *
     * @param mixed $model The model instance to validate and index
     * 
     * @throws FuzzySearchException If the model does not implement MustFuzzySearch
     */
    private function validateAndIndexModel(mixed $model): void
    {
        if (!$model instanceof MustFuzzySearch) {
            $modelClass = get_class($model);
            throw FuzzySearchException::modelNotSearchable($modelClass);
        }

        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);
        }
    }

    /**
     * Get the number of indexed entries for a specific model class.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The model class to query
     * 
     * @return int Number of index entries
     */
    private function getIndexedEntriesCount(string $modelClass): int
    {
        $stats = $this->getStats();

        return $stats['models'][$modelClass]['count'] ?? 0;
    }

    /**
     * Get the number of searchable fields defined for a model class.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The model class to inspect
     * 
     * @return int Number of searchable fields
     */
    private function getSearchableFieldsCount(string $modelClass): int
    {
        /** @var MustFuzzySearch $modelInstance */
        $modelInstance = new $modelClass();
        $searchableFields = $modelInstance->getSearchableFields();

        return count($searchableFields);
    }

    /**
     * Count total and indexable records for a model class.
     *
     * @param class-string<Model&MustFuzzySearch> $modelClass The model class to count
     * 
     * @return array{total: int, indexable: int} Total and indexable record counts
     */
    private function countModelRecords(string $modelClass): array
    {
        $totalRecords = 0;
        $indexableRecords = 0;

        /** @var class-string<Model&MustFuzzySearch> $modelClass */
        $modelClass::chunk(self::REINDEX_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexableRecords): void {
            $totalRecords += count($models);

            foreach ($models as $model) {
                if ($model instanceof MustFuzzySearch && $model->shouldBeIndexed()) {
                    $indexableRecords++;
                }
            }
        });

        return [
            'total' => $totalRecords,
            'indexable' => $indexableRecords,
        ];
    }

    /**
     * Calculate the estimated number of indexed models based on index entries.
     *
     * Each model with N searchable fields creates N index entries. This method
     * divides total entries by fields per model, capped by the maximum possible
     * indexable records.
     *
     * @param int $indexedEntries Total number of index entries
     * @param int $fieldsPerModel Number of searchable fields per model
     * @param int $maxPossible Maximum possible indexed models (indexable records)
     * 
     * @return int Estimated number of indexed models
     */
    private function calculateEstimatedIndexedModels(int $indexedEntries, int $fieldsPerModel, int $maxPossible): int
    {
        if ($fieldsPerModel === 0) {
            return 0;
        }

        $estimated = (int) round($indexedEntries / $fieldsPerModel);

        return min($estimated, $maxPossible);
    }

    /**
     * Calculate the coverage percentage of indexed models.
     *
     * @param int $indexedModels Number of estimated indexed models
     * @param int $totalIndexable Total number of records that should be indexed
     * 
     * @return float Coverage percentage (0-100)
     */
    private function calculateCoveragePercentage(int $indexedModels, int $totalIndexable): float
    {
        if ($totalIndexable === 0) {
            return 0.0;
        }

        return round(($indexedModels / $totalIndexable) * self::PERCENTAGE_FACTOR, 1);
    }
}
