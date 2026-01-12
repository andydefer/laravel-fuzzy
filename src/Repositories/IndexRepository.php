<?php

declare(strict_types=1);

namespace Fuzzy\Repositories;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\SearchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for managing fuzzy search index data with optimized database operations.
 */
class IndexRepository implements IndexRepositoryInterface
{
    /**
     * Cache of preloaded models keyed by model type and ID.
     *
     * @var array<string, Model>
     */
    private array $preloadedModelsMap = [];

    /**
     * Retrieve indexed data for a specific model class.
     *
     * Returns a structured array containing word index, item mapping, and model index
     * for efficient search operations.
     *
     * @param string $modelClass Fully qualified model class name
     * @param array<int> $modelIds Specific model IDs to filter (empty for all)
     * @return array{
     *     wordIndex: array<string, array<int, array<string, mixed>>>,
     *     itemMap: array<string, array{indexable_type: string, indexable_id: int|string}>,
     *     modelIndex: array<string, array<int, array<string, mixed>>>,
     *     rawEntries: array<int, array<string, mixed>>
     * }
     */
    public function getIndexDataForModel(string $modelClass, array $modelIds = []): array
    {
        $query = FuzzyIndex::forModel($modelClass);

        if ($modelIds !== []) {
            $query->whereIn('indexable_id', $modelIds);
        }

        $indexEntries = $query->get(['indexable_type', 'indexable_id', 'field', 'original_value', 'words', 'weight']);

        return $this->buildIndexStructures($indexEntries);
    }

    /**
     * Load multiple models efficiently in a single batch query.
     *
     * Supports eager loading of configured relationships to prevent N+1 query problems.
     *
     * @param string $modelClass Fully qualified model class name
     * @param array<int|string> $ids Model IDs to retrieve
     * @return Collection<int, Model>
     */
    public function getModelsBatch(string $modelClass, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $modelInstance = new $modelClass();
        $query = $modelClass::whereIn($modelInstance->getKeyName(), $ids);

        $eagerLoadRelations = config('fuzzy.eager_load.' . $modelClass, []);

        if ($eagerLoadRelations !== []) {
            $query->with($eagerLoadRelations);
        }

        return $query->get();
    }

    /**
     * Preload models referenced in search context for efficient access.
     *
     * This method loads all models needed for a search operation in a single query
     * and stores them in an internal cache for O(1) access.
     *
     * @param SearchContext $context Search context containing model IDs to preload
     */
    public function preloadModels(SearchContext $context): void
    {
        $modelIds = $context->getAllModelIds();

        if ($modelIds === []) {
            $this->preloadedModelsMap = [];
            return;
        }

        $itemMap = $context->getItemMap();

        if ($itemMap === []) {
            $this->preloadedModelsMap = [];
            return;
        }

        $modelClass = $context->getModelClass();
        $models = $this->getModelsBatch($modelClass, $modelIds);

        $this->cacheModels($models, $modelClass);
    }

    /**
     * Retrieve the map of preloaded models.
     *
     * @return array<string, Model> Models keyed by "ClassName_ID" format
     */
    public function getPreloadedModelsMap(): array
    {
        return $this->preloadedModelsMap;
    }

    /**
     * Get statistical information about the search index.
     *
     * Returns counts of total entries, per-model entries, and per-field distribution.
     *
     * @return array{
     *     total_entries: int,
     *     models: array<string, array{count: int, fields: array<string, int>}>
     * }
     */
    public function getStats(): array
    {
        $modelStats = $this->fetchModelStatistics();
        $fieldStats = $this->fetchFieldStatistics();

        return [
            'total_entries' => FuzzyIndex::count(),
            'models' => $this->buildStatisticsOutput($modelStats, $fieldStats),
        ];
    }

