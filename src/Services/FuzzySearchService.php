<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Symfony\Component\Finder\Finder;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\SearchContext;
use Fuzzy\Services\Scoring\ScoringEngine;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

/**
 * Main service for fuzzy search operations.
 */
class FuzzySearchService
{
    /**
     * @param Pipeline $pipeline Laravel pipeline for processing search stages
     * @param StringNormalizer $normalizer Service for normalizing strings and queries
     * @param SimilarityCalculator $similarityCalculator Service for calculating similarity scores
     * @param IndexBuilder $indexBuilder Service for building and managing search indexes
     * @param IndexRepositoryInterface $indexRepository Repository for optimized index operations
     * @param ScoringEngine $scoringEngine Unified scoring engine
     */
    public function __construct(
        protected Pipeline $pipeline,
        protected StringNormalizer $normalizer,
        protected SimilarityCalculator $similarityCalculator,
        protected IndexBuilder $indexBuilder,
        protected IndexRepositoryInterface $indexRepository,
        protected ScoringEngine $scoringEngine
    ) {}

    /**
     * Search across all searchable models.
     */
    public function search(string $query, array $options = []): Collection
    {
        if (!$this->isCacheEnabled()) {
            return $this->performSearch($query, $options);
        }

        $cacheKey = $this->generateCacheKey('search', $query, $options);
        $ttl = config('fuzzy.cache.ttl.search', 3600);

        return $this->cacheRemember($cacheKey, $ttl, function () use ($query, $options): Collection {
            return $this->performSearch($query, $options);
        });
    }

    /**
     * Recherche réelle (sans cache).
     */
    private function performSearch(string $query, array $options = []): Collection
    {
        $searchOptions = SearchOptionsData::fromConfig($options);
        $models = $this->getSearchableModels();

        $allResults = collect();

        foreach ($models as $modelClass) {
            $modelResults = $this->performSearchInModel($modelClass, $query, $options);
            $allResults = $allResults->merge($modelResults);
        }

        return $this->filterAndSortResults($allResults, $searchOptions->minScore);
    }

    /**
     * Search within a specific model.
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        if (!$this->isCacheEnabled()) {
            return $this->performSearchInModel($modelClass, $query, $options);
        }

        $cacheKey = $this->generateCacheKey('search_in_model', $modelClass, $query, $options);
        $ttl = config('fuzzy.cache.ttl.search_in_model', 3600);

        return $this->cacheRemember($cacheKey, $ttl, function () use ($modelClass, $query, $options): Collection {
            return $this->performSearchInModel($modelClass, $query, $options);
        });
    }

    /**
     * Recherche dans un modèle (sans cache).
     */
    private function performSearchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        $this->validateModel($modelClass);
        $searchOptions = SearchOptionsData::fromConfig($options);
        $searchQuery = SearchQuery::create($query, $this->normalizer);

        if ($searchQuery->isEmpty()) {
            return collect();
        }

        $indexData = $this->indexRepository->getIndexDataForModel($modelClass);
        $context = new SearchContext(
            query: $searchQuery,
            options: $searchOptions,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->indexBuilder,
            indexRepository: $this->indexRepository,
            scoringEngine: $this->scoringEngine,
            indexDataArray: $indexData
        );

        $results = $this->pipeline
            ->send($context)
            ->through($this->getPipelineStages())
            ->then(fn(SearchContext $context): array => $context->results);

