<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for cache storage abstraction.
 *
 * This interface allows the package to work with Laravel's cache system
 * without tight coupling to the Facade.
 */
interface CacheStoreInterface
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key The cache key
     * @return mixed The cached value, or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string $key The cache key
     * @param mixed $value The value to store
     * @param int $ttl Time to live in seconds
     * @return bool True if successful, false otherwise
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Store an item in the cache indefinitely.
     *
     * @param string $key The cache key
     * @param mixed $value The value to store
     * @return bool True if successful, false otherwise
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Remove an item from the cache.
     *
     * @param string $key The cache key to remove
     * @return bool True if successful, false otherwise
     */
    public function forget(string $key): bool;

    /**
     * Retrieve an item from the cache, or execute a callback and store the result.
     *
     * @param string $key The cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback The callback to execute if cache miss
     * @return mixed The cached or computed value
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key The cache key
     * @return bool True if exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Increment a value in the cache.
     *
     * @param string $key The cache key
     * @param int $value The amount to increment by
     * @return int|false The new value, or false on failure
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Decrement a value in the cache.
     *
     * @param string $key The cache key
     * @param int $value The amount to decrement by
     * @return int|false The new value, or false on failure
     */
    public function decrement(string $key, int $value = 1): int|false;
}
