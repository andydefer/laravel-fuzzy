<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Facades\Artisan;

/**
 * Tests for monitoring and statistics functionality.
 */
final class MonitoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->cleanupTestData();
    }

    /**
     * Clean up test data before each test.
     */
    private function cleanupTestData(): void
    {
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    /**
     * Test statistics retrieval with existing data.
     */
    public function test_get_stats_with_data(): void
    {
        // Arrange: Create test users and product
        User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'type' => 'user',
        ]);
        User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'type' => 'user',
        ]);
        Product::create([
            'name' => 'Product 1',
            'description' => 'Desc 1',
            'price' => 100,
        ]);

        $searchService = app('laravel-fuzzy.search');
        $searchService->reindexAll();

        // Act: Retrieve statistics
        $stats = $searchService->getStats();

        // Assert: Verify statistics structure and values
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);
        $this->assertEquals(6, $stats['total_entries']);

        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertArrayHasKey(Product::class, $stats['models']);
        $this->assertEquals(4, $stats['models'][User::class]['count']);
        $this->assertEquals(2, $stats['models'][Product::class]['count']);
    }

    /**
     * Test stats command output.
     */
    public function test_stats_command_output(): void
    {
        // Arrange: Create test user and index data
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Verify command output
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Search Index Statistics', $output);
        $this->assertStringContainsString('Total entries', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    /**
     * Test statistics with empty index.
     */
    public function test_stats_with_empty_index(): void
    {
        // Act: Retrieve statistics from empty index
        $searchService = app('laravel-fuzzy.search');
        $stats = $searchService->getStats();

        // Assert: Verify empty statistics
        $this->assertEquals(0, $stats['total_entries']);
        $this->assertEmpty($stats['models']);
    }

    /**
     * Test that statistics include field counts.
     */
    public function test_stats_include_field_counts(): void
    {
        // Arrange: Create test user and index data
        User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        $searchService = app('laravel-fuzzy.search');
        $searchService->reindexAll();

        // Act: Retrieve statistics
        $stats = $searchService->getStats();

        // Assert: Verify field count details
        $this->assertArrayHasKey('fields', $stats['models'][User::class]);
        $this->assertArrayHasKey('name', $stats['models'][User::class]['fields']);
        $this->assertArrayHasKey('email', $stats['models'][User::class]['fields']);
        $this->assertEquals(1, $stats['models'][User::class]['fields']['name']);
        $this->assertEquals(1, $stats['models'][User::class]['fields']['email']);
    }

    /**
     * Test cache invalidation for statistics.
     */
    public function test_stats_cache_invalidation(): void
    {
        // Arrange: Enable cache and create initial data
        config(['fuzzy.cache.enabled' => true]);

        User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        $searchService = app('laravel-fuzzy.search');
        $searchService->reindexAll();

        // Act: Get stats before and after cache invalidation
        $initialStats = $searchService->getStats();

        User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'type' => 'user',
        ]);
        $searchService->reindexAll();

        $updatedStats = $searchService->getStats();

        // Assert: Verify cache invalidation works
        $this->assertNotEquals($initialStats['total_entries'], $updatedStats['total_entries']);
        $this->assertSame(2, $updatedStats['total_entries'] - $initialStats['total_entries']);
    }

    /**
     * Test search performance monitoring.
     */
    public function test_performance_monitoring(): void
    {
        // Arrange: Create bulk test data
        $searchService = app('laravel-fuzzy.search');
        $this->createBulkTestUsers(100);
        $searchService->reindexAll();

        // Act: Measure search execution time
        $startTime = microtime(true);
        $results = $searchService->search('user');
        $executionTime = microtime(true) - $startTime;

        // Assert: Verify performance constraints
        $this->assertGreaterThan(0, $results->count());
        $this->assertLessThan(
            1.0,
            $executionTime,
            sprintf('Search execution time was %ss - should be under 1 second', $executionTime)
        );
    }

    /**
     * Create bulk test users for performance testing.
     */
    private function createBulkTestUsers(int $count): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            User::create([
                'name' => sprintf('User %d with a longer name for testing performance', $i),
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }
    }
}