        return $this->filterAndSortResults(collect($results), $searchOptions->minScore);
    }

    /**
     * Search across multiple specific models.
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection
    {
        if (!$this->isCacheEnabled()) {
            return $this->performSearchInModels($modelClasses, $query, $options);
        }

        $cacheKey = $this->generateCacheKey('search_in_models', $modelClasses, $query, $options);
        $ttl = config('fuzzy.cache.ttl.search_in_models', 3600);

        return $this->cacheRemember($cacheKey, $ttl, function () use ($modelClasses, $query, $options): Collection {
            return $this->performSearchInModels($modelClasses, $query, $options);
        });
    }

    /**
     * Recherche dans plusieurs modèles (sans cache).
     */
    private function performSearchInModels(array $modelClasses, string $query, array $options = []): Collection
    {
        $results = collect();

        foreach ($modelClasses as $modelClass) {
            if ($this->isModelSearchable($modelClass)) {
                $modelResults = $this->performSearchInModel($modelClass, $query, $options);
                $results = $results->merge($modelResults);
            }
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * Index a specific model instance for search.
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);

            if ($this->shouldInvalidateCacheOnIndex()) {
                $this->invalidateCacheForModel(get_class($model));
            }
        }
    }

    /**
     * Update the search index for a model instance.
     */
    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModelFromIndex($model);
        $this->indexModel($model);
    }

    /**
     * Remove a model instance from the search index.
     */
    public function removeModelFromIndex(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();

        FuzzyIndex::forModelInstance($modelType, $modelId)->delete();

        if ($this->shouldInvalidateCacheOnDelete()) {
            $this->invalidateCacheForModel($modelType);
        }
    }

    /**
     * Reindex all searchable models.
     */
    public function reindexAll(): void
    {
        $models = $this->getSearchableModels();

        foreach ($models as $modelClass) {
            $this->reindexModel($modelClass);
        }

        if ($this->shouldInvalidateCacheOnIndex()) {
            $this->invalidateAllCache();
        }
    }

    /**
     * Reindex all instances of a specific model.
     */
    public function reindexModel(string $modelClass): void
    {
        $this->validateModel($modelClass);

        FuzzyIndex::forModel($modelClass)->delete();

        $modelClass::chunk(100, function ($models): void {
            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $this->indexModel($model);
                }
            }
        });

        if ($this->shouldInvalidateCacheOnIndex()) {
            $this->invalidateCacheForModel($modelClass);
        }
    }

    /**
     * Calculate similarity score between two strings.
     */
    public function calculateSimilarity(string $firstString, string $secondString): float
    {
        return $this->similarityCalculator->calculateSimilarity($firstString, $secondString);
    }

    /**
     * Normalize a string for search operations.
     */
    public function normalize(string $string): string
    {
        return $this->normalizer->normalize($string);
    }

    /**
     * Split a string into individual words.
     */
    public function splitIntoWords(string $string): array
    {
        return $this->normalizer->splitIntoWords($string);
    }

    /**
     * Normalize a search query.
     */
    public function normalizeQuery(string $query): string
    {
        return $this->normalizer->normalizeQuery($query);
    }

    /**
     * Get search index statistics.
     */
    public function getStats(): array
    {
        if (!$this->isCacheEnabled()) {
            return $this->indexRepository->getStats();
        }

        $cacheKey = $this->generateCacheKey('stats');
        $ttl = config('fuzzy.cache.ttl.stats', 30); // 30 secondes

        return $this->cacheRemember($cacheKey, $ttl, function (): array {
            return $this->indexRepository->getStats();
        });
    }

    /**
     * Invalide tout le cache de recherche.
     * Solution SAFE: Stocker et supprimer uniquement nos propres clés.
     */
    public function invalidateAllCache(): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        $this->deleteStoredCacheKeys();
    }

    /**
     * Invalide le cache pour un modèle spécifique.
     */
    public function invalidateCacheForModel(string $modelClass): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        $this->deleteCacheKeysForModel($modelClass);
    }

    /**
     * Get all searchable models.
     */
    public function getSearchableModels(): array
    {
        if (!$this->isCacheEnabled()) {
            return $this->fetchSearchableModels();
        }

        $cacheKey = $this->generateCacheKey('searchable_models');
        $ttl = config('fuzzy.cache.ttl.search', 3600);

        return $this->cacheRemember($cacheKey, $ttl, function (): array {
            return $this->fetchSearchableModels();
        });
    }

    private function fetchSearchableModels(): array
    {
        $configuredModels = config('fuzzy.searchable_models', []);

        if (!empty($configuredModels)) {
            return array_filter($configuredModels, function (string $modelClass): bool {
                return $this->isModelSearchable($modelClass);
            });
        }

        return $this->discoverSearchableModels();
    }

    /**
     * Discover models implementing MustFuzzySearch interface.
     */
    private function discoverSearchableModels(): array
    {
        $models = [];
        $finder = new Finder();

        $paths = [
            app_path('Models'),
        ];

        if (config('fuzzy.auto_discovery.enabled')) {
            $paths[] = dirname(__DIR__, 2) . '/tests/Fixtures';
        }

        $finder->files()
            ->in($paths)
            ->name('*.php');


        foreach ($finder as $file) {
            $modelClass = $this->getClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isModelSearchable($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Extract fully qualified class name from a file.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        $namespace = '';
        $className = '';

        if (preg_match('/namespace\s+(.+?);/s', $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/', $content, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            $fullClassName = $namespace . '\\' . $className;
            return class_exists($fullClassName) ? $fullClassName : null;
        }

        return null;
    }

    /**
     * Check if a model implements the MustFuzzySearch interface.
     */
    protected function isModelSearchable(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
    }

    /**
     * Validate that a model implements MustFuzzySearch interface.
     */
    protected function validateModel(string $modelClass): void
    {
        if (!$this->isModelSearchable($modelClass)) {
            throw new ModelNotSearchableException(
                sprintf('Model %s must implement ', $modelClass) . MustFuzzySearch::class
            );
        }
    }

    /**
     * Get the pipeline stages for search processing.
     */
    protected function getPipelineStages(): array
    {
        return config('fuzzy.pipeline.stages', [
            NormalizeQueryStage::class,
            MatchDiscoveryStage::class,
            ScoringStage::class,
            SortAndLimitStage::class,
        ]);
    }

    /**
     * Filter and sort search results.
     */
    private function filterAndSortResults(Collection $results, float $minScore): Collection
    {
        return $results
            ->filter(fn($result): bool => $result !== null && $result->score >= $minScore)
            ->sortByDesc('score')
            ->values();
    }

    // ==================== MÉTHODES CACHE SIMPLIFIÉES ====================

    /**
     * Vérifie si le cache est activé.
     */
    private function isCacheEnabled(): bool
    {
        return config('fuzzy.cache.enabled', true);
    }

    /**
     * Vérifie si le cache doit être invalidé sur indexation.
     */
    private function shouldInvalidateCacheOnIndex(): bool
    {
        return config('fuzzy.cache.invalidation.on_index', true);
    }

    /**
     * Vérifie si le cache doit être invalidé sur suppression.
     */
    private function shouldInvalidateCacheOnDelete(): bool
    {
        return config('fuzzy.cache.invalidation.on_delete', true);
    }

    /**
     * Méthode wrapper pour Cache::remember avec suivi des clés.
     */
    private function cacheRemember(string $key, int $ttl, callable $callback)
    {
        // Stocker la clé pour invalidation future
        $this->storeCacheKey($key);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Génère une clé de cache unique.
     */
    private function generateCacheKey(string $type, string|array ...$params): string
    {
        $prefix = config('fuzzy.cache.prefix', 'fuzzy_search:');

        // Simplification: ne pas faire d'hypothèses sur le contenu
        $hash = md5(json_encode($params));
        $key = sprintf('%s%s:%s', $prefix, $type, $hash);

        // Limiter la longueur de la clé
        if (strlen($key) > 250) {
            return sprintf('%s%s:', $prefix, $type) . md5($key);
        }

        return $key;
    }

    /**
     * Stocke une clé de cache générée pour invalidation future.
     */
    private function storeCacheKey(string $key): void
    {
        $storageKey = $this->getStorageKeyName();
        $storedKeys = Cache::get($storageKey, []);

        if (!in_array($key, $storedKeys, true)) {
            $storedKeys[] = $key;
            // Stocker pour 1 jour de plus que le TTL max
            $maxTtl = max(array_values(config('fuzzy.cache.ttl', []))) + 86400;
            Cache::put($storageKey, $storedKeys, $maxTtl);
        }
    }

    /**
     * Supprime toutes les clés de cache stockées.
     */
    private function deleteStoredCacheKeys(): void
    {
        $storageKey = $this->getStorageKeyName();
        $storedKeys = Cache::get($storageKey, []);

        foreach ($storedKeys as $key) {
            Cache::forget($key);
        }

        Cache::forget($storageKey);
    }

    /**
     * Supprime les clés de cache pour un modèle spécifique.
     */
    private function deleteCacheKeysForModel(string $modelClass): void
    {
        $storageKey = $this->getStorageKeyName();
        $storedKeys = Cache::get($storageKey, []);
        $modelHash = md5($modelClass);

        $keysToDelete = [];
        $keysToKeep = [];

        foreach ($storedKeys as $key) {
            // Si la clé contient le hash du modèle, on la supprime
            if (str_contains($key, $modelHash)) {
                $keysToDelete[] = $key;
            } else {
                $keysToKeep[] = $key;
            }
        }

        // Supprimer les clés concernées
        foreach ($keysToDelete as $key) {
            Cache::forget($key);
        }

        // Mettre à jour la liste stockée
        if ($keysToDelete !== []) {
            $maxTtl = max(array_values(config('fuzzy.cache.ttl', []))) + 86400;
            Cache::put($storageKey, $keysToKeep, $maxTtl);
        }
    }

    /**
     * Retourne le nom de la clé de stockage des clés de cache.
     */
    private function getStorageKeyName(): string
    {
        $prefix = config('fuzzy.cache.prefix', 'fuzzy_search:');
        return $prefix . 'cache_keys';
    }
}
