<?php

declare(strict_types=1);

namespace Fuzzy\Repositories;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\SearchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for optimized index operations.
 */
class IndexRepository implements IndexRepositoryInterface
{
    private array $preloadedModelsMap = [];

    /**
     * Get index data for a specific model with optimized query.
     */
    public function getIndexDataForModel(string $modelClass, array $modelIds = []): array
    {
        $query = FuzzyIndex::forModel($modelClass);

        if (!empty($modelIds)) {
            $query->whereIn('indexable_id', $modelIds);
        }

        // Charger les données en une seule requête
        $indexEntries = $query->get(['indexable_type', 'indexable_id', 'field', 'original_value', 'words', 'weight']);

        $wordIndex = [];
        $itemMap = [];

        foreach ($indexEntries as $entry) {
            $this->processIndexEntry($entry, $wordIndex, $itemMap);
        }

        return [
            'wordIndex' => $wordIndex,
            'itemMap' => $itemMap,
            'rawEntries' => $indexEntries->toArray(),
        ];
    }

    /**
     * Get models in batch to avoid N+1 queries.
     */
    public function getModelsBatch(string $modelClass, array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return $modelClass::whereIn((new $modelClass)->getKeyName(), $ids)->get();
    }

    /**
     * Preload all models from item map.
     */
    public function preloadModels(SearchContext $context): void
    {
        $modelIds = $context->getAllModelIds();

        if (empty($modelIds)) {
            $this->preloadedModelsMap = [];
            return;
        }
        /** @var Collection<int, Model> $models  */
        $models = $this->getModelsBatch($context->modelClass, $modelIds);

        foreach ($models as $model) {
            $key = $context->modelClass . '_' . $model->getKey();
            $this->preloadedModelsMap[$key] = $model;
        }
    }

    /**
     * Get preloaded models map.
     */
    public function getPreloadedModelsMap(): array
    {
        return $this->preloadedModelsMap;
    }

    /**
     * Process a single index entry.
     */
    private function processIndexEntry($entry, array &$wordIndex, array &$itemMap): void
    {
        foreach ($entry->words as $word) {
            if (strlen($word) >= 2) {
                if (!isset($wordIndex[$word])) {
                    $wordIndex[$word] = [];
                }

                $wordIndex[$word][] = [
                    'indexable_type' => $entry->indexable_type,
                    'indexable_id' => $entry->indexable_id,
                    'field' => $entry->field,
                    'original_value' => $entry->original_value,
                    'normalized_words' => $entry->words,
                    'weight' => $entry->weight,
                ];
            }
        }

        $key = $entry->indexable_type . '_' . $entry->indexable_id;
        if (!isset($itemMap[$key])) {
            $itemMap[$key] = [
                'indexable_type' => $entry->indexable_type,
                'indexable_id' => $entry->indexable_id,
            ];
        }
    }

    /**
     * Get index statistics with optimized queries.
     */
    public function getStats(): array
    {
        $stats = [
            'total_entries' => FuzzyIndex::count(),
            'models' => [],
        ];

        // Utiliser une seule requête groupée
        $modelStats = FuzzyIndex::select('indexable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('indexable_type')
            ->get()
            ->keyBy('indexable_type');

        $fieldStats = FuzzyIndex::select('indexable_type', 'field', DB::raw('COUNT(*) as count'))
            ->groupBy('indexable_type', 'field')
            ->get();

        foreach ($modelStats as $modelClass => $modelStat) {
            $stats['models'][$modelClass] = [
                'count' => $modelStat->count,
                'fields' => $fieldStats->where('indexable_type', $modelClass)
                    ->pluck('count', 'field')
                    ->toArray(),
            ];
        }

        return $stats;
    }
}
