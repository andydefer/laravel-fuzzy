<?php

declare(strict_types=1);

namespace Fuzzy\Cache;

use Fuzzy\Contracts\CacheStoreInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache implementation.
 *
 * This adapter wraps Laravel's cache system to provide the
 * CacheStoreInterface abstraction.
 */
class LaravelCacheStore implements CacheStoreInterface
{
    private CacheRepository $cache;

    public function __construct(?CacheRepository $cache = null)
    {
        $this->cache = $cache ?? Cache::store();
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        return $this->cache->put($key, $value, $ttl);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->cache->forever($key, $value);
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($key);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->cache->remember($key, $ttl, $callback);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function increment(string $key, int $value = 1): int|false
    {
        return $this->cache->increment($key, $value);
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->cache->decrement($key, $value);
    }
}
