<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Commands;

use Fuzzy\Commands\ClearIndexCommand;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use ReflectionClass;

/**
 * Test suite for the ClearIndexCommand.
 *
 * Validates that the index clearing command works correctly for:
 * - Clearing all indexes with and without confirmation
 * - Clearing indexes for specific models
 * - Force option that bypasses confirmation
 * - Edge cases (empty index, non-existent models)
 * - Output messages and exit codes
 */
final class ClearIndexCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Load migrations and clean database before each test
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    protected function tearDown(): void
    {
        // Arrange: Clean database after each test
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();

        parent::tearDown();
    }

    /**
     * Test that clear command requires confirmation when no force option is provided.
     */
    public function test_clear_command_requires_confirmation(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Assert: Verify data exists before clearing
        $this->assertGreaterThan(0, FuzzyIndex::count());

        // Act & Assert: Command should ask for confirmation and exit without clearing
        $this->artisan('fuzzy:clear')
            ->expectsConfirmation('Clear ALL search indexes?', 'no')
            ->assertExitCode(0);
    }

    /**
     * Test that clear command with force flag skips confirmation.
     */
    public function test_clear_command_with_force_skips_confirmation(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act: Execute command with force flag
        $this->artisan('fuzzy:clear', ['--force' => true])
            ->expectsOutput("✓ Cleared all indexes ({$initialCount} entries)")
            ->assertExitCode(0);

        // Assert: All indexes should be removed
        $finalCount = FuzzyIndex::count();
        $this->assertEquals(0, $finalCount);
    }

    /**
     * Test that clear command removes all indexes from the database.
     */
    public function test_clear_command_removes_all_indexes(): void
    {
        // Arrange: Create test data from multiple models and index them
        User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
        User::create(['name' => 'User Two', 'email' => 'user2@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product One', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act: Execute clear command with force flag
        $exitCode = Artisan::call('fuzzy:clear', ['--force' => true]);
        $output = Artisan::output();

        // Assert: Command should succeed and display correct message
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("✓ Cleared all indexes ({$initialCount} entries)", $output);

        // Assert: Database should be empty after clearing
        $finalCount = FuzzyIndex::count();
        $this->assertEquals(0, $finalCount);
    }

    /**
     * Test that clear command can clear indexes for a specific model only.
     */
    public function test_clear_command_for_specific_model(): void
    {
        // Arrange: Create test data for multiple models and index them
        User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product One', 'description' => 'Test', 'price' => 100]);

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
        $output = Artisan::output();

        // Assert: Command should succeed and display correct model-specific message
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("✓ Cleared {$initialUserEntries} entries for " . User::class, $output);

        // Assert: User entries should be removed, Product entries should remain
        $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->count();

        $this->assertEquals(0, $userEntries);
        $this->assertEquals($initialProductEntries, $productEntries);
    }

    /**
     * Test that clear command for specific model requires confirmation without force.
     */
    public function test_clear_command_for_specific_model_requires_confirmation(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Assert: Verify data exists before clearing
        $this->assertGreaterThan(0, FuzzyIndex::where('indexable_type', User::class)->count());

        // Act & Assert: Command should ask for confirmation for the specific model
        $this->artisan('fuzzy:clear', ['model' => User::class])
            ->expectsConfirmation('Clear index for model ' . User::class . '?', 'no')
            ->assertExitCode(0);
    }

    /**
     * Test that clear command for specific model with force flag skips confirmation.
     */
    public function test_clear_command_for_specific_model_with_force_skips_confirmation(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        $initialEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertGreaterThan(0, $initialEntries);

        // Act: Execute clear command for specific model with force flag
        $this->artisan('fuzzy:clear', [
            'model' => User::class,
            '--force' => true,
        ])
            ->expectsOutput("✓ Cleared {$initialEntries} entries for " . User::class)
            ->assertExitCode(0);

        // Assert: User entries should be removed
        $finalEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(0, $finalEntries);
    }

    /**
     * Test that clear command gracefully handles non-existent model names.
     */
    public function test_clear_command_for_non_existent_model(): void
    {
        // Act: Try to clear indexes for a model that doesn't exist
        $exitCode = Artisan::call('fuzzy:clear', [
            'model' => 'NonExistentModel',
            '--force' => true,
        ]);
        $output = Artisan::output();

        // Assert: Command should report 0 entries cleared without error
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('✓ Cleared 0 entries for NonExistentModel', $output);
    }

    /**
     * Test that clear command respects user confirmation when answered yes.
     */
    public function test_clear_command_with_confirmation_acceptance(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        $initialCount = FuzzyIndex::count();
        $this->assertGreaterThan(0, $initialCount);

        // Act: Execute command and accept confirmation
        $this->artisan('fuzzy:clear')
            ->expectsConfirmation('Clear ALL search indexes?', 'yes')
            ->expectsOutput("✓ Cleared all indexes ({$initialCount} entries)")
            ->assertExitCode(0);

        // Assert: All indexes should be removed
        $finalCount = FuzzyIndex::count();
        $this->assertEquals(0, $finalCount);
    }

    /**
     * Test that clear command gracefully handles an already empty index.
     */
    public function test_clear_command_handles_empty_index(): void
    {
        // Assert: Verify index is empty
        $this->assertEquals(0, FuzzyIndex::count());

        // Act: Execute clear command on empty index
        $exitCode = Artisan::call('fuzzy:clear', ['--force' => true]);
        $output = Artisan::output();

        // Assert: Command should report 0 entries cleared
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('✓ Cleared all indexes (0 entries)', $output);
    }

    /**
     * Test that clear command for specific model handles empty index gracefully.
     */
    public function test_clear_command_for_specific_model_with_empty_index(): void
    {
        // Assert: Verify index is empty
        $this->assertEquals(0, FuzzyIndex::count());

        // Act: Execute clear command for specific model on empty index
        $exitCode = Artisan::call('fuzzy:clear', [
            'model' => User::class,
            '--force' => true,
        ]);
        $output = Artisan::output();

        // Assert: Command should report 0 entries cleared
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('✓ Cleared 0 entries for ' . User::class, $output);
    }

    /**
     * Test that command signature includes all required options.
     */
    public function test_command_has_required_options(): void
    {
        // Arrange: Instantiate the command
        $command = new ClearIndexCommand();

        // Act: Access the protected signature property via reflection
        $reflection = new ReflectionClass($command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        // Assert: Signature should contain model and force options
        $this->assertStringContainsString('model?', $signature);
        $this->assertStringContainsString('--force', $signature);
    }

    /**
     * Test that command has a non-empty description.
     */
    public function test_command_has_description(): void
    {
        // Arrange: Instantiate the command
        $command = new ClearIndexCommand();

        // Act: Access the protected description property via reflection
        $reflection = new ReflectionClass($command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        $description = $descriptionProperty->getValue($command);

        // Assert: Description should not be empty and should mention clearing indexes
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('Clear search index', $description);
    }

    /**
     * Test that clear command displays the correct count of entries after deletion.
     */
    public function test_clear_command_displays_correct_count(): void
    {
        // Arrange: Create multiple users (3 users × 2 fields = 6 index entries)
        User::create(['name' => 'User A', 'email' => 'a@example.com', 'type' => 'user']);
        User::create(['name' => 'User B', 'email' => 'b@example.com', 'type' => 'user']);
        User::create(['name' => 'User C', 'email' => 'c@example.com', 'type' => 'user']);

        Artisan::call('fuzzy:index');

        $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(6, $userEntries);

        // Act: Clear only User model indexes
        $exitCode = Artisan::call('fuzzy:clear', [
            'model' => User::class,
            '--force' => true,
        ]);
        $output = Artisan::output();

        // Assert: Command should report exactly 6 entries cleared
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('✓ Cleared 6 entries for ' . User::class, $output);
    }

    /**
     * Test that clear all command displays the correct total count.
     */
    public function test_clear_all_displays_correct_total_count(): void
    {
        // Arrange: Create test data from multiple models
        User::create(['name' => 'User', 'email' => 'user@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $totalEntries = FuzzyIndex::count();
        $this->assertGreaterThan(0, $totalEntries);

        // Act: Clear all indexes
        $exitCode = Artisan::call('fuzzy:clear', ['--force' => true]);
        $output = Artisan::output();

        // Assert: Command should report the correct total number of entries
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("✓ Cleared all indexes ({$totalEntries} entries)", $output);
    }
}
