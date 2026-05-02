<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Commands;

use Fuzzy\Commands\IndexSearchCommand;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\NoneUser;
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
 * - Incremental indexing (default behavior) - only new/updated records
 * - Full reindexing with --force flag - clears then rebuilds all
 * - Indexing specific models by name
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
        NoneUser::query()->delete();
    }

    protected function tearDown(): void
    {
        // Arrange: Clean database after each test
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
        NonIndexableUser::query()->delete();
        NoneUser::query()->delete();

        parent::tearDown();
    }

    /**
     * Test that incremental indexing (default) does not clear existing indexes.
     */
    public function test_incremental_indexing_does_not_clear_existing_indexes(): void
    {
        // Arrange: Create initial user and index it
        $user = User::create(['name' => 'Original Name', 'email' => 'original@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Create a second user
        User::create(['name' => 'Second User', 'email' => 'second@example.com', 'type' => 'user']);

        // Act: Run incremental indexing (no --force flag)
        $exitCode = Artisan::call('fuzzy:index');
        $output = Artisan::output();

        // Assert: Command should NOT clear existing indexes
        $this->assertEquals(0, $exitCode);
        $this->assertStringNotContainsString('Clearing all existing indexes', $output);
        $this->assertStringNotContainsString('Clearing existing index for', $output);

        // New entries should be added without removing old ones
        $finalCount = FuzzyIndex::count();
        $this->assertGreaterThan($initialCount, $finalCount);
    }

    /**
     * Test that full reindex with --force flag clears and rebuilds all indexes.
     */
    public function test_force_reindex_clears_and_rebuilds_all_indexes(): void
    {
        // Arrange: Create initial user and index it
        $user = User::create(['name' => 'Original Name', 'email' => 'original@example.com', 'type' => 'user']);
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

        // Index should contain the updated name
        $this->assertNotNull($entry);
        $this->assertEquals('Updated Name', $entry->original_value);
    }

    /**
     * Test that incremental indexing for specific model does not clear its index.
     */
    public function test_incremental_indexing_for_specific_model_does_not_clear(): void
    {
        // Arrange: Create user and index it
        $user = User::create(['name' => 'Original Name', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index', ['model' => User::class]);

        $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(2, $initialCount);

        // Create a second user
        User::create(['name' => 'Second User', 'email' => 'second@example.com', 'type' => 'user']);

        // Act: Run incremental indexing on specific model
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
        $output = Artisan::output();

        // Assert: Should NOT clear existing indexes
        $this->assertEquals(0, $exitCode);
        $this->assertStringNotContainsString('Clearing existing index for', $output);
        $this->assertStringContainsString('Indexing model: ' . User::class, $output);

        $finalCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(4, $finalCount);
    }

    /**
     * Test that force reindex for specific model clears only that model's index.
     */
    public function test_force_reindex_for_specific_model_clears_only_that_model(): void
    {
        // Arrange: Create user and product, index both
        $user = User::create(['name' => 'User Name', 'email' => 'user@example.com', 'type' => 'user']);
        $product = Product::create(['name' => 'Product Name', 'description' => 'Test', 'price' => 100]);
        Artisan::call('fuzzy:index');

        // Modify user
        $user->name = 'Updated User Name';
        $user->save();

        // Act: Force reindex only the User model
        $exitCode = Artisan::call('fuzzy:index', [
            'model' => User::class,
            '--force' => true,
        ]);
        $output = Artisan::output();

        // Assert: Command should clear only User model indexes
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Clearing existing index for ' . User::class, $output);
        $this->assertStringNotContainsString('Clearing existing index for ' . Product::class, $output);

        // User entries should be updated
        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();
        $this->assertNotNull($userEntry);
        $this->assertEquals('Updated User Name', $userEntry->original_value);

        // Product entries should still exist
        $productCount = FuzzyIndex::where('indexable_type', Product::class)->count();
        $this->assertGreaterThan(0, $productCount);
    }

    /**
     * Test that incremental indexing adds new records without removing existing ones.
     */
    public function test_incremental_indexing_adds_new_records_only(): void
    {
        // Arrange: Create and index initial user
        $user1 = User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index', ['model' => User::class]);

        $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(2, $initialCount);

        // Create a second user
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com', 'type' => 'user']);

        // Act: Run incremental indexing (no --force)
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
        $output = Artisan::output();

        // Assert: Only the new user should be indexed (no reindex of existing)
        $this->assertEquals(0, $exitCode);

        $finalCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(4, $finalCount);
    }

    /**
     * Test that index command indexes all models discovered via auto-discovery.
     */
    public function test_index_command_indexes_all_models(): void
    {
        // Arrange: Create test data for discovered models
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Product::create(['name' => 'Test Product', 'description' => 'Test', 'price' => 100]);

        // Act: Execute the index command (incremental by default)
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
        // Arrange: Create test data without auto-indexing
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
     * Test that index command with list flag displays discoverable models.
     */
    public function test_index_command_with_list_option(): void
    {
        // Act: Execute command with list flag
        $exitCode = Artisan::call('fuzzy:index', ['--list' => true]);
        $output = Artisan::output();

        // Assert: Command should display discoverable models
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('=== Discoverable Models ===', $output);
        $this->assertStringContainsString('Models that implement', $output);
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
     */
    public function test_index_command_with_no_indexable_models(): void
    {
        // Arrange: Create only non-indexable users (type='admin')
        User::create(['name' => 'Admin User', 'email' => 'admin@example.com', 'type' => 'admin']);

        // Act: Execute index command
        $exitCode = Artisan::call('fuzzy:index');
        $output = Artisan::output();

        // Assert: Command should complete successfully
        $this->assertEquals(0, $exitCode);

        // Assert: The output should indicate that no records were found or indexed
        $this->assertStringContainsString('No records found', $output);

        // Check that the total entries is 0
        $stats = app(\Fuzzy\Services\IndexManagerService::class)->getStats();
        $this->assertEquals(0, $stats['total_entries']);
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
     * Test that force reindex with --force flag rebuilds all models correctly.
     */
    public function test_force_reindex_rebuilds_all_models_correctly(): void
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
     * Test that index command uses auto-discovery to find all models.
     */
    public function test_index_command_uses_auto_discovery(): void
    {
        // Arrange: Create test data
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);

        // Act: Execute index command (no configuration, uses auto-discovery)
        $exitCode = Artisan::call('fuzzy:index');
        $output = Artisan::output();

        // Assert: Command should find and index the user
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString(User::class, $output);
        $this->assertGreaterThan(0, FuzzyIndex::count());
    }

    /**
     * Test that incremental indexing does not reindex already indexed records.
     */
    public function test_incremental_indexing_does_not_reindex_existing_records(): void
    {
        // Arrange: Create and index a user
        $user = User::create(['name' => 'Initial Name', 'email' => 'initial@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index', ['model' => User::class]);

        $initialEntries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(2, $initialEntries);

        // Store original entry IDs and values
        $originalIds = $initialEntries->pluck('id')->toArray();
        $originalValues = $initialEntries->pluck('original_value', 'field')->toArray();

        // Act: Run incremental indexing again (no changes to the user)
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);

        // Assert: No new entries were created
        $this->assertEquals(0, $exitCode);

        $finalEntries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(2, $finalEntries);

        // Verify the same entries still exist (not recreated)
        $finalIds = $finalEntries->pluck('id')->toArray();
        $this->assertEquals($originalIds, $finalIds);

        // Verify values haven't changed
        foreach ($finalEntries as $entry) {
            $this->assertEquals($originalValues[$entry->field], $entry->original_value);
        }
    }

    /**
     * Test that incremental indexing updates modified records.
     */
    public function test_incremental_indexing_updates_modified_records(): void
    {
        // Arrange: Create and index a user
        $user = User::create(['name' => 'Original Name', 'email' => 'original@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index', ['model' => User::class]);

        // Update the user's name
        $user->name = 'Updated Name';
        $user->save();

        // Act: Run incremental indexing
        $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);

        // Assert: The index should be updated
        $this->assertEquals(0, $exitCode);

        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Updated Name', $entry->original_value);

        // Email should remain unchanged
        $emailEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'email')
            ->first();

        $this->assertEquals('original@example.com', $emailEntry->original_value);
    }

    /**
     * Test that index command output shows which model is being indexed.
     */
    public function test_index_command_output_indicates_mode(): void
    {
        // Arrange: Create a user
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);

        // Act: Run in incremental mode (default)
        Artisan::call('fuzzy:index');
        $incrementalOutput = Artisan::output();

        // Assert: Output indicates which model is being indexed
        $this->assertStringContainsString('Indexing model:', $incrementalOutput);

        // Reset
        FuzzyIndex::query()->truncate();

        // Act: Run in force mode
        Artisan::call('fuzzy:index', ['--force' => true]);
        $forceOutput = Artisan::output();

        // Assert: Output indicates clearing
        $this->assertStringContainsString('Clearing all existing indexes', $forceOutput);
    }

    /**
     * Test that index command ignores IndexationLevel and always indexes.
     *
     * The fuzzy:index command performs manual indexing by directly calling
     * indexModel() on the IndexManager. This bypasses the model's events,
     * so IndexationLevel (NONE, CREATE_ONLY, etc.) has NO effect on the command.
     * This is intentional: the command is for manual/forced indexing.
     */
    public function test_index_command_ignores_indexation_level(): void
    {
        // Arrange: Create a model with NONE indexation level (no auto-indexing)
        $user = NoneUser::create([
            'name' => 'None User',
            'email' => 'none@example.com',
            'type' => 'user',
        ]);

        // Verify no auto-indexing occurred on create (NONE level respects events)
        $statsBefore = app(\Fuzzy\Services\IndexManagerService::class)->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $statsBefore['models']);

        // Act: Run the index command (manual indexing)
        $exitCode = Artisan::call('fuzzy:index', ['model' => NoneUser::class]);

        // Assert: Command should index the model despite NONE level
        // because the command uses direct manual calls, not events
        $this->assertEquals(0, $exitCode);

        $statsAfter = app(\Fuzzy\Services\IndexManagerService::class)->getStats();
        $this->assertArrayHasKey(NoneUser::class, $statsAfter['models']);
        $this->assertEquals(2, $statsAfter['models'][NoneUser::class]['count']);
    }
}
