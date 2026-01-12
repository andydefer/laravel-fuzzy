<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Test suite for the IndexBuilder service.
 *
 * @covers \Fuzzy\Services\IndexBuilder
 */
final class IndexBuilderTest extends TestCase
{
    private IndexBuilder $builder;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTestMigrations();

        $normalizer = new StringNormalizer();
        $this->builder = new IndexBuilder(normalizer: $normalizer);

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    /**
     * Load additional migrations required for testing.
     */
    private function loadTestMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /**
     * Test indexing a complete model with multiple searchable fields.
     */
    public function test_index_model(): void
    {
        // Arrange: Create a user with name and email fields
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        // Act: Index the user model
        $this->builder->indexModel($user);

        // Assert: Verify both name and email fields are indexed
        $indexEntries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(2, $indexEntries);
        $this->assertNotNull($indexEntries->where('field', 'name')->first());
        $this->assertNotNull($indexEntries->where('field', 'email')->first());
    }

    /**
     * Test indexing a model that contains null values in searchable fields.
     */
    public function test_index_model_with_null_value(): void
    {
        // Arrange: Create a test class with a nullable searchable field
        $testUser = new class implements MustFuzzySearch {
            /**
             * @return array<int, string>
             */
            public function getSearchableFields(): array
            {
                return ['name', 'nullable_field'];
            }

            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            public function getIndexableId(): int
            {
                return 99999;
            }

            public function shouldBeIndexed(): bool
            {
                return true;
            }

            public function getAttribute($key): ?string
            {
                return $key === 'name' ? 'Test User' : null;
            }
        };

        // Act: Index the test model
        $this->builder->indexModel($testUser);

        // Assert: Only non-null fields should be indexed
        $indexEntries = FuzzyIndex::where('indexable_type', get_class($testUser))
            ->where('indexable_id', 99999)
            ->get();

        $this->assertCount(1, $indexEntries);
        $this->assertEquals('name', $indexEntries->first()->field);
    }

    /**
     * Test indexing a single field with normal text value.
     */
    public function test_index_field(): void
    {
        // Arrange: Define test data for a single field
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'John Doe';

        // Act: Index the field
        $this->builder->indexField($modelType, $modelId, $field, $value);

        // Assert: Verify field is properly indexed with normalized values
        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals($value, $entry->original_value);
        $this->assertEquals('john doe', $entry->normalized_value);
        $this->assertEquals(['john', 'doe'], $entry->words);
        $this->assertIsFloat($entry->weight);
    }

    /**
     * Test that fields with only special characters are not indexed.
     */
    public function test_index_field_empty_normalized_value(): void
    {
        // Arrange: Field value that normalizes to empty string
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = '!!!';

        // Act: Attempt to index the field
        $this->builder->indexField($modelType, $modelId, $field, $value);

        // Assert: No index entry should be created for empty normalized values
        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNull($entry);
    }

    /**
     * Test that single-character fields are handled correctly.
     */
    public function test_index_field_empty_words(): void
    {
        // Arrange: Single character field value
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'a';

        // Act: Index the single character field
        $this->builder->indexField($modelType, $modelId, $field, $value);

        // Assert: Single character should create an index entry
        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(['a'], $entry->words);
    }

    /**
     * Test field weight calculation based on configuration.
     */
    public function test_calculate_field_weight(): void
    {
        // Arrange: Configure field weights
        Config::set('fuzzy.scoring.field_weights', [
            'name' => 1.0,
            'title' => 0.9,
            'default' => 0.5,
        ]);

        // Act & Assert: Verify weight calculation for different field types
        $this->assertEqualsWithDelta(1.0, $this->builder->calculateFieldWeight('name'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.9, $this->builder->calculateFieldWeight('title'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.5, $this->builder->calculateFieldWeight('unknown_field'), PHP_FLOAT_EPSILON);
    }

    /**
     * Test batch indexing of multiple models.
     */
    public function test_batch_index(): void
    {
        // Arrange: Create multiple users and a product
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100,
        ]);

        // Act: Batch index all models
        $this->builder->batchIndex([$user1, $user2, $product]);

        // Assert: Verify all fields are indexed correctly
        $userEntries = FuzzyIndex::where('indexable_type', User::class)->get();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->get();

        $this->assertCount(4, $userEntries); // 2 users × 2 fields each
        $this->assertCount(2, $productEntries); // 1 product × 2 fields
    }

    /**
     * Test that existing index entries are updated rather than duplicated.
     */
    public function test_update_or_create_existing_entry(): void
    {
        // Arrange: Create initial index entry
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';

        FuzzyIndex::create([
            'indexable_type' => $modelType,
            'indexable_id' => $modelId,
            'field' => $field,
            'original_value' => 'Old Name',
            'normalized_value' => 'old name',
            'words' => ['old', 'name'],
            'weight' => 0.5,
        ]);

        // Act: Update the field with new value
        $this->builder->indexField($modelType, $modelId, $field, 'New Name');

        // Assert: Verify entry is updated, not duplicated
        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('New Name', $entry->original_value);

        $count = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test indexing fields containing special characters and accents.
     */
    public function test_index_field_special_characters(): void
    {
        // Arrange: Field with special characters and accents
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'Jöhn-Doé @Company';

        // Act: Index the field with special characters
        $this->builder->indexField($modelType, $modelId, $field, $value);

        // Assert: Verify proper normalization of special characters
        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals($value, $entry->original_value);
        $this->assertEquals('john-doe company', $entry->normalized_value);
        $this->assertEquals(['john', 'doe', 'company'], $entry->words);
    }
}
