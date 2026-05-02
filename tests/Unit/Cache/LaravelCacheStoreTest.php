<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Cache;

use Fuzzy\Cache\LaravelCacheStore;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Tests for the LaravelCacheStore adapter.
 * 
 * Verifies that the cache store adapter correctly wraps Laravel's cache system.
 */
final class LaravelCacheStoreTest extends TestCase
{
    private LaravelCacheStore $cacheStore;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Clear cache before each test
        Cache::flush();
        config(['cache.default' => 'array']);

        $this->cacheStore = new LaravelCacheStore();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test that get returns null for non-existent key.
     */
    public function test_get_returns_null_for_non_existent_key(): void
    {
        // Act: Get non-existent key
        $value = $this->cacheStore->get('non_existent_key');

        // Assert: Should return null
        $this->assertNull($value);
    }

    /**
     * Test that put stores a value in cache.
     */
    public function test_put_stores_value_in_cache(): void
    {
        // Act: Put a value in cache
        $result = $this->cacheStore->put('test_key', 'test_value', 60);

        // Assert: Should return true and value should be retrievable
        $this->assertTrue($result);
        $this->assertEquals('test_value', $this->cacheStore->get('test_key'));
    }

    /**
     * Test that get retrieves stored value.
     */
    public function test_get_retrieves_stored_value(): void
    {
        // Arrange: Store a value
        $this->cacheStore->put('test_key', 'cached_value', 60);

        // Act: Get the value
        $value = $this->cacheStore->get('test_key');

        // Assert: Should return the stored value
        $this->assertEquals('cached_value', $value);
    }

    /**
     * Test that forever stores a value indefinitely.
     */
    public function test_forever_stores_value_indefinitely(): void
    {
        // Act: Store a value forever
        $result = $this->cacheStore->forever('permanent_key', 'permanent_value');

        // Assert: Should return true and value should be retrievable
        $this->assertTrue($result);
        $this->assertEquals('permanent_value', $this->cacheStore->get('permanent_key'));
    }

    /**
     * Test that forget removes a value from cache.
     */
    public function test_forget_removes_value_from_cache(): void
    {
        // Arrange: Store a value
        $this->cacheStore->put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', $this->cacheStore->get('test_key'));

        // Act: Forget the key
        $result = $this->cacheStore->forget('test_key');

        // Assert: Should return true and value should be gone
        $this->assertTrue($result);
        $this->assertNull($this->cacheStore->get('test_key'));
    }

    /**
     * Test that remember executes callback on cache miss.
     */
    public function test_remember_executes_callback_on_cache_miss(): void
    {
        // Arrange: Track callback execution
        $executed = false;

        // Act: Remember value (cache miss)
        $value = $this->cacheStore->remember('miss_key', 60, function () use (&$executed) {
            $executed = true;
            return 'computed_value';
        });

        // Assert: Callback should be executed
        $this->assertTrue($executed);
        $this->assertEquals('computed_value', $value);
        $this->assertEquals('computed_value', $this->cacheStore->get('miss_key'));
    }

    /**
     * Test that remember returns cached value on cache hit.
     */
    public function test_remember_returns_cached_value_on_hit(): void
    {
        // Arrange: Store a value first
        $this->cacheStore->put('hit_key', 'cached_value', 60);

        $executed = false;

        // Act: Remember value (cache hit)
        $value = $this->cacheStore->remember('hit_key', 60, function () use (&$executed) {
            $executed = true;
            return 'computed_value';
        });

        // Assert: Callback should NOT be executed
        $this->assertFalse($executed);
        $this->assertEquals('cached_value', $value);
    }

    /**
     * Test that has returns true for existing key.
     */
    public function test_has_returns_true_for_existing_key(): void
    {
        // Arrange: Store a value
        $this->cacheStore->put('existing_key', 'value', 60);

        // Act: Check if key exists
        $exists = $this->cacheStore->has('existing_key');

        // Assert: Should return true
        $this->assertTrue($exists);
    }

    /**
     * Test that has returns false for non-existing key.
     */
    public function test_has_returns_false_for_non_existing_key(): void
    {
        // Act: Check non-existing key
        $exists = $this->cacheStore->has('non_existing_key');

        // Assert: Should return false
        $this->assertFalse($exists);
    }

    /**
     * Test that increment increases value.
     */
    public function test_increment_increases_value(): void
    {
        // Arrange: Store initial value
        $this->cacheStore->put('counter', 10, 60);

        // Act: Increment by 5
        $newValue = $this->cacheStore->increment('counter', 5);

        // Assert: Should return new value
        $this->assertEquals(15, $newValue);
        $this->assertEquals(15, $this->cacheStore->get('counter'));
    }

    /**
     * Test that increment creates value if not exists.
     */
    public function test_increment_creates_value_if_not_exists(): void
    {
        // Act: Increment non-existing key
        $newValue = $this->cacheStore->increment('new_counter', 5);

        // Assert: Should create and increment from 0
        $this->assertEquals(5, $newValue);
        $this->assertEquals(5, $this->cacheStore->get('new_counter'));
    }

    /**
     * Test that decrement decreases value.
     */
    public function test_decrement_decreases_value(): void
    {
        // Arrange: Store initial value
        $this->cacheStore->put('counter', 20, 60);

        // Act: Decrement by 7
        $newValue = $this->cacheStore->decrement('counter', 7);

        // Assert: Should return new value
        $this->assertEquals(13, $newValue);
        $this->assertEquals(13, $this->cacheStore->get('counter'));
    }

    /**
     * Test that decrement creates value if not exists.
     */
    public function test_decrement_creates_value_if_not_exists(): void
    {
        // Act: Decrement non-existing key
        $newValue = $this->cacheStore->decrement('new_counter', 5);

        // Assert: Should create and decrement from 0
        $this->assertEquals(-5, $newValue);
        $this->assertEquals(-5, $this->cacheStore->get('new_counter'));
    }

    /**
     * Test that constructor accepts custom cache repository.
     */
    public function test_constructor_accepts_custom_cache_repository(): void
    {
        // Arrange: Get a specific cache store
        $customStore = Cache::store('array');

        // Act: Create with custom repository
        $cacheStore = new LaravelCacheStore($customStore);

        // Assert: Should work with custom store
        $cacheStore->put('custom_key', 'custom_value', 60);
        $this->assertEquals('custom_value', $cacheStore->get('custom_key'));
    }

    /**
     * Test that multiple operations work correctly in sequence.
     */
    public function test_multiple_operations_work_correctly(): void
    {
        // Act: Perform multiple cache operations
        $this->cacheStore->put('key1', 'value1', 60);
        $this->cacheStore->put('key2', 'value2', 60);

        $value1 = $this->cacheStore->get('key1');
        $value2 = $this->cacheStore->get('key2');

        $this->cacheStore->forget('key1');

        $exists1 = $this->cacheStore->has('key1');
        $exists2 = $this->cacheStore->has('key2');

        // Assert: Verify all operations
        $this->assertEquals('value1', $value1);
        $this->assertEquals('value2', $value2);
        $this->assertFalse($exists1);
        $this->assertTrue($exists2);
    }

    /**
     * Test that remember with zero TTL still works.
     */
    public function test_remember_with_zero_ttl_works(): void
    {
        // Act: Remember with TTL 0
        $value = $this->cacheStore->remember('zero_ttl_key', 0, function () {
            return 'zero_ttl_value';
        });

        // Assert: Should still work (TTL 0 might mean no expiration)
        $this->assertEquals('zero_ttl_value', $value);
    }
}
