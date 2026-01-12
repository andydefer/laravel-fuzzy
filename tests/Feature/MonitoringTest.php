<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Facades\Artisan;

final class MonitoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    public function test_get_stats_with_data(): void
    {
        // Arrange
        User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product 1', 'description' => 'Desc 1', 'price' => 100]);

        $searchService = app('laravel-fuzzy.search');
        $searchService->reindexAll();

        // Act
        $stats = $searchService->getStats();

        // Assert
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);

        // 2 users × 2 fields + 1 product × 2 fields = 6 entries
        $this->assertEquals(6, $stats['total_entries']);

        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertArrayHasKey(Product::class, $stats['models']);

        $this->assertEquals(4, $stats['models'][User::class]['count']); // 2 users × 2 fields
        $this->assertEquals(2, $stats['models'][Product::class]['count']); // 1 product × 2 fields
    }

    public function test_stats_command_output(): void
    {
        // Arrange
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);

        Artisan::call('fuzzy:index');

        // Act
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Search Index Statistics', $output);
        $this->assertStringContainsString('Total entries', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    public function test_stats_with_empty_index(): void
    {
        // Act
        $searchService = app('laravel-fuzzy.search');
        $stats = $searchService->getStats();

        // Assert
        $this->assertEquals(0, $stats['total_entries']);
        $this->assertEmpty($stats['models']);
    }

    public function test_stats_include_field_counts(): void
    {
        // Arrange
        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);

        $searchService = app('laravel-fuzzy.search');
        $searchService->reindexAll();

        // Act
        $stats = $searchService->getStats();

        // Assert
        $this->assertArrayHasKey('fields', $stats['models'][User::class]);
        $this->assertArrayHasKey('name', $stats['models'][User::class]['fields']);
        $this->assertArrayHasKey('email', $stats['models'][User::class]['fields']);
        $this->assertEquals(1, $stats['models'][User::class]['fields']['name']);
        $this->assertEquals(1, $stats['models'][User::class]['fields']['email']);
    }

    public function test_stats_cache_invalidation(): void
    {
        // Arrange
        config(['fuzzy.cache.enabled' => true]);

        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);

        $searchService = app('laravel-fuzzy.search');
        $searchService->reindexAll();

        // Premier appel (mise en cache)
        $stats1 = $searchService->getStats();

        // Ajouter un nouvel utilisateur
        User::create(['name' => 'New User', 'email' => 'new@example.com', 'type' => 'user']);
        $searchService->reindexAll();

        // Deuxième appel après expiration du cache
        $stats2 = $searchService->getStats();

        // Assert - Devrait montrer la différence
        $this->assertNotEquals($stats1['total_entries'], $stats2['total_entries']);
        $this->assertSame(2, $stats2['total_entries'] - $stats1['total_entries']); // +2 fields
    }

    public function test_performance_monitoring(): void
    {
        // Arrange
        $searchService = app('laravel-fuzzy.search');

        // Créer des données de test
        for ($i = 1; $i <= 100; ++$i) {
            User::create([
                'name' => sprintf('User %d with a longer name for testing performance', $i),
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        $searchService->reindexAll();

        // Act - Mesurer le temps d'exécution
        $startTime = microtime(true);
        $results = $searchService->search('user');
        $executionTime = microtime(true) - $startTime;

        // Assert
        $this->assertGreaterThan(0, $results->count());
        $this->assertLessThan(1.0, $executionTime, sprintf('Search took %ss - should be under 1 second', $executionTime));
    }
}
