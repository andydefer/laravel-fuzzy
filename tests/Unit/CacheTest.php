<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit;

use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

final class CacheTest extends TestCase
{
    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();

        $this->createTestData();
    }

    /**
     * Create minimal test data and reindex.
     */
    protected function createTestData(): void
    {
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();

        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'admin',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'type' => 'user',
        ]);

        Product::create([
            'name' => 'Laptop Pro',
            'description' => 'High-end laptop',
            'price' => 1299.99,
        ]);

        app(FuzzySearchService::class)->reindexAll();
    }

    /**
     * Test that search results are properly cached.
     */
    public function test_search_results_are_cached(): void
    {
        // Arrange: Enable caching
        config(['fuzzy.cache.enabled' => true]);
        Cache::spy();

        $searchService = app(FuzzySearchService::class);

        // Act: Perform first search
        $results = $searchService->search('john');

        // Assert: Cache::remember should be called at least once
        Cache::shouldHaveReceived('remember')->atLeast()->once();
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Test that cache invalidation does not affect other application caches.
     */
    public function test_cache_does_not_flush_entire_application_cache(): void
    {
        // Arrange: Enable caching and store unrelated cache data
        config(['fuzzy.cache.enabled' => true]);

        Cache::put('session_data', 'user123', 60);
        Cache::put('config_cache', 'value', 3600);

        $searchService = app(FuzzySearchService::class);
        $searchService->search('john');

        $this->assertTrue(Cache::has('session_data'));
        $this->assertTrue(Cache::has('config_cache'));

        // Act: Invalidate fuzzy cache only
        $searchService->invalidateAllCache();

        // Assert: Other caches should remain intact
        $this->assertTrue(
            Cache::has('session_data'),
            'Session cache should not be cleared'
        );
        $this->assertTrue(
            Cache::has('config_cache'),
            'Config cache should not be cleared'
        );

        // Verify fuzzy search cache is regenerated
        Cache::spy();
        $searchService->search('john');
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    /**
     * Test that cache is invalidated after indexing new data.
     */
    public function test_cache_is_invalidated_after_indexing(): void
    {
        // Arrange: Enable caching with index invalidation
        config([
            'fuzzy.cache.enabled' => true,
            'fuzzy.cache.invalidation.on_index' => true,
        ]);

        $searchService = app(FuzzySearchService::class);
        $initialResults = $searchService->search('john');
        $initialCount = $initialResults->count();

        $newUser = User::create([
            'name' => 'Johnny New',
            'email' => 'johnny@example.com',
            'type' => 'user',
        ]);

        Cache::spy();

        // Act: Index new user and search again
        $searchService->indexModel($newUser);
        $newResults = $searchService->search('john');

        // Assert: Cache should be regenerated
        Cache::shouldHaveReceived('remember')->atLeast()->once();
        $this->assertGreaterThanOrEqual(
            $initialCount,
            $newResults->count(),
            'Search after indexing should find at least as many results'
        );
    }

    /**
     * Test that search works correctly when caching is disabled.
     */
    public function test_cache_disabled_works(): void
    {
        // Arrange: Disable caching
        config(['fuzzy.cache.enabled' => false]);

        $searchService = app(FuzzySearchService::class);

        // Act: Perform search
        $results = $searchService->search('test');

        // Assert: Should return results even if empty
        $this->assertInstanceOf(Collection::class, $results);

        // Verify multiple searches work without error
        $secondResults = $searchService->search('test');
        $this->assertInstanceOf(Collection::class, $secondResults);
    }

    /**
     * Test that stats cache has short TTL and expires correctly.
     */
    public function test_stats_cache_has_short_ttl(): void
    {
        // Arrange: Enable caching with short TTL for stats
        config([
            'fuzzy.cache.enabled' => true,
            'fuzzy.cache.ttl.stats' => 2,
        ]);

        $searchService = app(FuzzySearchService::class);

        // First stats call (cache population)
        $initialStats = $searchService->getStats();
        $initialUserCount = $initialStats['models'][User::class]['count'] ?? 0;

        // Create and index new user after cache should expire
        $newUser = User::create([
            'name' => 'Stats Test User',
            'email' => 'stats@example.com',
            'type' => 'user',
        ]);

        sleep(3);
        $searchService->indexModel($newUser);

        // Act: Get stats after cache expiration
        $newStats = $searchService->getStats();
        $newUserCount = $newStats['models'][User::class]['count'] ?? 0;

        // Assert: Stats should be different due to cache expiration
        $this->assertNotEquals(
            $initialUserCount,
            $newUserCount,
            sprintf(
                'Stats should refresh after cache TTL expires (TTL: 2s). ' .
                    'Initial: %d, New: %d',
                $initialUserCount,
                $newUserCount
            )
        );
    }

    /**
     * Test that model-specific cache invalidation works correctly.
     */
    public function test_model_specific_cache_invalidation(): void
    {
        // Arrange: Enable caching with index invalidation
        config([
            'fuzzy.cache.enabled' => true,
            'fuzzy.cache.invalidation.on_index' => true,
        ]);

        $searchService = app(FuzzySearchService::class);

        // Cache user search
        $initialResults = $searchService->searchInModel(User::class, 'john');
        $initialCount = $initialResults->count();

        // Create and index product (should not affect user cache)
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 100,
        ]);

        $searchService->indexModel($product);
        Cache::spy();

        // Act: Search users again
        $newResults = $searchService->searchInModel(User::class, 'john');

        // Assert: User cache should remain valid
        $this->assertCount(
            $initialCount,
            $newResults,
            'User cache should not be affected by Product indexing'
        );

        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    /**
     * Test that cache can be invalidated for specific models only.
     */
    public function test_invalidate_cache_for_specific_model(): void
    {
        // Arrange: Enable caching
        config(['fuzzy.cache.enabled' => true]);

        $searchService = app(FuzzySearchService::class);

        // Cache searches for two models
        $searchService->searchInModel(User::class, 'john');
        $searchService->searchInModel(Product::class, 'laptop');

        // Act: Invalidate only User cache
        $searchService->invalidateCacheForModel(User::class);
        Cache::spy();

        // Perform searches again
        $userResults = $searchService->searchInModel(User::class, 'john');
        $productResults = $searchService->searchInModel(Product::class, 'laptop');

        // Assert: Searches should work and cache should be used
        $this->assertInstanceOf(Collection::class, $userResults);
        $this->assertInstanceOf(Collection::class, $productResults);
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    /**
     * Test that cache keys are properly managed and cleaned up.
     */
    public function test_cache_keys_are_properly_managed(): void
    {
        // Arrange: Enable caching
        config(['fuzzy.cache.enabled' => true]);

        $searchService = app(FuzzySearchService::class);

        // Generate cache keys through various operations
        $searchService->search('test1');
        $searchService->search('test2');
        $searchService->searchInModel(User::class, 'test3');
        $searchService->getStats();

        $storageKey = config('fuzzy.cache.prefix', 'fuzzy_search:') . 'cache_keys';
        $storedKeys = Cache::get($storageKey, []);

        // Assert: Keys should be stored
        $this->assertNotEmpty(
            $storedKeys,
            'Cache keys should be stored for future invalidation'
        );

        // Act: Invalidate all cache
        $searchService->invalidateAllCache();

        // Assert: Stored keys should be removed
        $this->assertFalse(
            Cache::has($storageKey),
            'Cache keys list should be cleared after invalidateAllCache'
        );
    }
}
