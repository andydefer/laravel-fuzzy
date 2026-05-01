<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\CacheManagerInterface;
use Fuzzy\Config\CacheConfig;
use Illuminate\Support\Facades\Cache;

class CacheManagerService implements CacheManagerInterface
{
    private const MIN_CACHE_KEY_LENGTH_FOR_HASH = 250;
    private const STATS_CACHE_TYPE = 'stats';

    private CacheConfig $config;

    public function __construct(?CacheConfig $config = null)
    {
        $this->config = $config ?? CacheConfig::fromConfig();
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function remember(string $type, callable $callback, array $parameters): mixed
    {
        if (!$this->config->isEnabled()) {
            return $callback();
        }

        $cacheKey = $this->generateCacheKey($type, $parameters);
        $ttl = $this->getTtlForCacheType($type);

        // Extraire le modèle des paramètres si présent
        $modelClass = $this->extractModelClassFromParameters($parameters);

        return $this->cacheRemember($cacheKey, $ttl, $callback, $modelClass);
    }

    public function invalidateAll(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $this->deleteStoredCacheKeys();
    }

    public function invalidateForModel(string $modelClass): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $this->deleteCacheKeysForModel($modelClass);
    }

    /**
     * {@inheritDoc}
     */
    public function invalidateStatsCache(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $statsKey = $this->generateCacheKey(self::STATS_CACHE_TYPE, []);
        Cache::forget($statsKey);

        // Also remove from stored keys tracking if exists
        $this->removeStatsKeyFromStorage($statsKey);
    }

    private function getTtlForCacheType(string $cacheType): int
    {
        return match ($cacheType) {
            'search' => $this->config->getTtlSearch(),
            'search_in_model' => $this->config->getTtlSearchInModel(),
            'search_in_models' => $this->config->getTtlSearchInModels(),
            self::STATS_CACHE_TYPE => $this->config->getTtlStats(),
            default => $this->config->getTtlSearch(),
        };
    }

    private function generateCacheKey(string $type, array $parameters): string
    {
        $hash = md5(json_encode($parameters));
        $key = sprintf('%s%s:%s', $this->config->getPrefix(), $type, $hash);

        if (strlen($key) > self::MIN_CACHE_KEY_LENGTH_FOR_HASH) {
            return sprintf('%s%s:', $this->config->getPrefix(), $type) . md5($key);
        }

        return $key;
    }

    /**
     * Extract model class from parameters array
     */
    private function extractModelClassFromParameters(array $parameters): ?string
    {
        // Pour search_in_model: [modelClass, query, options]
        if (isset($parameters[0]) && is_string($parameters[0]) && class_exists($parameters[0])) {
            return $parameters[0];
        }

        // Pour search_in_models: [modelClasses, query, options]
        if (isset($parameters[0]) && is_array($parameters[0])) {
            // Pour l'invalidation, on ne stocke pas tous les modèles
            // On retourne null car l'invalidation se fera par modèle individuel
            return null;
        }

        return null;
    }

    private function cacheRemember(string $key, int $ttl, callable $callback, ?string $modelClass = null): mixed
    {
        $this->storeCacheKey($key, $modelClass);
        return Cache::remember($key, $ttl, $callback);
    }

    private function storeCacheKey(string $key, ?string $modelClass = null): void
    {
        $storageKey = $this->config->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        // Structure des données stockées
        $keyData = [
            'key' => $key,
            'created_at' => time(),
        ];

        if ($modelClass !== null) {
            $keyData['model'] = $modelClass;
        }

        // Vérifier si la clé existe déjà
        $keyExists = false;
        foreach ($storedKeys as $existingKeyData) {
            if (is_array($existingKeyData) && $existingKeyData['key'] === $key) {
                $keyExists = true;
                break;
            }
            if (is_string($existingKeyData) && $existingKeyData === $key) {
                $keyExists = true;
                break;
            }
        }

        if (!$keyExists) {
            $storedKeys[] = $keyData;
            Cache::put($storageKey, $storedKeys, $this->config->getMaxTtl());
        }
    }

    private function deleteStoredCacheKeys(): void
    {
        $storageKey = $this->config->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        foreach ($storedKeys as $keyData) {
            $key = is_array($keyData) ? $keyData['key'] : $keyData;
            Cache::forget($key);
        }

        Cache::forget($storageKey);
    }

    private function deleteCacheKeysForModel(string $modelClass): void
    {
        $storageKey = $this->config->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        $keysToDelete = [];
        $keysToKeep = [];

        foreach ($storedKeys as $keyData) {
            $key = is_array($keyData) ? $keyData['key'] : $keyData;
            $keyModel = is_array($keyData) ? ($keyData['model'] ?? null) : null;

            // Si la clé est associée à ce modèle, on la supprime
            if ($keyModel === $modelClass) {
                $keysToDelete[] = $key;
            } else {
                $keysToKeep[] = $keyData;
            }
        }

        // Supprimer les clés du cache
        foreach ($keysToDelete as $key) {
            Cache::forget($key);
        }

        // Mettre à jour le storage
        if ($keysToDelete !== []) {
            Cache::put($storageKey, $keysToKeep, $this->config->getMaxTtl());
        }
    }

    /**
     * Remove stats key from stored keys tracking.
     *
     * @param string $statsKey The stats cache key to remove
     * @return void
     */
    private function removeStatsKeyFromStorage(string $statsKey): void
    {
        $storageKey = $this->config->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        $keysToKeep = [];
        $keyRemoved = false;

        foreach ($storedKeys as $keyData) {
            $key = is_array($keyData) ? $keyData['key'] : $keyData;
            if ($key === $statsKey) {
                $keyRemoved = true;
                continue;
            }
            $keysToKeep[] = $keyData;
        }

        if ($keyRemoved) {
            Cache::put($storageKey, $keysToKeep, $this->config->getMaxTtl());
        }
    }
}
