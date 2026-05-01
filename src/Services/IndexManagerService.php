<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\IndexManagerInterface;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;

class IndexManagerService implements IndexManagerInterface
{
    private const REINDEX_CHUNK_SIZE = 100;
    private const PCT_FACTOR = 100;

    public function __construct(
        private IndexBuilder $indexBuilder,
        private IndexRepositoryInterface $indexRepository,
        private ModelDiscoveryInterface $modelDiscovery
    ) {}

    public function indexModel(MustFuzzySearch $model): void
    {
        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);
        }
    }

    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModel($model);
        $this->indexModel($model);
    }

    public function removeModel(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();

        FuzzyIndex::forModelInstance($modelType, $modelId)->delete();
    }

    public function reindexAll(): void
    {
        $models = $this->modelDiscovery->getSearchableModels();

        foreach ($models as $modelClass) {
            $this->reindexModel($modelClass);
        }
    }

    public function reindexModel(string $modelClass): void
    {
        $this->modelDiscovery->validateModel($modelClass);

        FuzzyIndex::forModel($modelClass)->delete();

        $modelClass::chunk(self::REINDEX_CHUNK_SIZE, function ($models): void {
            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $this->indexModel($model);
                }
            }
        });
    }

    public function getStats(): array
    {
        return $this->indexRepository->getStats();
    }

    public function getPreciseModelStats(string $modelClass): array
    {
        $this->modelDiscovery->validateModel($modelClass);

        $stats = $this->getStats();
        $indexedEntries = $stats['models'][$modelClass]['count'] ?? 0;

        $modelInstance = new $modelClass();
        $searchableFields = $modelInstance->getSearchableFields();
        $fieldsPerModel = count($searchableFields);

        $totalRecords = 0;
        $indexableRecords = 0;

        $modelClass::chunk(self::REINDEX_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexableRecords) {
            $totalRecords += count($models);

            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $indexableRecords++;
                }
            }
        });

        $estimatedIndexedModels = $fieldsPerModel > 0
            ? (int) round($indexedEntries / $fieldsPerModel)
            : 0;

        $estimatedIndexedModels = min($estimatedIndexedModels, $indexableRecords);

        $coveragePercentage = $indexableRecords > 0
            ? round(($estimatedIndexedModels / $indexableRecords) * self::PCT_FACTOR, 1)
            : 0;

        return [
            'total_records' => $totalRecords,
            'indexable_records' => $indexableRecords,
            'indexed_entries' => $indexedEntries,
            'estimated_indexed_models' => $estimatedIndexedModels,
            'fields_per_model' => $fieldsPerModel,
            'coverage_percentage' => $coveragePercentage,
        ];
    }
}
