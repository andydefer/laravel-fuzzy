<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

/**
 * Feature tests for console commands.
 */
final class CommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    public function test_index_command_with_auto_discovery(): void
    {
        // Arrange: Configure auto-discovery with specific models
        Config::set('fuzzy.auto_discovery.enabled', true);
        Config::set('fuzzy.searchable_models', [User::class, Product::class]);

        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        // Act: Execute the index command
        $exitCode = Artisan::call('fuzzy:index');

        // Assert: Verify command succeeded and indexing was performed
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Indexing complete', $output);

        $entries = FuzzyIndex::count();
        $this->assertGreaterThan(0, $entries);
    }

    public function test_index_command_with_specific_model(): void
    {
        // Arrange: Prepare clean environment with specific models
        FuzzyIndex::query()->truncate();
        Config::set('fuzzy.searchable_models', []);
        Config::set('fuzzy.auto_discovery.enabled', false);

        User::withoutEvents(
            fn() => User::create([
                'name' => 'User One',
                'email' => 'user1@example.com'
            ])
        );

        Product::withoutEvents(
            fn() => Product::create([
                'name' => 'Product One',
                'description' => 'Test',
                'price' => 100
            ])
        );

        $this->assertEquals(0, FuzzyIndex::count(), 'Index should be empty before indexing');

        // Act: Index only the User model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);

        // Assert: Verify only User entries were indexed
        $this->assertEquals(0, $exitCode);

        $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->count();

        $this->assertEquals(2, $userEntries); // name + email
        $this->assertEquals(0, $productEntries); // Should be 0
    }

    public function test_index_command_with_force_option(): void
    {
        // Arrange: Create user and initial index
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        Artisan::call('fuzzy:index');
        $initialCount = FuzzyIndex::count();

        $user->name = 'Updated User';
        $user->save();

        // Act: Reindex with force option
        $exitCode = Artisan::call('fuzzy:index', ['--force' => true]);

        // Assert: Verify index was rebuilt with updated data
        $this->assertEquals(0, $exitCode);

        $finalCount = FuzzyIndex::count();
        $this->assertEquals($initialCount, $finalCount);

        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Updated User', $entry->original_value);
    }

    public function test_index_command_with_chunk_option(): void
    {
        // Arrange: Create multiple users for batch processing
        for ($i = 1; $i <= 150; ++$i) {
            User::create([
                'name' => 'User ' . $i,
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        // Act: Index with specific chunk size
        $exitCode = Artisan::call('fuzzy:index', ['--chunk' => 50]);

        // Assert: Verify all entries were processed correctly
        $this->assertEquals(0, $exitCode);

        $entries = FuzzyIndex::count();
        $this->assertEquals(300, $entries); // 150 users × 2 fields each
    }

    public function test_index_command_list_option(): void
    {
        // Arrange: Configure models for discovery
        Config::set('fuzzy.searchable_models', [User::class]);
        Config::set('fuzzy.auto_discovery.enabled', true);

        // Act: Execute command with list option
        $exitCode = Artisan::call('fuzzy:index', ['--list' => true]);

        // Assert: Verify command output contains model information
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Current Configuration', $output);
        $this->assertStringContainsString('Valid searchable models', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    public function test_clear_command_with_specific_model(): void
    {
        // Arrange: Create data and build index
        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $initialUserEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $initialProductEntries = FuzzyIndex::where('indexable_type', Product::class)->count();

        $this->assertGreaterThan(0, $initialUserEntries);
        $this->assertGreaterThan(0, $initialProductEntries);

        // Act: Clear only User model indexes
        $exitCode = Artisan::call('fuzzy:clear', [
            'model' => User::class,
            '--force' => true,
        ]);

        // Assert: Verify only User entries were removed
        $this->assertEquals(0, $exitCode);

        $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->count();

        $this->assertEquals(0, $userEntries);
        $this->assertEquals($initialProductEntries, $productEntries);
    }

    public function test_clear_command_all_models(): void
    {
        // Arrange: Create data and build complete index
        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act: Clear all indexes
        $exitCode = Artisan::call('fuzzy:clear', ['--force' => true]);

        // Assert: Verify all entries were removed
        $this->assertEquals(0, $exitCode);

        $finalCount = FuzzyIndex::count();
        $this->assertEquals(0, $finalCount);
    }

    public function test_stats_command(): void
    {
        // Arrange: Create data and build index
        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Act: Execute statistics command
        $exitCode = Artisan::call('fuzzy:stats');

        // Assert: Verify statistics output contains expected information
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Search Index Statistics', $output);
        $this->assertStringContainsString('Total entries', $output);
        $this->assertStringContainsString('Per model statistics', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    public function test_stats_command_empty_index(): void
    {
        // Act: Execute statistics command on empty index
        $exitCode = Artisan::call('fuzzy:stats');

        // Assert: Verify empty index statistics
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Total entries: 0', $output);
        $this->assertStringContainsString('No models indexed yet', $output);
    }

    public function test_clear_cache_command(): void
    {
        // Act: Execute cache clearing command
        $exitCode = Artisan::call('fuzzy:clear-cache', ['--force' => true]);

        // Assert: Verify cache was cleared
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('All fuzzy search cache cleared', $output);
    }

    public function test_clear_cache_command_stats_only(): void
    {
        // Act: Execute cache clearing for statistics only
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--stats' => true,
            '--force' => true,
        ]);

        // Assert: Verify statistics cache was cleared
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertTrue(
            str_contains($output, 'Statistics cache cleared successfully.') ||
                str_contains($output, 'Stats cache clearing not available')
        );
    }

    public function test_clear_cache_command_specific_model(): void
    {
        // Act: Execute cache clearing for specific model
        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--model' => User::class,
            '--force' => true,
        ]);

        // Assert: Verify model-specific cache was cleared
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertTrue(
            str_contains($output, "Cache cleared for model: " . User::class) ||
                str_contains($output, 'Model-specific cache clearing not available')
        );
    }

    public function test_index_command_with_auto_option(): void
    {
        // Arrange: Configure auto-discovery with empty config
        Config::set('fuzzy.auto_discovery.enabled', true);
        Config::set('fuzzy.searchable_models', []);

        User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);

        // Act: Execute index command with auto option
        $exitCode = Artisan::call('fuzzy:index', ['--auto' => true]);

        // Assert: Verify auto-discovery worked
        $this->assertEquals(0, $exitCode);

        $entries = FuzzyIndex::count();
        $this->assertGreaterThan(0, $entries);
    }

    public function test_index_command_invalid_model(): void
    {
        // Act: Execute command with invalid model name
        $exitCode = Artisan::call('fuzzy:index', [
            'model' => 'Invalid\\Model\\Class',
        ]);

        // Assert: Verify command handles invalid model gracefully
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('must implement', $output);
        $this->assertStringContainsString('Invalid\\Model\\Class', $output);
    }
}
