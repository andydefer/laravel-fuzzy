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
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    /**
     * Test that clear cache command executes successfully.
     */
    public function test_clear_cache_command(): void
    {
        // Act: Execute clear cache command with force flag
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--force' => true,
        ]);

        // Assert: Command should succeed with success message
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('All fuzzy search cache cleared', $output);
    }

    /**
     * Test clearing cache for a specific model.
     */
    public function test_clear_cache_for_specific_model(): void
    {
        // Arrange: Enable cache for testing
        Config::set('fuzzy.cache.enabled', true);

        // Act: Execute clear cache command for User model
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--model' => User::class,
            '--force' => true,
        ]);

        // Assert: Command should succeed with model-specific message
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertTrue(
            str_contains($output, "Cache cleared for model: " . User::class) ||
                str_contains($output, 'Model-specific cache clearing not available')
        );
    }

    /**
     * Test cache statistics display.
     */
    public function test_clear_cache_stats_only(): void
    {
        // Act: Execute command with stats flag
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--stats' => true,
            '--force' => true,
        ]);

        // Assert: Command should succeed
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test indexing command with custom chunk size.
     */
    public function test_index_command_with_chunk_size(): void
    {
        // Arrange: Create multiple users for indexing
        for ($i = 1; $i <= 250; ++$i) {
            User::create([
                'name' => 'User ' . $i,
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        // Act: Execute indexing with custom chunk size
        $exitCode = Artisan::call('fuzzy:index', [
            '--chunk' => 50,
        ]);

        // Assert: Command should succeed with expected index count
        $this->assertEquals(0, $exitCode);

        $indexEntries = FuzzyIndex::count();
        $this->assertEquals(500, $indexEntries); // 250 users × 2 searchable fields
    }

    /**
     * Test list option to display configuration.
     */
    public function test_index_command_list_option(): void
    {
        // Arrange: Configure searchable models
        Config::set('fuzzy.searchable_models', [User::class]);

        // Act: Execute command with list option
        $exitCode = Artisan::call('fuzzy:index', ['--list' => true]);

        // Assert: Command should display configuration details
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('=== Current Configuration ===', $output);
        $this->assertStringContainsString('Valid searchable models', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    /**
     * Test clear command with user confirmation.
     */
    public function test_clear_command_confirmation(): void
    {
        // Act & Assert: Execute command expecting "no" confirmation
        $this->artisan('fuzzy:clear')
            ->expectsConfirmation('Clear ALL search indexes?', 'no')
            ->assertExitCode(0);
    }

    /**
     * Test force clearing of search indexes.
     */
    public function test_clear_command_with_force(): void
    {
        // Arrange: Create user and index data
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);
        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act: Execute force clear command
        $exitCode = Artisan::call('fuzzy:clear', ['--force' => true]);

        // Assert: All indexes should be cleared
        $this->assertEquals(0, $exitCode);

        $finalCount = FuzzyIndex::count();
        $this->assertEquals(0, $finalCount);
    }

    /**
     * Test statistics command with empty index.
     */
    public function test_stats_command_empty(): void
    {
        // Act: Execute statistics command
        $exitCode = Artisan::call('fuzzy:stats');

        // Assert: Command should display empty statistics
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Total entries: 0', $output);
        $this->assertStringContainsString('No models indexed yet', $output);
    }
}
