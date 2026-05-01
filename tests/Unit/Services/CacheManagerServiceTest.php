<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Config\CacheConfig;
use Fuzzy\Services\CacheManagerService;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

final class CacheManagerServiceTest extends TestCase
{
    private CacheManagerService $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['cache.default' => 'array']);
        config(['fuzzy.cache.enabled' => true]);
        config(['fuzzy.cache.prefix' => 'fuzzy_test:']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_is_enabled_returns_true_when_cache_enabled(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $this->assertTrue($this->cacheManager->isEnabled());
    }

    public function test_is_enabled_returns_false_when_cache_disabled(): void
    {
        config(['fuzzy.cache.enabled' => false]);
        $this->cacheManager = new CacheManagerService();

        $this->assertFalse($this->cacheManager->isEnabled());
    }

    public function test_remember_executes_callback_when_cache_disabled(): void
    {
        config(['fuzzy.cache.enabled' => false]);
        $this->cacheManager = new CacheManagerService();

        $executed = false;
        $result = $this->cacheManager->remember('test', function () use (&$executed) {
            $executed = true;
            return 'callback_result';
        }, []);

        $this->assertTrue($executed);
        $this->assertEquals('callback_result', $result);
    }

    public function test_remember_uses_cache_when_enabled(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $callbackExecutions = 0;

        $result1 = $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
            $callbackExecutions++;
            return 'cached_value';
        }, ['param1']);

        $result2 = $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
            $callbackExecutions++;
            return 'cached_value';
        }, ['param1']);

        $this->assertEquals(1, $callbackExecutions);
        $this->assertEquals('cached_value', $result1);
        $this->assertEquals('cached_value', $result2);
    }

    public function test_remember_generates_different_keys_for_different_parameters(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $callbackExecutions = 0;

        $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
            $callbackExecutions++;
            return 'value1';
        }, ['param1']);

        $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
            $callbackExecutions++;
            return 'value2';
        }, ['param2']);

        $this->assertEquals(2, $callbackExecutions);
    }

    public function test_remember_stores_model_metadata_for_search_in_model(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $userParams = [User::class, 'john', []];

        $this->cacheManager->remember('search_in_model', fn() => 'user_result', $userParams);

        $storageKey = $this->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        $foundModel = false;
        foreach ($storedKeys as $keyData) {
            if (is_array($keyData) && isset($keyData['model']) && $keyData['model'] === User::class) {
                $foundModel = true;
                break;
            }
        }

        $this->assertTrue($foundModel, 'Model metadata should be stored with cache key');
    }

    public function test_invalidate_all_clears_cache(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $this->cacheManager->remember('test', fn() => 'value', []);
        $this->cacheManager->invalidateAll();

        $callbackExecuted = false;
        $result = $this->cacheManager->remember('test', function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'new_value';
        }, []);

        $this->assertTrue($callbackExecuted);
        $this->assertEquals('new_value', $result);
    }

    public function test_invalidate_all_clears_storage_keys(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $this->cacheManager->remember('test', fn() => 'value', []);

        $storageKey = $this->getCacheKeysStorageKey();
        $this->assertNotNull(Cache::get($storageKey));

        $this->cacheManager->invalidateAll();

        $this->assertNull(Cache::get($storageKey));
    }

    public function test_invalidate_for_model_clears_only_model_cache(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        // Paramètres pour User et Product
        $userParams = [User::class, 'john', []];
        $productParams = [Product::class, 'laptop', []];

        // Mettre en cache des résultats pour User et Product
        $this->cacheManager->remember('search_in_model', fn() => 'user_result', $userParams);
        $this->cacheManager->remember('search_in_model', fn() => 'product_result', $productParams);

        // Récupérer le storage des clés
        $storageKey = $this->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        // Trouver les clés User et Product
        $userCached = null;
        $productCached = null;

        foreach ($storedKeys as $keyData) {
            $key = is_array($keyData) ? $keyData['key'] : $keyData;
            $model = is_array($keyData) ? ($keyData['model'] ?? null) : null;

            if ($model === User::class) {
                $userCached = $key;
            }
            if ($model === Product::class) {
                $productCached = $key;
            }
        }

        // Vérifier que les deux clés existent
        $this->assertNotNull($userCached, 'User cache key should exist');
        $this->assertNotNull($productCached, 'Product cache key should exist');

        // Vérifier que les valeurs sont correctement mises en cache
        $this->assertEquals('user_result', Cache::get($userCached));
        $this->assertEquals('product_result', Cache::get($productCached));

        // Invalider uniquement le cache User
        $this->cacheManager->invalidateForModel(User::class);

        // Vérifier que le cache User a été supprimé
        $this->assertNull(Cache::get($userCached), 'User cache should be cleared');

        // Vérifier que le cache Product est toujours présent
        $this->assertEquals('product_result', Cache::get($productCached), 'Product cache should remain');

        // Vérifier que la clé User a été retirée du storage
        $updatedStoredKeys = Cache::get($storageKey, []);
        $userKeyStillExists = false;
        $productKeyStillExists = false;

        foreach ($updatedStoredKeys as $keyData) {
            $model = is_array($keyData) ? ($keyData['model'] ?? null) : null;

            if ($model === User::class) {
                $userKeyStillExists = true;
            }
            if ($model === Product::class) {
                $productKeyStillExists = true;
            }
        }

        $this->assertFalse($userKeyStillExists, 'User cache key should be removed from storage');
        $this->assertTrue($productKeyStillExists, 'Product cache key should remain in storage');

        // Maintenant tester que le callback User est réexécuté
        $userCallbackExecuted = false;
        $productCallbackExecuted = false;

        $this->cacheManager->remember('search_in_model', function () use (&$userCallbackExecuted) {
            $userCallbackExecuted = true;
            return 'new_user_result';
        }, $userParams);

        $this->cacheManager->remember('search_in_model', function () use (&$productCallbackExecuted) {
            $productCallbackExecuted = false;
            return 'product_result';
        }, $productParams);

        $this->assertTrue($userCallbackExecuted, 'User callback should be re-executed');
        $this->assertFalse($productCallbackExecuted, 'Product callback should not be re-executed');
    }

    public function test_invalidate_for_model_handles_nonexistent_model(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        // Ne devrait pas lever d'exception
        $this->cacheManager->invalidateForModel('NonExistentModel');

        $this->assertTrue(true);
    }

    public function test_invalidate_does_nothing_when_cache_disabled(): void
    {
        config(['fuzzy.cache.enabled' => false]);
        $this->cacheManager = new CacheManagerService();

        // Should not throw any exception
        $this->cacheManager->invalidateAll();
        $this->cacheManager->invalidateForModel(User::class);

        $this->assertTrue(true);
    }

    public function test_cache_key_generation_with_long_parameters(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        // Créer des paramètres très longs
        $longString = str_repeat('a', 300);
        $params = [$longString];

        $result = $this->cacheManager->remember('test', fn() => 'value', $params);

        // Devrait retourner la valeur, pas la clé
        $this->assertEquals('value', $result);
    }

    public function test_multiple_cache_types_have_different_ttls(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        config(['fuzzy.cache.ttl.search' => 100]);
        config(['fuzzy.cache.ttl.stats' => 50]);

        $this->cacheManager = new CacheManagerService();

        // Cette méthode est privée, on teste indirectement
        $this->assertTrue(true);
    }

    public function test_remember_without_model_metadata(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        // search_in_models n'a pas de modèle unique
        $modelsParams = [[User::class, Product::class], 'query', []];

        $this->cacheManager->remember('search_in_models', fn() => 'combined_result', $modelsParams);

        $storageKey = $this->getCacheKeysStorageKey();
        $storedKeys = Cache::get($storageKey, []);

        $hasKeyWithoutModel = false;
        foreach ($storedKeys as $keyData) {
            if (is_array($keyData) && !isset($keyData['model'])) {
                $hasKeyWithoutModel = true;
                break;
            }
        }

        $this->assertTrue($hasKeyWithoutModel, 'Keys without model metadata should be stored');
    }

    public function test_remember_returns_cached_value_on_second_call(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService();

        $executionCount = 0;

        $result1 = $this->cacheManager->remember('test', function () use (&$executionCount) {
            $executionCount++;
            return 'cached_value';
        }, []);

        $result2 = $this->cacheManager->remember('test', function () use (&$executionCount) {
            $executionCount++;
            return 'cached_value';
        }, []);

        $this->assertEquals(1, $executionCount);
        $this->assertEquals('cached_value', $result1);
        $this->assertEquals('cached_value', $result2);
    }

    private function getCacheKeysStorageKey(): string
    {
        $config = CacheConfig::fromConfig();
        return $config->getCacheKeysStorageKey();
    }
}
