<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Commands;

use Fuzzy\Commands\IndexSearchCommand;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\NonIndexableUser;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use ReflectionClass;

/**
 * Test suite for the IndexSearchCommand.
 *
 * Validates that the index building command works correctly for:
 * - Indexing all searchable models from configuration
 * - Indexing specific models by name
 * - Force reindexing with --force flag
 * - Custom chunk size configuration
 * - Listing available models with --list flag
 * - Respecting shouldBeIndexed() method on models
 * - Displaying accurate statistics after indexing
 */
final class IndexSearchCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Load migrations and clean database before each test
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
        NonIndexableUser::query()->delete();
    }

    protected function tearDown(): void
    {
        // Arrange: Clean database after each test
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
        NonIndexableUser::query()->delete();

        parent::tearDown();
    }

    /**
     * Test that index command indexes all models from configuration and auto-discovery.
     */
    public function test_index_command_indexes_all_models(): void
    {
        // Arrange: Configure searchable models and create test data
        Config::set('fuzzy.searchable_models', [User::class, Product::class]);

        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Product::create(['name' => 'Test Product', 'description' => 'Test', 'price' => 100]);

        // Act: Execute the index command
        $exitCode = Artisan::call('fuzzy:index');
        $output = Artisan::output();

        // Assert: Command should succeed and create index entries
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Indexing complete', $output);
        $this->assertGreaterThan(0, FuzzyIndex::count());
    }

    /**
     * Test that index command can index a specific model only.
     */
    public function test_index_command_with_specific_model(): void
    {
        // Arrange: Disable auto-discovery and create test data without auto-indexing
        Config::set('fuzzy.searchable_models', []);

        FuzzyIndex::query()->truncate();

        $user = User::withoutEvents(function () {
            return User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
        });

        $product = Product::withoutEvents(function () {
            return Product::create(['name' => 'Product One', 'description' => 'Test', 'price' => 100]);
        });

        // Assert: Verify no entries exist before indexing
        $this->assertEquals(0, FuzzyIndex::count(), 'FuzzyIndex should be empty before indexing');

        // Act: Index only the User model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
        $output = Artisan::output();

        // Assert: Command should succeed and index only User entries
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("Indexing model: " . User::class, $output);
        $this->assertStringContainsString("✓ Indexed", $output);

        $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->count();

        // User has 2 searchable fields (name, email) -> 2 entries
        $this->assertEquals(2, $userEntries, 'User should have 2 entries');
        // Product should not be indexed because we only indexed User
        $this->assertEquals(0, $productEntries, 'Product should have 0 entries');
    }

    /**
     * Test that index command with force flag clears and rebuilds indexes.
     */
    public function test_index_command_with_force_reindex(): void
    {
        // Arrange: Create user, index it, then update the name
        $user = User::create(['name' => 'Original Name', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        $user->name = 'Updated Name';
        $user->save();

        // Act: Reindex with force flag
        $exitCode = Artisan::call('fuzzy:index', ['--force' => true]);
        $output = Artisan::output();

        // Assert: Command should clear existing indexes before rebuilding
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Clearing all existing indexes', $output);

        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        // Assert: Index should contain the updated name
        $this->assertNotNull($entry);
        $this->assertEquals('Updated Name', $entry->original_value);
    }

    /**
     * Test that index command with force flag for specific model works correctly.
     */
    public function test_index_command_with_force_reindex_for_specific_model(): void
    {
        // Arrange: Create user, index it, then update the name
        $user = User::create(['name' => 'Original Name', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index', ['model' => User::class]);

        $user->name = 'Updated Name';
        $user->save();

        // Act: Reindex only the User model with force flag
        $exitCode = Artisan::call('fuzzy:index', [
            'model' => User::class,
            '--force' => true,
        ]);
        $output = Artisan::output();

        // Assert: Command should clear only User model indexes
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Clearing existing index for ' . User::class, $output);
    }

    /**
     * Test that index command respects custom chunk size configuration.
     */
    public function test_index_command_with_custom_chunk_size(): void
    {
        // Arrange: Create 150 users to test chunking
        for ($i = 1; $i <= 150; $i++) {
            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
        }

        // Act: Index with chunk size of 50 (should process in 3 batches)
        $exitCode = Artisan::call('fuzzy:index', ['--chunk' => 50]);

        // Assert: 150 users × 2 fields = 300 index entries
        $this->assertEquals(0, $exitCode);
        $this->assertEquals(300, FuzzyIndex::count());
    }

    /**
     * Test that index command with list flag displays available models.
     */
    public function test_index_command_with_list_option(): void
    {
        // Arrange: Configure a searchable model
        Config::set('fuzzy.searchable_models', [User::class]);

        // Act: Execute command with list flag
        $exitCode = Artisan::call('fuzzy:index', ['--list' => true]);
        $output = Artisan::output();

        // Assert: Command should display configuration and model list
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('=== Current Configuration ===', $output);
        $this->assertStringContainsString('Valid searchable models', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    /**
     * Test that index command gracefully handles invalid model names.
     */
    public function test_index_command_with_invalid_model(): void
    {
        // Act: Try to index a non-existent model
        $exitCode = Artisan::call('fuzzy:index', ['model' => 'Invalid\\Model\\Class']);
        $output = Artisan::output();

        // Assert: Command should report the error without crashing
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('must implement', $output);
    }

    /**
     * Test that index command respects the shouldBeIndexed method on models.
     */
    public function test_index_command_respects_shouldBeIndexed(): void
    {
        // Arrange: Create both indexable and non-indexable users
        User::create(['name' => 'Indexable User', 'email' => 'indexable@example.com', 'type' => 'user']);
        NonIndexableUser::create(['name' => 'Non-Indexable User', 'email' => 'nonindexable@example.com', 'type' => 'admin']);

        // Act: Index the User model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);

        // Assert: Only indexable users should be indexed
        $this->assertEquals(0, $exitCode);
        $this->assertEquals(2, FuzzyIndex::where('indexable_type', User::class)->count());
        $this->assertEquals(0, FuzzyIndex::where('indexable_type', NonIndexableUser::class)->count());
    }

    /**
     * Test that index command displays correct statistics after indexing.
     */
    public function test_index_command_displays_statistics_correctly(): void
    {
        // Arrange: Create 5 indexable users
        for ($i = 1; $i <= 5; $i++) {
            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
        }

        // Act: Index the User model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
        $output = Artisan::output();

        // Assert: 5 users × 2 fields = 10 entries
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('✓ Indexed 10 entries for ' . User::class, $output);
        $this->assertStringContainsString('Indexed models: 5 out of 5 total records (100%)', $output);
    }

    /**
     * Test that index command handles mixed indexable and non-indexable users correctly.
     */
    public function test_index_command_with_mixed_indexable_users(): void
    {
        // Arrange: Truncate before test and create both types of users
        FuzzyIndex::query()->truncate();

        // Create indexable users (type='user')
        for ($i = 1; $i <= 3; $i++) {
            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
        }

        // Create non-indexable users within same class (type='admin')
        for ($i = 1; $i <= 2; $i++) {
            User::create(['name' => "Admin User {$i}", 'email' => "admin{$i}@example.com", 'type' => 'admin']);
        }

        // Act: Index the User model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
        $output = Artisan::output();

        // Assert: 3 indexable users × 2 fields = 6 entries out of 5 total users
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('out of 5 total records', $output);
        $this->assertStringContainsString('Indexed models: 3', $output);
        $this->assertStringContainsString('Skipped records: 2', $output);
        $this->assertStringContainsString('(60%)', $output);
    }

    /**
     * Test that index command handles the case when no searchable models are indexable.
     * Models may be discovered but all have shouldBeIndexed() returning false.
     */
    public function test_index_command_with_no_indexable_models(): void
    {
        // Arrange: Clear searchable models configuration
        Config::set('fuzzy.searchable_models', []);

        // Create only non-indexable users (type='admin')
        User::create(['name' => 'Admin User', 'email' => 'admin@example.com', 'type' => 'admin']);

        // Act: Execute index command
        $exitCode = Artisan::call('fuzzy:index');
        $output = Artisan::output();

        // Assert: Command should find models but index 0 entries
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No models were indexed - check shouldBeIndexed() method', $output);
        $this->assertStringNotContainsString('No searchable models found', $output);
    }

    /**
     * Test that index command displays usage guidance with list flag.
     */
    public function test_index_command_displays_usage_guidance(): void
    {
        // Act: Execute command with list flag
        $exitCode = Artisan::call('fuzzy:index', ['--list' => true]);
        $output = Artisan::output();

        // Assert: Command should display usage information
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('php artisan fuzzy:index', $output);
        $this->assertStringContainsString('--force', $output);
        $this->assertStringContainsString('--list', $output);
    }

    /**
     * Test that command signature includes all required options.
     */
    public function test_command_has_required_options(): void
    {
        // Arrange: Instantiate the command
        $command = new IndexSearchCommand();

        // Act: Access the protected signature property via reflection
        $reflection = new ReflectionClass($command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        // Assert: Signature should contain all expected options
        $this->assertStringContainsString('model?', $signature);
        $this->assertStringContainsString('--force', $signature);
        $this->assertStringContainsString('--chunk=', $signature);
        $this->assertStringContainsString('--list', $signature);
    }

    /**
     * Test that command has a non-empty description.
     */
    public function test_command_has_description(): void
    {
        // Arrange: Instantiate the command
        $command = new IndexSearchCommand();

        // Act: Access the protected description property via reflection
        $reflection = new ReflectionClass($command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        $description = $descriptionProperty->getValue($command);

        // Assert: Description should not be empty and mention indexing
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('Index searchable models', $description);
    }

    /**
     * Test that index command displays warning when no models are actually indexed.
     */
    public function test_index_command_displays_warning_when_no_models_indexed(): void
    {
        // Arrange: Create only non-indexable users (type='admin')
        User::create(['name' => 'Non-Indexable', 'email' => 'test@example.com', 'type' => 'admin']);

        // Act: Index the User model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
        $output = Artisan::output();

        // Assert: Command should warn that no models were indexed
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No models were indexed - check shouldBeIndexed() method', $output);
    }

    /**
     * Test that index command with force reindex rebuilds all models correctly.
     */
    public function test_index_command_with_force_reindex_all_models(): void
    {
        // Arrange: Create test data from multiple models
        User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product 1', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act: Reindex all with force flag
        $exitCode = Artisan::call('fuzzy:index', ['--force' => true]);
        $output = Artisan::output();

        // Assert: Count should remain the same after rebuild
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Clearing all existing indexes', $output);
        $this->assertStringContainsString('Indexing complete', $output);
        $this->assertEquals($initialCount, FuzzyIndex::count());
    }

    /**
     * Test that index command uses configured models instead of auto-discovery.
     */
    public function test_index_command_with_configured_models(): void
    {
        // Arrange: Configure explicit searchable models
        Config::set('fuzzy.searchable_models', [User::class]);

        User::create(['name' => 'Configured User', 'email' => 'configured@example.com', 'type' => 'user']);

        // Act: Execute index command
        $exitCode = Artisan::call('fuzzy:index');
        $output = Artisan::output();

        // Assert: Command should indicate configuration source
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('(config)', $output);
        $this->assertGreaterThan(0, FuzzyIndex::count());
    }
}
