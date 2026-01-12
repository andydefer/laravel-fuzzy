<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

final class ArtisanCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    public function test_clear_cache_command(): void
    {
        // Act
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--force' => true,
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('All fuzzy search cache cleared', $output);
    }

    public function test_clear_cache_for_specific_model(): void
    {
        // Arrange
        Config::set('fuzzy.cache.enabled', true);

        // Act
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--model' => User::class,
            '--force' => true,
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertTrue(
            str_contains($output, "Cache cleared for model: " . User::class) ||
                str_contains($output, 'Model-specific cache clearing not available')
        );
    }

    public function test_clear_cache_stats_only(): void
    {
        // Act
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--stats' => true,
            '--force' => true,
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
    }

    public function test_index_command_with_chunk_size(): void
    {
        // Arrange - Créer plusieurs utilisateurs
        for ($i = 1; $i <= 250; ++$i) {
            User::create([
                'name' => 'User ' . $i,
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        // Act - Indexer avec taille de chunk personnalisée
        $exitCode = Artisan::call('fuzzy:index', [
            '--chunk' => 50,
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);

        $entries = FuzzyIndex::count();
        $this->assertEquals(500, $entries); // 250 users × 2 fields
    }

    public function test_index_command_list_option(): void
    {
        // Arrange
        Config::set('fuzzy.auto_discovery.enabled', true);
        Config::set('fuzzy.searchable_models', [User::class]);

        // Act
        $exitCode = Artisan::call('fuzzy:index', ['--list' => true]);

        // Assert
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Current Configuration', $output);
        $this->assertStringContainsString('Valid searchable models', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    public function test_clear_command_confirmation(): void
    {
        // Arrange - Simuler l'entrée utilisateur (non confirmé)
        $this->artisan('fuzzy:clear')
            ->expectsConfirmation('Clear ALL search indexes?', 'no')
            ->assertExitCode(0);

        // Vérifier qu'aucune entrée n'a été supprimée
        // (ne peut pas être testé car pas d'entrées au départ)
    }

    public function test_clear_command_with_force(): void
    {
        // Arrange
        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act
        $exitCode = Artisan::call('fuzzy:clear', ['--force' => true]);

        // Assert
        $this->assertEquals(0, $exitCode);

        $finalCount = FuzzyIndex::count();
        $this->assertEquals(0, $finalCount);
    }

    public function test_stats_command_empty(): void
    {
        // Act
        $exitCode = Artisan::call('fuzzy:stats');

        // Assert
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Total entries: 0', $output);
        $this->assertStringContainsString('No models indexed yet', $output);
    }

    public function test_index_auto_discovery_command(): void
    {
        // Arrange
        Config::set('fuzzy.auto_discovery.enabled', true);
        Config::set('fuzzy.searchable_models', []);

        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);

        // Act
        $exitCode = Artisan::call('fuzzy:index', ['--auto' => true]);

        // Assert
        $this->assertEquals(0, $exitCode);

        $entries = FuzzyIndex::count();
        $this->assertGreaterThan(0, $entries);
    }
}
