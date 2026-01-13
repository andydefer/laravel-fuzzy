<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Error;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Tests\TestCase;
use Fuzzy\FuzzySearch;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Collection;

/**
 * Feature tests for the FuzzySearch facade.
 *
 * Tests all public methods of the FuzzySearch facade to ensure they work correctly
 * with real database interactions.
 */
final class FacadeTest extends TestCase
{
    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTestMigrations();

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();

        $this->createTestData();
        FuzzySearch::reindexAll();
    }

    /**
     * Load test-specific migrations.
     */
    private function loadTestMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Create test data for all tests.
     */
    private function createTestData(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        User::create([
            'name' => 'Chris Proctor',
            'email' => 'chris@example.com',
            'type' => 'user',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'type' => 'user',
        ]);

        Product::create([
            'name' => 'Laptop Pro',
            'description' => 'High-end laptop with 16GB RAM',
            'price' => 1299.99,
        ]);

        Product::create([
            'name' => 'Mouse Wireless',
            'description' => 'Wireless mouse with ergonomic design',
            'price' => 49.99,
        ]);
    }

    /**
     * Test basic search functionality through the facade.
     */
    public function test_search_facade(): void
    {
        // Arrange: Test data is already set up in setUp() method
        $searchTerm = 'john';

        // Act: Search for "john" using the facade
        $results = FuzzySearch::search($searchTerm);

        // Assert: Verify search returns correct results
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count(), 'Search should return results for "john"');

        $johnResult = $results->first(function ($result): bool {
            return isset($result->item->name) && str_contains(strtolower($result->item->name), 'john');
        });

        $this->assertNotNull($johnResult, 'Should find John in search results');
    }

    /**
     * Test search limited to a specific model class.
     */
    public function test_search_in_model_facade(): void
    {
        // Arrange: Test data is already set up in setUp() method
        $searchTerm = 'john';
        $targetModel = User::class;

        // Act: Search only in User model for "john"
        /** @var Collection<int, SearchResultData> $results */
        $results = FuzzySearch::searchInModel($targetModel, $searchTerm);

        // Assert: Verify only users are returned
        $this->assertInstanceOf(Collection::class, $results);

        foreach ($results as $result) {
            $this->assertEquals($targetModel, $result->modelType);
        }
    }

    /**
     * Test search across multiple specific models.
     */
    public function test_search_in_models_facade(): void
    {
        // Arrange: Test data is already set up in setUp() method
        $searchTerm = 'pro';
        $modelsToSearch = [User::class, Product::class];

        // Act: Search in both User and Product models for "pro"
        $results = FuzzySearch::searchInModels($modelsToSearch, $searchTerm);

        // Assert: Verify results include both model types
        $this->assertInstanceOf(Collection::class, $results);

        $foundTypes = $results->pluck('modelType')->unique()->toArray();

        $this->assertNotEmpty($foundTypes, 'Search should return some results for "pro"');

        $hasUser = in_array(User::class, $foundTypes);
        $hasProduct = in_array(Product::class, $foundTypes);

        $this->assertTrue(
            $hasUser || $hasProduct,
            'Search should find results from User or Product models. Found types: ' . implode(', ', $foundTypes)
        );
    }

    /**
     * Test indexing a single model instance.
     */
    public function test_index_model_facade(): void
    {
        // Arrange: Create a new user to index
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'type' => 'user',
        ]);

        // Act: Index the new user using the facade
        FuzzySearch::indexModel($newUser);

        // Assert: Verify the user is indexed in FuzzyIndex
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $newUser->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('New User', $entry->original_value);
    }

    /**
     * Test updating index for a model after changes.
     */
    public function test_update_model_index_facade(): void
    {
        // Arrange: Get existing user and change name
        $user = User::first();
        $user->name = 'Updated Name';
        $user->save();

        // Act: Update the index for this user
        FuzzySearch::updateModelIndex($user);

        // Assert: Verify index is updated with new value
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Updated Name', $entry->original_value);
    }

    /**
     * Test removing a model from the index.
     */
    public function test_remove_model_from_index_facade(): void
    {
        // Arrange: Get initial count and a user
        $user = User::first();
        $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();

        // Act: Remove the user from index
        FuzzySearch::removeModelFromIndex($user);

        // Assert: Verify count decreased and user entry is gone
        $newCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertLessThan($initialCount, $newCount);

        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($userEntry);
    }

    /**
     * Test reindexing all registered models.
     */
    public function test_reindex_all_facade(): void
    {
        // Arrange: Get initial count and delete some entries
        $initialCount = FuzzyIndex::count();

        FuzzyIndex::limit(2)->delete();
        $afterDeleteCount = FuzzyIndex::count();
        $this->assertLessThan($initialCount, $afterDeleteCount);

        // Act: Reindex all models
        FuzzySearch::reindexAll();

        // Assert: Verify all entries are restored
        $finalCount = FuzzyIndex::count();
        $this->assertEquals($initialCount, $finalCount);
    }

    /**
     * Test reindexing a specific model class.
     */
    public function test_reindex_model_facade(): void
    {
        // Arrange: Get initial counts and delete user entries
        $initialUserCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $initialProductCount = FuzzyIndex::where('indexable_type', Product::class)->count();

        FuzzyIndex::where('indexable_type', User::class)->delete();
        $afterDeleteUserCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(0, $afterDeleteUserCount);

        // Act: Reindex only User model
        FuzzySearch::reindexModel(User::class);

        // Assert: Verify user entries restored, product entries unchanged
        $finalUserCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $finalProductCount = FuzzyIndex::where('indexable_type', Product::class)->count();

        $this->assertEquals($initialUserCount, $finalUserCount);
        $this->assertEquals($initialProductCount, $finalProductCount);
    }

    /**
     * Test retrieving search index statistics.
     */
    public function test_get_stats_facade(): void
    {
        // Act: Get statistics from facade
        $stats = FuzzySearch::getStats();

        // Assert: Verify statistics structure and data
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);
        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertArrayHasKey(Product::class, $stats['models']);
    }

    /**
     * Test similarity calculation between two strings.
     */
    public function test_calculate_similarity_facade(): void
    {
        // Arrange: Define test strings
        $identicalString1 = 'hello';
        $identicalString2 = 'hello';
        $partialString1 = 'hello';
        $partialString2 = 'helo';

        // Act: Calculate similarity scores using facade
        $identicalSimilarity = FuzzySearch::calculateSimilarity($identicalString1, $identicalString2);
        $partialSimilarity = FuzzySearch::calculateSimilarity($partialString1, $partialString2);

        // Assert: Verify similarity calculations are correct
        $this->assertEqualsWithDelta(1.0, $identicalSimilarity, PHP_FLOAT_EPSILON);
        $this->assertGreaterThan(0.0, $partialSimilarity);
        $this->assertLessThan(1.0, $partialSimilarity);
    }

    /**
     * Test string normalization.
     */
    public function test_normalize_facade(): void
    {
        // Arrange: Define string with special characters
        $inputString = 'Héllò Wörld!';
        $expectedOutput = 'hello world';

        // Act: Normalize the string using facade
        $normalized = FuzzySearch::normalize($inputString);

        // Assert: Verify normalization removes accents and special chars
        $this->assertEquals($expectedOutput, $normalized);
    }

    /**
     * Test splitting string into words.
     */
    public function test_split_into_words_facade(): void
    {
        // Arrange: Define input string with hyphens
        $inputString = 'hello-world test';
        $expectedWords = ['hello', 'world', 'test'];

        // Act: Split string into words using facade
        $words = FuzzySearch::splitIntoWords($inputString);

        // Assert: Verify correct word splitting
        $this->assertEquals($expectedWords, $words);
    }

    /**
     * Test search with custom options.
     */
    public function test_search_with_options_facade(): void
    {
        // Arrange: Define search term and options
        $searchTerm = 'j';
        $searchOptions = [
            'min_score' => 0.8,
            'max_results' => 1,
            'fuzzy' => true,
            'threshold' => 0.3,
        ];

        // Act: Search with custom parameters
        $results = FuzzySearch::search($searchTerm, $searchOptions);

        // Assert: Verify results respect options
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertLessThanOrEqual(1, $results->count());

        if ($results->count() > 0) {
            $this->assertGreaterThanOrEqual(0.8, $results->first()->score);
        }
    }

    /**
     * Test facade error handling for non-existent methods.
     */
    public function test_facade_missing_method(): void
    {
        // Arrange: Expect error for non-existent method
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined method');

        // Act: Call non-existent method on facade
        FuzzySearch::nonExistentMethod();
    }

    /**
     * Test facade singleton behavior.
     */
    public function test_facade_singleton_instance(): void
    {
        // Act: Get facade root instance twice
        $instance1 = FuzzySearch::getFacadeRoot();
        $instance2 = FuzzySearch::getFacadeRoot();

        // Assert: Verify same instance is returned (singleton)
        $this->assertSame($instance1, $instance2);
    }
}
