<?php

declare(strict_types=1);

namespace Fuzzy\Repositories;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for managing fuzzy search index data with optimized database operations.
 *
 * Handles retrieval of index entries, model preloading, and statistics collection.
 * Implements caching strategies to reduce database queries during search operations.
 *
 * @package Fuzzy\Repositories
 */
class IndexRepository implements IndexRepositoryInterface
{
    /**
     * Minimum word length to include in word index.
     * Words shorter than this are filtered out.
     */
    private const MIN_WORD_LENGTH = 2;

    /**
     * Cache of preloaded models keyed by model type and ID.
     *
     * @var array<string, Model>
     */
    private array $preloadedModelsMap = [];

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getModelsBatch(string $modelClass, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $modelInstance = new $modelClass();
        $query = $modelClass::whereIn($modelInstance->getKeyName(), $ids);

        $eagerLoadRelations = $this->getEagerLoadRelations($modelClass);

        if ($eagerLoadRelations !== []) {
            $query->with($eagerLoadRelations);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function preloadModels(SearchContextInterface $context): void
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
     * {@inheritDoc}
     */
    public function getPreloadedModelsMap(): array
    {
        return $this->preloadedModelsMap;
    }

    /**
     * {@inheritDoc}
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
     * Get eager load relations for a specific model from configuration.
     *
     * @param string $modelClass Fully qualified model class name
     * @return array<int, string> List of relation names to eager load
     */
    private function getEagerLoadRelations(string $modelClass): array
    {
        return config('fuzzy.eager_load.' . $modelClass, []);
    }

    /**
     * Build index structures from raw index entries.
     *
     * @param Collection<int, FuzzyIndex> $indexEntries Collection of index entries
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

            $this->addWordIndexEntries($entry->words, $matchData, $wordIndex);
            $this->addToModelIndex($modelKey, $matchData, $modelIndex);
            $this->addToItemMap($modelKey, $entry, $itemMap);
        }

        return [
            'wordIndex' => $wordIndex,
            'itemMap' => $itemMap,
            'modelIndex' => $modelIndex,
            'rawEntries' => $indexEntries->toArray(),
        ];
    }

    /**
     * Create match data structure from an index entry.
     *
     * @param FuzzyIndex $entry The index entry
     * @return array<string, mixed> Match data structure
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
     * Add word entries to the word index.
     *
     * Filters out words that are too short.
     *
     * @param array<int, string> $words Array of words from the index entry
     * @param array<string, mixed> $matchData Match data to associate with each word
     * @param array<string, array<int, array<string, mixed>>> $wordIndex Reference to word index (modified in place)
     */
    private function addWordIndexEntries(array $words, array $matchData, array &$wordIndex): void
    {
        foreach ($words as $word) {
            if (strlen($word) < self::MIN_WORD_LENGTH) {
                continue;
            }

            if (!isset($wordIndex[$word])) {
                $wordIndex[$word] = [];
            }

            $wordIndex[$word][] = $matchData;
        }
    }

    /**
     * Add match data to the model index.
     *
     * @param string $modelKey Unique model identifier (type_id format)
     * @param array<string, mixed> $matchData Match data to add
     * @param array<string, array<int, array<string, mixed>>> $modelIndex Reference to model index (modified in place)
     */
    private function addToModelIndex(string $modelKey, array $matchData, array &$modelIndex): void
    {
        if (!isset($modelIndex[$modelKey])) {
            $modelIndex[$modelKey] = [];
        }

        $modelIndex[$modelKey][] = $matchData;
    }

    /**
     * Add a model entry to the item map if not already present.
     *
     * @param string $modelKey Unique model identifier
     * @param FuzzyIndex $entry The index entry containing model information
     * @param array<string, array{indexable_type: string, indexable_id: int|string}> $itemMap Reference to item map (modified in place)
     */
    private function addToItemMap(string $modelKey, FuzzyIndex $entry, array &$itemMap): void
    {
        if (!isset($itemMap[$modelKey])) {
            $itemMap[$modelKey] = [
                'indexable_type' => $entry->indexable_type,
                'indexable_id' => $entry->indexable_id,
            ];
        }
    }

    /**
     * Cache loaded models in internal map for O(1) access.
     *
     * @param Collection<int, Model> $models Collection of loaded models
     * @param string $modelClass The model class name
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
     * @param string $modelClass Fully qualified model class name
     * @param int|string $modelId Model identifier
     * @return string Unique key in format "class_id"
     */
    private function buildModelKey(string $modelClass, int|string $modelId): string
    {
        return $modelClass . '_' . $modelId;
    }
}
