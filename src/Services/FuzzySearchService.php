<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\SearchContext;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\RelevanceScoringStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;
use Fuzzy\ValueObjects\SearchQuery;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Main service for fuzzy search operations.
 *
 * Provides search capabilities across searchable models with caching,
 * indexing, and similarity calculations.
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
     *
     * @param string $query The search query
     * @param array<string, mixed> $options Search options to override defaults
     * @return Collection<array-key, mixed> Collection of search results with scores
     */
    public function search(string $query, array $options = []): Collection
    {
        return $this->executeWithCache(
            cacheType: 'search',
            callback: fn() => $this->executeSearch($query, $options),
            parameters: [$query, $options]
        );
    }

    /**
     * Execute search without caching.
     *
     * @param string $query The search query
     * @param array<string, mixed> $options Search options
     * @return Collection<array-key, mixed> Collection of search results
     */
    private function executeSearch(string $query, array $options = []): Collection
    {
        $searchOptions = SearchOptionsData::fromConfig($options);
        $models = $this->getSearchableModels();

        $allResults = collect();

        foreach ($models as $modelClass) {
            $modelResults = $this->searchInModelWithoutCache($modelClass, $query, $options);
            $allResults = $allResults->merge($modelResults);
        }

        return $this->filterAndSortResults($allResults, $searchOptions->minScore);
    }

    /**
     * Search within a specific model.
     *
     * @param string $modelClass Fully qualified model class name
     * @param string $query The search query
     * @param array<string, mixed> $options Search options
     * @return Collection<array-key, mixed> Search results for the specified model
     * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        return $this->executeWithCache(
            cacheType: 'search_in_model',
            callback: fn() => $this->searchInModelWithoutCache($modelClass, $query, $options),
            parameters: [$modelClass, $query, $options]
        );
    }

    /**
     * Search within a model without caching.
     *
     * @param string $modelClass Fully qualified model class name
     * @param string $query The search query
     * @param array<string, mixed> $options Search options
     * @return Collection<array-key, mixed> Search results
     * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
     */
    private function searchInModelWithoutCache(string $modelClass, string $query, array $options = []): Collection
    {
        $this->validateModel($modelClass);
        $searchOptions = SearchOptionsData::fromConfig($options);
        $searchQuery = SearchQuery::create($query, $this->normalizer);

        if ($searchQuery->isEmpty()) {
            return collect();
        }

        $indexData = $this->indexRepository->getIndexDataForModel($modelClass);
        $context = $this->createSearchContext($searchQuery, $searchOptions, $indexData);

        $results = $this->processSearchPipeline($context);

        return $this->filterAndSortResults(collect($results), $searchOptions->minScore);
    }

    /**
     * Create a search context for pipeline processing.
     *
     * @param SearchQuery $searchQuery Normalized search query
     * @param SearchOptionsData $searchOptions Search configuration
     * @param array<string, mixed> $indexData Preloaded index data
     * @return SearchContext Configured search context
     */
    private function createSearchContext(
        SearchQuery $searchQuery,
        SearchOptionsData $searchOptions,
        array $indexData
    ): SearchContext {
        return new SearchContext(
            query: $searchQuery,
            options: $searchOptions,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->indexBuilder,
            indexRepository: $this->indexRepository,
            scoringEngine: $this->scoringEngine,
            indexDataArray: $indexData
        );
    }

    /**
     * Execute the search pipeline with the given context.
     *
     * @param SearchContext $context Search context
     * @return array<int, mixed> Raw search results
     */
    private function processSearchPipeline(SearchContext $context): array
    {
        return $this->pipeline
            ->send($context)
            ->through($this->getPipelineStages())
            ->then(fn(SearchContext $context): array => $context->results);
    }

    /**
     * Search across multiple specific models.
     *
     * @param array<int, string> $modelClasses Array of fully qualified model class names
     * @param string $query The search query
     * @param array<string, mixed> $options Search options
     * @return Collection<array-key, mixed> Combined search results
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection
    {
        return $this->executeWithCache(
            cacheType: 'search_in_models',
            callback: fn() => $this->searchInModelsWithoutCache($modelClasses, $query, $options),
            parameters: [$modelClasses, $query, $options]
        );
    }

    /**
     * Search across multiple models without caching.
     *
     * @param array<int, string> $modelClasses Array of model class names
     * @param string $query The search query
     * @param array<string, mixed> $options Search options
     * @return Collection<array-key, mixed> Combined search results
     */
    private function searchInModelsWithoutCache(array $modelClasses, string $query, array $options = []): Collection
    {
        $results = collect();

        foreach ($modelClasses as $modelClass) {
            if ($this->isModelSearchable($modelClass)) {
                $modelResults = $this->searchInModelWithoutCache($modelClass, $query, $options);
                $results = $results->merge($modelResults);
            }
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * Index a specific model instance for search.
     *
     * @param MustFuzzySearch $model The model instance to index
     * @return void
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
     *
     * @param MustFuzzySearch $model The model instance to update
     * @return void
     */
    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModelFromIndex($model);
        $this->indexModel($model);
    }

    /**
     * Remove a model instance from the search index.
     *
     * @param MustFuzzySearch $model The model instance to remove
     * @return void
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
     *
     * @return void
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
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
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
     * Get precise indexing statistics for a specific model.
     *
     * @param string $modelClass Fully qualified model class name
     * @return array{
     *     total_records: int,
     *     indexable_records: int,
     *     indexed_entries: int,
     *     estimated_indexed_models: int,
     *     fields_per_model: int,
     *     coverage_percentage: float
     * }
     * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
     */
    public function getPreciseModelStats(string $modelClass): array
    {
        $this->validateModel($modelClass);

        $stats = $this->getStats();
        $indexedEntries = $stats['models'][$modelClass]['count'] ?? 0;

        $modelInstance = new $modelClass();
        $searchableFields = $modelInstance->getSearchableFields();
        $fieldsPerModel = count($searchableFields);

        $totalRecords = 0;
        $indexableRecords = 0;

        $modelClass::chunk(1000, function ($models) use (&$totalRecords, &$indexableRecords) {
            $totalRecords += count($models);

            /** @var \Fuzzy\Contracts\MustFuzzySearch $model */
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
            ? round(($estimatedIndexedModels / $indexableRecords) * 100, 1)
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

    /**
     * Calculate similarity score between two strings.
     *
     * @param string $firstString First string to compare
     * @param string $secondString Second string to compare
     * @return float Similarity score between 0 and 1
     */
    public function calculateSimilarity(string $firstString, string $secondString): float
    {
        return $this->similarityCalculator->calculateSimilarity($firstString, $secondString);
    }

    /**
     * Normalize a string for search operations.
     *
     * @param string $string String to normalize
     * @return string Normalized string
     */
    public function normalize(string $string): string
    {
        return $this->normalizer->normalize($string);
    }

    /**
     * Split a string into individual words.
     *
     * @param string $string String to split
     * @return array<int, string> Array of words
     */
    public function splitIntoWords(string $string): array
    {
        return $this->normalizer->splitIntoWords($string);
    }

    /**
     * Normalize a search query.
     *
     * @param string $query Search query to normalize
     * @return string Normalized query
     */
    public function normalizeQuery(string $query): string
    {
        return $this->normalizer->normalizeQuery($query);
    }

    /**
     * Get search index statistics.
     *
     * @return array<string, mixed> Statistics about the search index
     */
    public function getStats(): array
    {
        return $this->executeWithCache(
            cacheType: 'stats',
            callback: fn() => $this->indexRepository->getStats(),
            parameters: []
        );
    }

    /**
     * Invalidate all search cache.
     *
     * @return void
     */
    public function invalidateAllCache(): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        $this->deleteStoredCacheKeys();
    }

    /**
     * Invalidate cache for a specific model.
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
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
     *
     * @return array<int, string> Array of fully qualified model class names
     */
    public function getSearchableModels(): array
    {
        return $this->executeWithCache(
            cacheType: 'searchable_models',
            callback: fn() => $this->fetchSearchableModels(),
            parameters: []
        );
    }

    /**
     * Fetch searchable models from configuration or auto-discovery.
     *
     * @return array<int, string> Array of model class names
     */
    private function fetchSearchableModels(): array
    {
        $configuredModels = config('fuzzy.searchable_models', []);

        if (!empty($configuredModels)) {
            return $this->filterValidModels($configuredModels);
        }

        return $this->discoverSearchableModels();
    }

    /**
     * Filter array to only include valid searchable models.
     *
     * @param array<int, string> $modelClasses Array of model class names
     * @return array<int, string> Filtered array of valid models
     */
    private function filterValidModels(array $modelClasses): array
    {
        return array_filter($modelClasses, function (string $modelClass): bool {
            return $this->isModelSearchable($modelClass);
        });
    }

    /**
     * Discover models implementing MustFuzzySearch interface.
     *
     * @return array<int, string> Array of discovered model class names
     */
    private function discoverSearchableModels(): array
    {
        $models = [];
        $finder = new Finder();

        $paths = $this->getDiscoveryPaths();

        $finder->files()
            ->in($paths)
            ->name('*.php');

        foreach ($finder as $file) {
            $modelClass = $this->extractClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isModelSearchable($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Get paths for model discovery.
     *
     * @return array<int, string> Array of directory paths
     */
    private function getDiscoveryPaths(): array
    {
        $paths = [
            app_path('Models'),
        ];

        if (config('fuzzy.auto_discovery.enabled')) {
            $paths[] = dirname(__DIR__, 2) . '/tests/Fixtures';
        }

        return $paths;
    }

    /**
     * Extract fully qualified class name from a file.
     *
     * @param string $filePath Path to PHP file
     * @return string|null Fully qualified class name or null if not found
     */
    private function extractClassNameFromFile(string $filePath): ?string
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
     *
     * @param string $modelClass Fully qualified model class name
     * @return bool True if model is searchable
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
     *
     * @param string $modelClass Fully qualified model class name
     * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
     * @return void
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
     *
     * @return array<int, string> Array of pipeline stage class names
     */
    protected function getPipelineStages(): array
    {
        return config('fuzzy.pipeline.stages', [
            NormalizeQueryStage::class,
            MatchDiscoveryStage::class,
            ScoringStage::class,
            RelevanceScoringStage::class,
            SortAndLimitStage::class,
        ]);
    }

    /**
     * Filter and sort search results.
     *
     * @param Collection<array-key, mixed> $results Collection of search results
     * @param float $minScore Minimum score threshold
     * @return Collection<array-key, mixed> Filtered and sorted results
     */
    private function filterAndSortResults(Collection $results, float $minScore): Collection
    {
        return $results
            ->filter(fn($result): bool => $result !== null && $result->score >= $minScore)
            ->sortByDesc('score')
            ->values();
    }

    /**
     * Check if cache is enabled.
     *
     * @return bool True if caching is enabled
     */
    private function isCacheEnabled(): bool
    {
        return config('fuzzy.cache.enabled', true);
    }

    /**
     * Check if cache should be invalidated on index operations.
     *
     * @return bool True if cache should be invalidated
     */
    private function shouldInvalidateCacheOnIndex(): bool
    {
        return config('fuzzy.cache.invalidation.on_index', true);
    }

    /**
     * Check if cache should be invalidated on delete operations.
     *
     * @return bool True if cache should be invalidated
     */
    private function shouldInvalidateCacheOnDelete(): bool
    {
        return config('fuzzy.cache.invalidation.on_delete', true);
    }

    /**
     * Execute operation with caching.
     *
     * @param string $cacheType Type of cache operation
     * @param callable $callback Operation to execute
     * @param array<int, mixed> $parameters Parameters for cache key generation
     * @return mixed Result of the operation
     */
    private function executeWithCache(string $cacheType, callable $callback, array $parameters)
    {
        if (!$this->isCacheEnabled()) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($cacheType, ...$parameters);
        $ttl = config("fuzzy.cache.ttl.{$cacheType}", 3600);

        return $this->cacheRemember($cacheKey, $ttl, $callback);
    }

    /**
     * Generate a unique cache key.
     *
     * @param string $type Type of cache key
     * @param string|array<int, mixed> ...$parameters Parameters to include in key
     * @return string Generated cache key
     */
    private function generateCacheKey(string $type, string|array ...$parameters): string
    {
        $prefix = config('fuzzy.cache.prefix', 'fuzzy_search:');
        $hash = md5(json_encode($parameters));
        $key = sprintf('%s%s:%s', $prefix, $type, $hash);

        if (strlen($key) > 250) {
            return sprintf('%s%s:', $prefix, $type) . md5($key);
        }

        return $key;
    }

    /**
     * Cache operation with key tracking.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Operation to cache
     * @return mixed Result of the operation
     */
    private function cacheRemember(string $key, int $ttl, callable $callback)
    {
        $this->storeCacheKey($key);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Store a cache key for future invalidation.
     *
     * @param string $key Cache key to store
     * @return void
     */
    private function storeCacheKey(string $key): void
    {
        $storageKey = $this->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        if (!in_array($key, $storedKeys, true)) {
            $storedKeys[] = $key;
            $maxTtl = max(array_values(config('fuzzy.cache.ttl', []))) + 86400;
            Cache::put($storageKey, $storedKeys, $maxTtl);
        }
    }

    /**
     * Delete all stored cache keys.
     *
     * @return void
     */
    private function deleteStoredCacheKeys(): void
    {
        $storageKey = $this->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        foreach ($storedKeys as $key) {
            Cache::forget($key);
        }

        Cache::forget($storageKey);
    }

    /**
     * Delete cache keys for a specific model.
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     */
    private function deleteCacheKeysForModel(string $modelClass): void
    {
        $storageKey = $this->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);
        $modelHash = md5($modelClass);

        $keysToDelete = [];
        $keysToKeep = [];

        foreach ($storedKeys as $key) {
            if (str_contains($key, $modelHash)) {
                $keysToDelete[] = $key;
            } else {
                $keysToKeep[] = $key;
            }
        }

        foreach ($keysToDelete as $key) {
            Cache::forget($key);
        }

        if ($keysToDelete !== []) {
            $maxTtl = max(array_values(config('fuzzy.cache.ttl', []))) + 86400;
            Cache::put($storageKey, $keysToKeep, $maxTtl);
        }
    }

    /**
     * Get the storage key name for cache keys tracking.
     *
     * @return string Storage key name
     */
    private function getCacheKeysStorageKey(): string
    {
        $prefix = config('fuzzy.cache.prefix', 'fuzzy_search:');
        return $prefix . 'cache_keys';
    }
}
