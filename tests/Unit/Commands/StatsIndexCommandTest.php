<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Commands;

use Fuzzy\Commands\StatsIndexCommand;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use ReflectionClass;

/**
 * Test suite for the StatsIndexCommand.
 *
 * Validates that the statistics command correctly displays:
 * - Total index entries count
 * - Per-model statistics with entry counts
 * - Field distribution per model
 * - Table formatting for readability
 * - Edge cases (empty index, no fields, large datasets)
 */
final class StatsIndexCommandTest extends TestCase
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
     * Test that stats command displays empty statistics when no data is indexed.
     */
    public function test_stats_command_with_empty_index(): void
    {
        // Act: Execute stats command on empty index
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Command should succeed and show empty statistics
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('=== Search Index Statistics ===', $output);
        $this->assertStringContainsString('Total entries: 0', $output);
        $this->assertStringContainsString('No models indexed yet', $output);
    }

    /**
     * Test that stats command displays statistics when data is indexed.
     */
    public function test_stats_command_with_indexed_data(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Command should display statistics with model information
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('=== Search Index Statistics ===', $output);
        $this->assertStringContainsString('Total entries:', $output);
        $this->assertStringContainsString('Per model statistics:', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    /**
     * Test that stats command shows the correct total entries count.
     */
    public function test_stats_command_shows_correct_total_entries(): void
    {
        // Arrange: Create multiple records and index them
        User::create(['name' => 'User A', 'email' => 'a@example.com', 'type' => 'user']);
        User::create(['name' => 'User B', 'email' => 'b@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product A', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $expectedTotal = FuzzyIndex::count();

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Total entries should match the database count
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("Total entries: {$expectedTotal}", $output);
    }

    /**
     * Test that stats command shows per-model statistics correctly.
     */
    public function test_stats_command_shows_per_model_statistics(): void
    {
        // Arrange: Create data for multiple models and index them
        User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product One', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->count();

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Each model should appear with its entry count
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString(User::class, $output);
        $this->assertStringContainsString((string) $userEntries, $output);
        $this->assertStringContainsString(Product::class, $output);
        $this->assertStringContainsString((string) $productEntries, $output);
    }

    /**
     * Test that stats command shows field distribution per model.
     */
    public function test_stats_command_shows_field_distribution(): void
    {
        // Arrange: Create a user with searchable fields and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Field names should appear in the output for User model
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('name:', $output);
        $this->assertStringContainsString('email:', $output);
    }

    /**
     * Test that stats command correctly handles multiple models.
     */
    public function test_stats_command_handles_multiple_models(): void
    {
        // Arrange: Create data for both User and Product models
        User::create(['name' => 'User', 'email' => 'user@example.com', 'type' => 'user']);
        Product::create(['name' => 'Product', 'description' => 'Test', 'price' => 100]);

        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Both models should be present in the output
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString(User::class, $output);
        $this->assertStringContainsString(Product::class, $output);
    }

    /**
     * Test that stats command displays results in a readable table format.
     */
    public function test_stats_command_displays_table_format(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Table headers should be present
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Model', $output);
        $this->assertStringContainsString('Entries', $output);
        $this->assertStringContainsString('Fields', $output);
    }

    /**
     * Test that stats command shows correct counts when multiple entries exist per field.
     */
    public function test_stats_command_with_multiple_entries_per_field(): void
    {
        // Arrange: Create 5 users with searchable fields
        for ($i = 1; $i <= 5; $i++) {
            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
        }
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Each field should show count of 5 entries
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('name: 5', $output);
        $this->assertStringContainsString('email: 5', $output);
    }

    /**
     * Test that stats command only counts indexable users (those with shouldBeIndexed = true).
     */
    public function test_stats_command_with_mixed_indexable_users(): void
    {
        // Arrange: Create both indexable and non-indexable users
        User::create(['name' => 'Indexable User', 'email' => 'indexable@example.com', 'type' => 'user']);
        User::create(['name' => 'Non-Indexable', 'email' => 'non@example.com', 'type' => 'admin']);

        Artisan::call('fuzzy:index', ['model' => User::class]);

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Only the indexable user should be counted (2 fields = 2 entries)
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('name: 1', $output);
        $this->assertStringContainsString('email: 1', $output);
    }

    /**
     * Test that the command has the correct signature.
     */
    public function test_command_signature(): void
    {
        // Arrange: Instantiate the command
        $command = new StatsIndexCommand();

        // Act: Access the protected signature property via reflection
        $reflection = new ReflectionClass($command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        $signature = $signatureProperty->getValue($command);

        // Assert: Signature should be 'fuzzy:stats'
        $this->assertEquals('fuzzy:stats', $signature);
    }

    /**
     * Test that the command has a non-empty description.
     */
    public function test_command_has_description(): void
    {
        // Arrange: Instantiate the command
        $command = new StatsIndexCommand();

        // Act: Access the protected description property via reflection
        $reflection = new ReflectionClass($command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        $description = $descriptionProperty->getValue($command);

        // Assert: Description should mention index statistics
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('Show search index statistics', $description);
    }

    /**
     * Test that stats command shows correct information after reindexing.
     * Stats command only shows counts, not values, so we verify counts remain correct.
     */
    public function test_stats_command_after_reindex(): void
    {
        // Arrange: Create a user, index it, then update and reindex
        $user = User::create(['name' => 'Original Name', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Assert: Verify initial count (2 fields = 2 entries)
        $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(2, $initialCount);

        // Act: Update user and force reindex
        $user->name = 'Updated Name';
        $user->save();

        Artisan::call('fuzzy:index', ['--force' => true]);
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Entry count should remain the same after update
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Total entries: 2', $output);
        $this->assertStringContainsString(User::class, $output);
    }

    /**
     * Test that stats command gracefully handles the edge case of no fields indexed.
     */
    public function test_stats_command_with_no_fields_indexed(): void
    {
        // Act: Execute stats command on completely empty index
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Command should indicate no models are indexed
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No models indexed yet', $output);
    }

    /**
     * Test that stats command displays correct data types in the output.
     */
    public function test_stats_command_displays_correct_types(): void
    {
        // Arrange: Create test data and index it
        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Output should not claim there are no fields indexed
        $this->assertEquals(0, $exitCode);
        $this->assertStringNotContainsString('No fields indexed', $output);
    }

    /**
     * Test that stats command handles a large number of models efficiently.
     */
    public function test_stats_command_with_large_number_of_models(): void
    {
        // Arrange: Create 50 users (50 × 2 fields = 100 index entries)
        for ($i = 1; $i <= 50; $i++) {
            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
        }
        Artisan::call('fuzzy:index');

        // Act: Execute stats command
        $exitCode = Artisan::call('fuzzy:stats');
        $output = Artisan::output();

        // Assert: Total entries should be 100 (50 users × 2 fields)
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Total entries: 100', $output);
    }
}
