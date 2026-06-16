<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Configuration Value Object for Cache management.
 *
 * Encapsulates all cache-related configuration parameters for the fuzzy search system:
 * - Cache enable/disable flag
 * - Cache key prefix for namespace isolation
 * - TTL (Time To Live) values for different cache types (search, stats)
 * - Invalidation strategies for index operations (create, update, delete)
 *
 * All values are immutable and loaded from Laravel configuration with sensible defaults.
 */
final class CacheConfig implements ConfigInterface
{
    /** Default TTL (in seconds) for general search cache. */
    private const DEFAULT_TTL_SEARCH = 3600;

    /** Default TTL (in seconds) for single model search cache. */
    private const DEFAULT_TTL_SEARCH_IN_MODEL = 3600;

    /** Default TTL (in seconds) for multi-model search cache. */
    private const DEFAULT_TTL_SEARCH_IN_MODELS = 3600;

    /** Default TTL (in seconds) for statistics cache. */
    private const DEFAULT_TTL_STATS = 30;

    /** Extra TTL added to the maximum cache duration for the keys storage. */
    private const DEFAULT_TTL_MAX_EXTRA = 86400;

    /** Prefix for all cache keys to avoid collisions with other applications. */
    private const DEFAULT_PREFIX = 'fuzzy_search:';

    /** Whether caching is enabled by default. */
    private const DEFAULT_ENABLED = true;

    /** Whether to invalidate cache when indexing occurs. */
    private const DEFAULT_INVALIDATE_ON_INDEX = true;

    /** Whether to invalidate cache when a model is updated. */
    private const DEFAULT_INVALIDATE_ON_UPDATE = true;

    /** Whether to invalidate cache when a model is deleted. */
    private const DEFAULT_INVALIDATE_ON_DELETE = true;

    public function __construct(
        private readonly bool $enabled,
        private readonly string $prefix,
        private readonly int $ttlSearch,
        private readonly int $ttlSearchInModel,
        private readonly int $ttlSearchInModels,
        private readonly int $ttlStats,
        private readonly bool $invalidateOnIndex,
        private readonly bool $invalidateOnUpdate,
        private readonly bool $invalidateOnDelete
    ) {}

    /**
     * Create an instance from Laravel configuration.
     *
     * Loads values from 'fuzzy.cache' config key and merges with defaults.
     *
     * @return self Configured instance
     */
    public static function fromConfig(): self
    {
        $config = config('fuzzy.cache', []);

        return new self(
            enabled: (bool) $config['enabled'] ?? self::DEFAULT_ENABLED,
            prefix: $config['prefix'] ?? self::DEFAULT_PREFIX,
            ttlSearch: $config['ttl']['search'] ?? self::DEFAULT_TTL_SEARCH,
            ttlSearchInModel: $config['ttl']['search_in_model'] ?? self::DEFAULT_TTL_SEARCH_IN_MODEL,
            ttlSearchInModels: $config['ttl']['search_in_models'] ?? self::DEFAULT_TTL_SEARCH_IN_MODELS,
            ttlStats: $config['ttl']['stats'] ?? self::DEFAULT_TTL_STATS,
            invalidateOnIndex: $config['invalidation']['on_index'] ?? self::DEFAULT_INVALIDATE_ON_INDEX,
            invalidateOnUpdate: $config['invalidation']['on_update'] ?? self::DEFAULT_INVALIDATE_ON_UPDATE,
            invalidateOnDelete: $config['invalidation']['on_delete'] ?? self::DEFAULT_INVALIDATE_ON_DELETE
        );
    }

    /**
     * Create a default instance with built-in values.
     *
     * Useful for testing or when no configuration is available.
     *
     * @return self Default configured instance
     */
    public static function createDefault(): self
    {
        return new self(
            enabled: self::DEFAULT_ENABLED,
            prefix: self::DEFAULT_PREFIX,
            ttlSearch: self::DEFAULT_TTL_SEARCH,
            ttlSearchInModel: self::DEFAULT_TTL_SEARCH_IN_MODEL,
            ttlSearchInModels: self::DEFAULT_TTL_SEARCH_IN_MODELS,
            ttlStats: self::DEFAULT_TTL_STATS,
            invalidateOnIndex: self::DEFAULT_INVALIDATE_ON_INDEX,
            invalidateOnUpdate: self::DEFAULT_INVALIDATE_ON_UPDATE,
            invalidateOnDelete: self::DEFAULT_INVALIDATE_ON_DELETE
        );
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool True if caching is enabled, false otherwise
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string Prefix string for all cache keys
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get TTL for general search cache.
     *
     * @return int Time to live in seconds
     */
    public function getTtlSearch(): int
    {
        return $this->ttlSearch;
    }

    /**
     * Get TTL for single model search cache.
     *
     * @return int Time to live in seconds
     */
    public function getTtlSearchInModel(): int
    {
        return $this->ttlSearchInModel;
    }

    /**
     * Get TTL for multi-model search cache.
     *
     * @return int Time to live in seconds
     */
    public function getTtlSearchInModels(): int
    {
        return $this->ttlSearchInModels;
    }

    /**
     * Get TTL for statistics cache.
     *
     * @return int Time to live in seconds
     */
    public function getTtlStats(): int
    {
        return $this->ttlStats;
    }

    /**
     * Get the maximum TTL for cache key storage.
     *
     * Calculated as the highest TTL among cache types plus an extra buffer.
     * This ensures stored cache keys outlive all cached data.
     *
     * @return int Maximum time to live in seconds
     */
    public function getMaxTtl(): int
    {
        $maxTtlAmongTypes = max(
            $this->ttlSearch,
            $this->ttlSearchInModel,
            $this->ttlSearchInModels,
            $this->ttlStats
        );

        return $maxTtlAmongTypes + self::DEFAULT_TTL_MAX_EXTRA;
    }

    /**
     * Check if cache should be invalidated during index operations.
     *
     * @return bool True if invalidation on index is enabled
     */
    public function shouldInvalidateOnIndex(): bool
    {
        return $this->invalidateOnIndex;
    }

    /**
     * Check if cache should be invalidated during model updates.
     *
     * @return bool True if invalidation on update is enabled
     */
    public function shouldInvalidateOnUpdate(): bool
    {
        return $this->invalidateOnUpdate;
    }

    /**
     * Check if cache should be invalidated during model deletions.
     *
     * @return bool True if invalidation on delete is enabled
     */
    public function shouldInvalidateOnDelete(): bool
    {
        return $this->invalidateOnDelete;
    }

    /**
     * Get the storage key used to track all cache keys.
     *
     * This key is used to store a list of all cache keys for batch invalidation.
     *
     * @return string Storage key for cache keys tracking
     */
    public function getCacheKeysStorageKey(): string
    {
        return $this->prefix . 'cache_keys';
    }
}
