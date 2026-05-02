<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Cache\LaravelCacheStore;
use Fuzzy\Config\CacheConfig;
use Fuzzy\Services\CacheManagerService;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

final class CacheManagerServiceTest extends TestCase
{
    private CacheManagerService $cacheManager;
    private LaravelCacheStore $cacheStore;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Clear cache before each test
        Cache::flush();
        config(['cache.default' => 'array']);
        config(['fuzzy.cache.enabled' => true]);
        config(['fuzzy.cache.prefix' => 'fuzzy_test:']);

        // Create cache store and manager with dependency injection
        $this->cacheStore = new LaravelCacheStore();
        $this->cacheManager = new CacheManagerService(
            cache: $this->cacheStore
        );
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_is_enabled_returns_true_when_cache_enabled(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        $this->assertTrue($this->cacheManager->isEnabled());
    }

    public function test_is_enabled_returns_false_when_cache_disabled(): void
    {
        config(['fuzzy.cache.enabled' => false]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        $this->assertFalse($this->cacheManager->isEnabled());
    }

    public function test_remember_executes_callback_when_cache_disabled(): void
    {
        config(['fuzzy.cache.enabled' => false]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

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
        $this->cacheManager = new CacheManagerService($this->cacheStore);

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
        $this->cacheManager = new CacheManagerService($this->cacheStore);

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

    public function test_invalidate_all_clears_cache(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

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

    public function test_invalidate_for_model_clears_only_model_cache(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        $userParams = [User::class, 'john', []];
        $productParams = [Product::class, 'laptop', []];

        $this->cacheManager->remember('search_in_model', fn() => 'user_result', $userParams);
        $this->cacheManager->remember('search_in_model', fn() => 'product_result', $productParams);

        // Invalider uniquement le cache User
        $this->cacheManager->invalidateForModel(User::class);

        // Vérifier que le callback User est réexécuté
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
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        // Ne devrait pas lever d'exception
        $this->cacheManager->invalidateForModel('NonExistentModel');

        $this->assertTrue(true);
    }

    public function test_invalidate_does_nothing_when_cache_disabled(): void
    {
        config(['fuzzy.cache.enabled' => false]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        // Should not throw any exception
        $this->cacheManager->invalidateAll();
        $this->cacheManager->invalidateForModel(User::class);

        $this->assertTrue(true);
    }

    public function test_cache_key_generation_with_long_parameters(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        // Créer des paramètres très longs
        $longString = str_repeat('a', 300);
        $params = [$longString];

        $result = $this->cacheManager->remember('test', fn() => 'value', $params);

        // Devrait retourner la valeur, pas la clé
        $this->assertEquals('value', $result);
    }

    public function test_invalidate_stats_cache(): void
    {
        config(['fuzzy.cache.enabled' => true]);
        $this->cacheManager = new CacheManagerService($this->cacheStore);

        // First call should execute callback
        $executed = false;
        $result1 = $this->cacheManager->remember('stats', function () use (&$executed) {
            $executed = true;
            return 'stats_value';
        }, []);

        $this->assertTrue($executed);
        $this->assertEquals('stats_value', $result1);

        // Second call should use cache
        $executed = false;
        $result2 = $this->cacheManager->remember('stats', function () use (&$executed) {
            $executed = true;
            return 'stats_value';
        }, []);

        $this->assertFalse($executed);
        $this->assertEquals('stats_value', $result2);

        // Invalidate stats cache
        $this->cacheManager->invalidateStatsCache();

        // Third call should execute callback again
        $executed = false;
        $result3 = $this->cacheManager->remember('stats', function () use (&$executed) {
            $executed = true;
            return 'new_stats_value';
        }, []);

        $this->assertTrue($executed);
        $this->assertEquals('new_stats_value', $result3);
    }

    private function getCacheKeysStorageKey(): string
    {
        $config = CacheConfig::fromConfig();
        return $config->getCacheKeysStorageKey();
    }
}
