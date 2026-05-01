<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for cache management in the fuzzy search system.
 *
 * Defines the contract for caching search results, index queries,
 * and statistics. Implementations should handle cache key generation,
 * storage, TTL management, and smart invalidation strategies.
 *
 * @package Fuzzy\Contracts
 */
interface CacheManagerInterface
{
    /**
     * Retrieve a cached value or execute a callback to store it.
     *
     * Implements the "remember" pattern: returns the cached value if it exists
     * and is valid; otherwise executes the callback, caches its result, and
     * returns it.
     *
     * @param string $type Cache type identifier (e.g., 'search', 'stats')
     * @param callable $callback Callback to execute when cache is missing
     * @param array $parameters Parameters used to generate the cache key
     * @return mixed The cached or freshly computed value
     */
    public function remember(string $type, callable $callback, array $parameters): mixed;

    /**
     * Invalidate all cache entries managed by this service.
     *
     * Clears every cache key that was stored through the cache keys tracking
     * system. This is useful after bulk operations like reindexing all models.
     *
     * @return void
     */
    public function invalidateAll(): void;

    /**
     * Invalidate all cache entries associated with a specific model.
     *
     * Removes cached data that depends on the given model, such as search
     * results for queries that included this model type.
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     */
    public function invalidateForModel(string $modelClass): void;

    /**
     * Invalidate the statistics cache.
     *
     * Clears only the cached statistics data without affecting other cached data.
     * This is useful after index modifications where statistics need to be refreshed
     * but search results cache should remain valid.
     *
     * @return void
     */
    public function invalidateStatsCache(): void;

    /**
     * Check if caching is currently enabled.
     *
     * @return bool True if caching is active, false otherwise
     */
    public function isEnabled(): bool;
}
