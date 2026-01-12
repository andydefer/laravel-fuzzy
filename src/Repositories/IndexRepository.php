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
 * Repository for optimized index operations.
 */
class IndexRepository implements IndexRepositoryInterface
{
    private array $preloadedModelsMap = [];

    /**
     * Get index data for a specific model with optimized query.
     * @return array<string, mixed>
     */
    public function getIndexDataForModel(string $modelClass, array $modelIds = []): array
    {
        $query = FuzzyIndex::forModel($modelClass);

        if ($modelIds !== []) {
            $query->whereIn('indexable_id', $modelIds);
        }

        // Charger les données en une seule requête
        $indexEntries = $query->get(['indexable_type', 'indexable_id', 'field', 'original_value', 'words', 'weight']);

        $wordIndex = [];
        $itemMap = [];
        $modelIndex = []; // NOUVEAU: Index inversé pour éviter O(n²)

        foreach ($indexEntries as $entry) {
            $modelKey = $entry->indexable_type . '_' . $entry->indexable_id;

            foreach ($entry->words as $word) {
                if (strlen($word) >= 2) {
                    if (!isset($wordIndex[$word])) {
                        $wordIndex[$word] = [];
                    }

                    $matchData = [
                        'indexable_type' => $entry->indexable_type,
                        'indexable_id' => $entry->indexable_id,
                        'field' => $entry->field,
                        'original_value' => $entry->original_value,
                        'normalized_words' => $entry->words,
                        'weight' => $entry->weight,
                    ];

                    $wordIndex[$word][] = $matchData;

                    // Construire l'index inversé en même temps
                    if (!isset($modelIndex[$modelKey])) {
                        $modelIndex[$modelKey] = [];
                    }

                    $modelIndex[$modelKey][] = $matchData;
                }
            }

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
            'modelIndex' => $modelIndex, // NOUVEAU: Index inversé
            'rawEntries' => $indexEntries->toArray(),
        ];
    }

    /**
     * Get models in batch to avoid N+1 queries.
     * OPTIMISÉ: Ajout de l'eager loading
     */
    public function getModelsBatch(string $modelClass, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $modelInstance = new $modelClass;

        // Charger avec eager loading des relations configurées
        $eagerLoadRelations = config('fuzzy.eager_load.' . $modelClass, []);

        $query = $modelClass::whereIn($modelInstance->getKeyName(), $ids);

        if (!empty($eagerLoadRelations)) {
            $query->with($eagerLoadRelations);
        }

        return $query->get();
    }

    /**
     * Preload all models from item map.
     */
    public function preloadModels(SearchContext $context): void
    {
        $modelIds = $context->getAllModelIds();

        if ($modelIds === []) {
            $this->preloadedModelsMap = [];
            return;
        }

        // Utiliser getItemMap() au lieu d'accéder directement à la propriété
        $itemMap = $context->getItemMap();
        if ($itemMap === []) {
            $this->preloadedModelsMap = [];
            return;
        }

        $modelClass = $context->getModelClass();

        // Charger tous les modèles en une seule requête
        /** @var Collection<int, Model> $models */
        $models = $this->getModelsBatch($modelClass, $modelIds);

        // Indexer pour un accès O(1)
        $keyName = (new $modelClass)->getKeyName();
        foreach ($models as $model) {
            $key = $modelClass . '_' . $model->{$keyName};
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
