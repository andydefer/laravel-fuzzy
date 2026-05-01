<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\CacheManagerInterface;
use Fuzzy\Contracts\IndexManagerInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\SearchProcessorInterface;
use Fuzzy\Contracts\SearchServiceInterface;
use Illuminate\Support\Collection;

/**
 * Main service for fuzzy search operations.
 *
 * This service is responsible ONLY for search orchestration.
 * It delegates to specialized services and exposes their public methods
 * directly via getters, eliminating unnecessary wrappers.
 *
 * @package Fuzzy\Services
 */
class FuzzySearchService implements SearchServiceInterface
{
    /**
     * Constructor.
     *
     * @param CacheManagerInterface $cacheManager Handles caching of search results and statistics
     * @param ModelDiscoveryInterface $modelDiscovery Discovers and validates searchable models
     * @param IndexManagerInterface $indexManager Manages index entries for searchable models
     * @param SearchProcessorInterface $searchProcessor Processes search requests through the pipeline
     */
    public function __construct(
        private readonly CacheManagerInterface $cacheManager,
        private readonly ModelDiscoveryInterface $modelDiscovery,
        private readonly IndexManagerInterface $indexManager,
        private readonly SearchProcessorInterface $searchProcessor
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getCacheManager(): CacheManagerInterface
    {
        return $this->cacheManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getModelDiscovery(): ModelDiscoveryInterface
    {
        return $this->modelDiscovery;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexManager(): IndexManagerInterface
    {
        return $this->indexManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchProcessor(): SearchProcessorInterface
    {
        return $this->searchProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query, array $options = []): Collection
    {
        return $this->cacheManager->remember(
            type: 'search',
            callback: fn() => $this->executeSearch($query, $options),
            parameters: [$query, $options]
        );
    }

    /**
     * Execute search without caching.
     *
     * @param string $query The search query string
     * @param array<string, mixed> $options Search options
     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
     */
    private function executeSearch(string $query, array $options = []): Collection
    {
        $models = $this->modelDiscovery->getSearchableModels();
        return $this->searchProcessor->search($query, $models, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        return $this->cacheManager->remember(
            type: 'search_in_model',
            callback: fn() => $this->searchProcessor->searchInModel($modelClass, $query, $options),
            parameters: [$modelClass, $query, $options]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection
    {
        return $this->cacheManager->remember(
            type: 'search_in_models',
            callback: fn() => $this->searchProcessor->searchInModels($modelClasses, $query, $options),
            parameters: [$modelClasses, $query, $options]
        );
    }
}
