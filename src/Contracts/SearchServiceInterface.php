<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for the main fuzzy search service.
 *
 * Defines the contract for search orchestration across all searchable models.
 * 
 * This service acts as a facade that delegates to specialized services.
 * The public properties provide direct access to underlying services
 * for advanced operations (cache management, index management, etc.).
 *
 * @package Fuzzy\Contracts
 */
interface SearchServiceInterface
{
    /**
     * Get the cache manager instance.
     *
     * Provides access to cache operations for advanced cache management.
     *
     * @return CacheManagerInterface
     */
    public function getCacheManager(): CacheManagerInterface;

    /**
     * Get the model discovery instance.
     *
     * Provides access to model discovery for advanced model operations.
     *
     * @return ModelDiscoveryInterface
     */
    public function getModelDiscovery(): ModelDiscoveryInterface;

    /**
     * Get the index manager instance.
     *
     * Provides access to index operations for advanced index management.
     *
     * @return IndexManagerInterface
     */
    public function getIndexManager(): IndexManagerInterface;

    /**
     * Get the search processor instance.
     *
     * Provides access to search processor for advanced search operations.
     *
     * @return SearchProcessorInterface
     */
    public function getSearchProcessor(): SearchProcessorInterface;

    /**
     * Search across all searchable models.
     *
     * @param string $query The search query string
     * @param array<string, mixed> $options Search options
     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
     */
    public function search(string $query, array $options = []): Collection;

    /**
     * Search within a specific model.
     *
     * @param string $modelClass The fully qualified model class name
     * @param string $query The search query string
     * @param array<string, mixed> $options Search options
     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection;

    /**
     * Search across multiple specific models.
     *
     * @param array<int, string> $modelClasses Array of fully qualified model class names
     * @param string $query The search query string
     * @param array<string, mixed> $options Search options
     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection;
}
