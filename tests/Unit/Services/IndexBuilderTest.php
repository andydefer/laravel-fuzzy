<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\Fixtures\NonIndexableUser;
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
        $this->builder = new IndexBuilder($normalizer);

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
        NonIndexableUser::query()->delete();
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
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        $this->builder->indexModel($user);

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
        $product = Product::create([
            'name' => 'Test Product',
            'description' => null,
            'price' => 100,
        ]);

        $this->builder->indexModel($product);

        $indexEntries = FuzzyIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->get();

        $this->assertCount(1, $indexEntries);
        $this->assertEquals('name', $indexEntries->first()->field);
        $this->assertEquals('Test Product', $indexEntries->first()->original_value);
    }

    /**
     * Test that models that should not be indexed are still indexed by IndexBuilder.
     * Note: IndexBuilder does NOT check shouldBeIndexed() - that's IndexManager's job.
     */
    public function test_index_model_indexes_even_when_should_be_indexed_returns_false(): void
    {
        $user = NonIndexableUser::create([
            'name' => 'Non Indexable User',
            'email' => 'nonindexable@example.com',
            'type' => 'inactive',
        ]);

        $this->builder->indexModel($user);

        $indexEntries = FuzzyIndex::where('indexable_type', NonIndexableUser::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(2, $indexEntries);
    }

    /**
     * Test indexing a single field with normal text value.
     */
    public function test_index_field(): void
    {
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'John Doe';

        $this->builder->indexField($modelType, $modelId, $field, $value);

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
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = '!!!';

        $this->builder->indexField($modelType, $modelId, $field, $value);

        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNull($entry);
    }

    /**
     * Test that words shorter than min_word_length are ignored.
     * The default min_word_length is 2, so single characters are not indexed.
     */
    public function test_index_field_minimum_length_ignores_single_characters(): void
    {
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'a';

        $this->builder->indexField($modelType, $modelId, $field, $value);

        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNull($entry);
    }

    /**
     * Test that two-character words are indexed.
     */
    public function test_index_field_two_characters_are_indexed(): void
    {
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'ab';

        $this->builder->indexField($modelType, $modelId, $field, $value);

        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(['ab'], $entry->words);
    }

    /**
     * Test field weight calculation based on configuration.
     */
    public function test_calculate_field_weight(): void
    {
        Config::set('fuzzy.scoring.field_weights', [
            'name' => 1.0,
            'title' => 0.9,
            'default' => 0.5,
        ]);

        $this->assertEqualsWithDelta(1.0, $this->builder->calculateFieldWeight('name'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.9, $this->builder->calculateFieldWeight('title'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.5, $this->builder->calculateFieldWeight('unknown_field'), PHP_FLOAT_EPSILON);
    }

    /**
     * Test batch indexing of multiple models.
     */
    public function test_batch_index(): void
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100,
        ]);

        $this->builder->batchIndex([$user1, $user2, $product]);

        $userEntries = FuzzyIndex::where('indexable_type', User::class)->get();
        $productEntries = FuzzyIndex::where('indexable_type', Product::class)->get();

        $this->assertCount(4, $userEntries);
        $this->assertCount(2, $productEntries);
    }

    /**
     * Test that existing index entries are updated rather than duplicated.
     * Utilise des mots qui ne sont PAS des stop words pour éviter la suppression.
     */
    public function test_update_or_create_existing_entry(): void
    {
        $modelType = User::class;
        $modelId = time();
        $field = 'unique_test_field_' . $modelId;

        // Utiliser des mots qui ne sont PAS des stop words
        // 'original' et 'value' ne sont pas dans la liste des stop words
        FuzzyIndex::create([
            'indexable_type' => $modelType,
            'indexable_id' => $modelId,
            'field' => $field,
            'original_value' => 'Original Value',
            'normalized_value' => 'original value',
            'words' => ['original', 'value'],
            'weight' => 0.5,
            'metadata' => [],
        ]);

        // Utiliser des mots qui ne sont PAS des stop words
        $this->builder->indexField($modelType, $modelId, $field, 'Updated Value');

        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Updated Value', $entry->original_value);

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
        $modelType = User::class;
        $modelId = 1;
        $field = 'name';
        $value = 'Jöhn-Doé @Company';

        $this->builder->indexField($modelType, $modelId, $field, $value);

        $entry = FuzzyIndex::where('indexable_type', $modelType)
            ->where('indexable_id', $modelId)
            ->where('field', $field)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals($value, $entry->original_value);
        $this->assertEquals('john doe company', $entry->normalized_value);
        $this->assertEquals(['john', 'doe', 'company'], $entry->words);
    }

    /**
     * Test that protected fields preserve stop words during indexing.
     */
    public function test_index_model_with_protected_fields_preserves_stop_words(): void
    {
        $user = User::create([
            'name' => 'Jean de La Fontaine',
            'email' => 'jean@example.com',
            'type' => 'user',
        ]);

        $this->builder->indexModel($user);

        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        // Les stop words français "de" et "la" sont préservés car le champ 'name' est protégé
        $this->assertEquals('jean de la fontaine', $entry->normalized_value);
        $this->assertEquals(['jean', 'de', 'la', 'fontaine'], $entry->words);
        $this->assertTrue($entry->metadata['preserves_stop_words'] ?? false);
    }

    /**
     * Test that non-protected fields remove stop words during indexing.
     * 
     * Note: 'very' et 'everyone' sont des stop words et sont donc supprimés.
     * Seuls 'nice' et 'product' restent.
     */
    public function test_index_model_non_protected_fields_remove_stop_words(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'This is a very nice product for everyone',
            'price' => 100,
        ]);

        $this->builder->indexModel($product);

        $entry = FuzzyIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->where('field', 'description')
            ->first();

        $this->assertNotNull($entry);
        // Les stop words 'this', 'is', 'a', 'very', 'for', 'everyone' sont supprimés
        // Restent: 'nice', 'product'
        $this->assertEquals('nice product', $entry->normalized_value);
        $this->assertFalse($entry->metadata['preserves_stop_words'] ?? true);
    }

    /**
     * Test that multiple models with different protected fields are handled correctly.
     * 
     * Note: 'de' n'est PAS un stop word anglais, donc il reste dans la description du produit.
     */
    public function test_index_multiple_models_with_different_protected_fields(): void
    {
        $user = User::create([
            'name' => 'Charles de Gaulle',
            'email' => 'charles@example.com',
            'type' => 'user',
        ]);

        $product = Product::create([
            'name' => 'French History Book',
            'description' => 'About Charles de Gaulle and French history',
            'price' => 50,
        ]);

        $this->builder->indexModel($user);
        $this->builder->indexModel($product);

        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();
        $this->assertEquals('charles de gaulle', $userEntry->normalized_value);

        $productEntry = FuzzyIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->where('field', 'description')
            ->first();
        // 'de' n'est pas un stop word anglais, donc il est conservé
        $this->assertEquals('charles de gaulle french history', $productEntry->normalized_value);
    }
}
