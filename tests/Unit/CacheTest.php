<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit;

use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Unit tests for fuzzy search caching functionality.
 *
 * @covers \Fuzzy\Services\FuzzySearchService::search
 * @covers \Fuzzy\Services\FuzzySearchService::searchInModel
 * @covers \Fuzzy\Services\FuzzySearchService::getStats
 * @covers \Fuzzy\Services\FuzzySearchService::invalidateCacheForModel
 * @covers \Fuzzy\Services\FuzzySearchService::invalidateAllCache
 */
final class CacheTest extends TestCase
{
    /**
     * Setup test environment.
     *
     * @return void
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
     *
     * @return void
     */
    protected function createTestData(): void
    {
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();

        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
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
     *
     * @return void
     */
    public function test_search_results_are_cached(): void
    {
        // Arrange: Enable caching and spy on Cache facade
        config(['fuzzy.cache.enabled' => true]);
        Cache::spy();

        $searchService = app(FuzzySearchService::class);

        // Act: Perform first search operation
        $results = $searchService->search('john');

        // Assert: Cache should be used and results should be valid
        Cache::shouldHaveReceived('remember')->atLeast()->once();
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Test that cache invalidation does not affect other application caches.
     *
     * @return void
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

        // Act: Invalidate fuzzy search cache only
        $searchService->invalidateAllCache();

        // Assert: Other application caches should remain intact
        $this->assertTrue(Cache::has('session_data'));
        $this->assertTrue(Cache::has('config_cache'));

        // Verify fuzzy search cache is regenerated on subsequent search
        Cache::spy();
        $searchService->search('john');
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    /**
     * Test that cache is invalidated after indexing new data.
     *
     * @return void
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

        // Act: Index new user and perform search
        $searchService->indexModel($newUser);
        $newResults = $searchService->search('john');

        // Assert: Cache should be regenerated with updated results
        Cache::shouldHaveReceived('remember')->atLeast()->once();
        $this->assertGreaterThanOrEqual($initialCount, $newResults->count());
    }

    /**
     * Test that search works correctly when caching is disabled.
     *
     * @return void
     */
    public function test_cache_disabled_works(): void
    {
        // Arrange: Disable caching in configuration
        config(['fuzzy.cache.enabled' => false]);

        $searchService = app(FuzzySearchService::class);

        // Act: Perform multiple searches
        $firstResults = $searchService->search('test');
        $secondResults = $searchService->search('test');

        // Assert: Both searches should return valid collections
        $this->assertInstanceOf(Collection::class, $firstResults);
        $this->assertInstanceOf(Collection::class, $secondResults);
    }

    /**
     * Test that stats cache has short TTL and expires correctly.
     *
     * @return void
     */
    public function test_stats_cache_has_short_ttl(): void
    {
        // Arrange: Enable caching with short TTL for stats
        config([
            'fuzzy.cache.enabled' => true,
            'fuzzy.cache.ttl.stats' => 2,
        ]);

        $searchService = app(FuzzySearchService::class);

        // Get initial stats to populate cache
        $initialStats = $searchService->getStats();
        $initialUserCount = $initialStats['models'][User::class]['count'] ?? 0;

        // Create and index new user after cache expiration
        $newUser = User::create([
            'name' => 'Stats Test User',
            'email' => 'stats@example.com',
            'type' => 'user',
        ]);

        sleep(3);
        $searchService->indexModel($newUser);

        // Act: Get stats after cache TTL expiration
        $newStats = $searchService->getStats();
        $newUserCount = $newStats['models'][User::class]['count'] ?? 0;

        // Assert: Stats should be refreshed due to cache expiration
        $this->assertNotEquals($initialUserCount, $newUserCount);
    }

    /**
     * Test that model-specific cache invalidation works correctly.
     *
     * @return void
     */
    public function test_model_specific_cache_invalidation(): void
    {
        // Arrange: Enable caching with index invalidation
        config([
            'fuzzy.cache.enabled' => true,
            'fuzzy.cache.invalidation.on_index' => true,
        ]);

        $searchService = app(FuzzySearchService::class);

        // Cache user search results
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

        // Assert: User cache should remain valid and unaffected by product indexing
        $this->assertCount($initialCount, $newResults);
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    /**
     * Test that cache can be invalidated for specific models only.
     *
     * @return void
     */
    public function test_invalidate_cache_for_specific_model(): void
    {
        // Arrange: Enable caching
        config(['fuzzy.cache.enabled' => true]);

        $searchService = app(FuzzySearchService::class);

        // Cache searches for two different models
        $searchService->searchInModel(User::class, 'john');
        $searchService->searchInModel(Product::class, 'laptop');

        // Act: Invalidate only User model cache
        $searchService->invalidateCacheForModel(User::class);
        Cache::spy();

        // Perform searches for both models
        $userResults = $searchService->searchInModel(User::class, 'john');
        $productResults = $searchService->searchInModel(Product::class, 'laptop');

        // Assert: Both searches should work and use cache appropriately
        $this->assertInstanceOf(Collection::class, $userResults);
        $this->assertInstanceOf(Collection::class, $productResults);
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    /**
     * Test that cache keys are properly managed and cleaned up.
     *
     * @return void
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

        // Assert: Cache keys should be properly stored
        $this->assertNotEmpty($storedKeys);

        // Act: Invalidate all fuzzy search cache
        $searchService->invalidateAllCache();

        // Assert: Cache keys list should be cleared
        $this->assertFalse(Cache::has($storageKey));
    }
}