    /**
     * Build index structures from raw index entries.
     *
     * @param Collection<int, FuzzyIndex> $indexEntries
     * @return array{
     *     wordIndex: array<string, array<int, array<string, mixed>>>,
     *     itemMap: array<string, array{indexable_type: string, indexable_id: int|string}>,
     *     modelIndex: array<string, array<int, array<string, mixed>>>,
     *     rawEntries: array<int, array<string, mixed>>
     * }
     */
    private function buildIndexStructures(Collection $indexEntries): array
    {
        $wordIndex = [];
        $itemMap = [];
        $modelIndex = [];

        foreach ($indexEntries as $entry) {
            $modelKey = $this->buildModelKey($entry->indexable_type, $entry->indexable_id);

            $matchData = $this->buildMatchData($entry);

            $this->processEntryWords($entry->words, $matchData, $wordIndex);
            $this->updateModelIndex($modelKey, $matchData, $modelIndex);

            if (!isset($itemMap[$modelKey])) {
                $itemMap[$modelKey] = [
                    'indexable_type' => $entry->indexable_type,
                    'indexable_id' => $entry->indexable_id,
                ];
            }
        }

        return [
            'wordIndex' => $wordIndex,
            'itemMap' => $itemMap,
            'modelIndex' => $modelIndex,
            'rawEntries' => $indexEntries->toArray(),
        ];
    }

    /**
     * Create match data structure from index entry.
     *
     * @param FuzzyIndex $entry
     * @return array<string, mixed>
     */
    private function buildMatchData(FuzzyIndex $entry): array
    {
        return [
            'indexable_type' => $entry->indexable_type,
            'indexable_id' => $entry->indexable_id,
            'field' => $entry->field,
            'original_value' => $entry->original_value,
            'normalized_words' => $entry->words,
            'weight' => $entry->weight,
        ];
    }

    /**
     * Process words from an index entry and update word index.
     *
     * @param array<int, string> $words
     * @param array<string, mixed> $matchData
     * @param array<string, array<int, array<string, mixed>>> $wordIndex Reference to word index
     */
    private function processEntryWords(array $words, array $matchData, array &$wordIndex): void
    {
        foreach ($words as $word) {
            if (strlen($word) < 2) {
                continue;
            }

            if (!isset($wordIndex[$word])) {
                $wordIndex[$word] = [];
            }

            $wordIndex[$word][] = $matchData;
        }
    }

    /**
     * Update model index with match data.
     *
     * @param string $modelKey
     * @param array<string, mixed> $matchData
     * @param array<string, array<int, array<string, mixed>>> $modelIndex Reference to model index
     */
    private function updateModelIndex(string $modelKey, array $matchData, array &$modelIndex): void
    {
        if (!isset($modelIndex[$modelKey])) {
            $modelIndex[$modelKey] = [];
        }

        $modelIndex[$modelKey][] = $matchData;
    }

    /**
     * Cache loaded models in internal map for O(1) access.
     *
     * @param Collection<int, Model> $models
     * @param string $modelClass
     */
    private function cacheModels(Collection $models, string $modelClass): void
    {
        $keyName = (new $modelClass())->getKeyName();

        foreach ($models as $model) {
            $key = $this->buildModelKey($modelClass, $model->{$keyName});
            $this->preloadedModelsMap[$key] = $model;
        }
    }

    /**
     * Fetch statistics grouped by model type.
     *
     * @return Collection<string, object{indexable_type: string, count: int}>
     */
    private function fetchModelStatistics(): Collection
    {
        return FuzzyIndex::select('indexable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('indexable_type')
            ->get()
            ->keyBy('indexable_type');
    }

    /**
     * Fetch statistics grouped by model type and field.
     *
     * @return Collection<int, object{indexable_type: string, field: string, count: int}>
     */
    private function fetchFieldStatistics(): Collection
    {
        return FuzzyIndex::select('indexable_type', 'field', DB::raw('COUNT(*) as count'))
            ->groupBy('indexable_type', 'field')
            ->get();
    }

    /**
     * Build final statistics output combining model and field data.
     *
     * @param Collection<string, object{indexable_type: string, count: int}> $modelStats
     * @param Collection<int, object{indexable_type: string, field: string, count: int}> $fieldStats
     * @return array<string, array{count: int, fields: array<string, int>}>
     */
    private function buildStatisticsOutput(Collection $modelStats, Collection $fieldStats): array
    {
        $output = [];

        foreach ($modelStats as $modelClass => $modelStat) {
            $output[$modelClass] = [
                'count' => $modelStat->count,
                'fields' => $fieldStats
                    ->where('indexable_type', $modelClass)
                    ->pluck('count', 'field')
                    ->toArray(),
            ];
        }

        return $output;
    }

    /**
     * Generate a consistent key for model identification.
     *
     * @param string $modelClass
     * @param int|string $modelId
     * @return string
     */
    private function buildModelKey(string $modelClass, $modelId): string
    {
        return $modelClass . '_' . $modelId;
    }
}
