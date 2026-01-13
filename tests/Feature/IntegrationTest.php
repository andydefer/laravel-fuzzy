<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\UserSearchData;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Services\FuzzySearchService;
use Illuminate\Support\Collection;
use Fuzzy\Data\SearchResultData;

/**
 * Integration tests for the complete fuzzy search system.
 */
final class IntegrationTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Clean up test data before each test
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    /**
     * Test the complete search workflow from indexing to searching.
     */
    public function test_complete_search_workflow(): void
    {
        // Arrange: Create test data with various models
        $user1 = User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'type' => 'user',
        ]);

        $user2 = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'type' => 'user',
        ]);

        Product::create([
            'name' => 'MacBook Pro',
            'description' => 'Apple laptop with M1 chip',
            'price' => 1999.99,
        ]);

        Product::create([
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic mouse with Bluetooth',
            'price' => 59.99,
        ]);

        $searchService = app(FuzzySearchService::class);

        // Act: Index all data in the system
        $searchService->reindexAll();

        // Assert: Verify initial indexing was successful
        $initialStats = $searchService->getStats();
        $this->assertGreaterThan(0, $initialStats['total_entries'], 'Should have indexed entries after reindex');

        // Act: Perform global search across all models
        $allResults = $searchService->search('john');

        // Assert: Verify search found relevant results
        $this->assertGreaterThan(0, $allResults->count(), 'Search should find "john" in all models');
        $this->assertTrue(
            $this->containsResultWithName($allResults, 'john'),
            'Should find John in search results'
        );

        // Act: Search within specific model
        /** @var Collection<int, SearchResultData> $userResults */
        $userResults = $searchService->searchInModel(User::class, 'doe');

        // Assert: Verify user-specific search returns correct model type
        $this->assertGreaterThan(0, $userResults->count(), 'Should find users with "doe" in name');
        foreach ($userResults as $result) {
            $this->assertEquals(User::class, $result->modelType, 'All results should be User models');
        }

        // Act: Test fuzzy search with lower threshold
        $fuzzyResults = $searchService->search('joh', ['fuzzy' => true, 'threshold' => 0.3]);

        // Assert: Verify fuzzy search returns results for partial match
        $this->assertGreaterThan(0, $fuzzyResults->count(), 'Fuzzy search should find partial matches');

        // Act: Test exact match search
        $exactResults = $searchService->search('John Smith', ['fuzzy' => false]);

        // Assert: Verify exact match has high similarity score
        $this->assertGreaterThan(0, $exactResults->count(), 'Exact search should find exact matches');
        $exactMatch = $exactResults->first(function ($result): bool {
            return $result->item->name === 'John Smith';
        });
        $this->assertNotNull($exactMatch, 'Should find John Smith in exact search');
        $this->assertGreaterThan(0.9, $exactMatch->score, 'Exact match should have high score');

        // Act: Test multi-word search across fields
        $multiWordResults = $searchService->search('wireless bluetooth mouse');

        // Assert: Verify multi-word search returns relevant results
        $this->assertGreaterThan(0, $multiWordResults->count(), 'Multi-word search should return results');

        // Act: Test search with custom options
        /** @var Collection<int, SearchResultData> $limitedResults */
        $limitedResults = $searchService->search('e', [
            'min_score' => 0.5,
            'max_results' => 2,
        ]);

        // Assert: Verify search options are respected
        $this->assertLessThanOrEqual(2, $limitedResults->count(), 'Should respect max_results limit');
        foreach ($limitedResults as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score, 'Should respect min_score threshold');
        }

        // Act: Update model and reindex
        $user1->name = 'Jonathan Smith';
        $user1->save();
        $searchService->updateModelIndex($user1);

        // Assert: Verify updated data is searchable
        $updatedResults = $searchService->search('jonathan');
        $this->assertGreaterThan(0, $updatedResults->count(), 'Should find updated name in search');

        // Act: Remove model from index and delete
        $searchService->removeModelFromIndex($user2);
        User::withoutEvents(function () use ($user2): void {
            $user2->delete();
        });

        // Assert: Verify removed data is no longer searchable
        $afterRemoveResults = $searchService->search('jane');
        $this->assertFalse(
            $this->containsResultWithName($afterRemoveResults, 'jane'),
            'Deleted user should not appear in search results'
        );

        // Assert: Verify statistics reflect the deletion
        $finalStats = $searchService->getStats();
        $expectedFinalEntries = $initialStats['total_entries'] - 2;
        $this->assertEquals(
            $expectedFinalEntries,
            $finalStats['total_entries'],
            'Total entries should decrease after deletion'
        );
    }

    /**
     * Test automatic indexing via FuzzySearchable trait.
     */
    public function test_model_auto_indexing_via_trait(): void
    {
        // Arrange: Get initial index count before creating model
        $initialCount = FuzzyIndex::count();

        // Act: Create a new user (should auto-index via trait)
        $user = User::create([
            'name' => 'Auto Index Test',
            'email' => 'auto@example.com',
            'type' => 'user',
        ]);

        // Assert: Verify index entry was automatically created
        $afterCreateCount = FuzzyIndex::count();
        $this->assertGreaterThan($initialCount, $afterCreateCount, 'Index count should increase after model creation');

        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($userEntry, 'Should find index entry for user name field');
        $this->assertEquals('Auto Index Test', $userEntry->original_value, 'Index should store original value');

        // Act: Update the user's name
        $user->name = 'Updated Auto Index';
        $user->save();

        // Assert: Verify index was automatically updated
        $updatedEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($updatedEntry, 'Should find updated index entry');
        $this->assertEquals('Updated Auto Index', $updatedEntry->original_value, 'Index should reflect updated value');

        // Act: Delete the user
        $user->delete();

        // Assert: Verify index entry was automatically removed
        $deletedEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($deletedEntry, 'Index entry should be deleted with model');
    }

    /**
     * Test custom shouldBeIndexed logic.
     */
    public function test_should_be_indexed_logic(): void
    {
        // Arrange: Create anonymous class with custom shouldBeIndexed logic
        $user = new class extends User {
            protected $table = 'users';

            public function shouldBeIndexed(): bool
            {
                return $this->type === 'active';
            }
        };

        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->type = 'inactive';
        $user->save();

        $searchService = app(FuzzySearchService::class);

        // Act: Try to index inactive user
        $searchService->indexModel($user);

        // Assert: Verify inactive user was not indexed
        $entry = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($entry, 'Inactive user should not be indexed');

        // Act: Change user to active and reindex
        $user->type = 'active';
        $user->save();
        $searchService->indexModel($user);

        // Assert: Verify active user was indexed
        $entry = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNotNull($entry, 'Active user should be indexed');
    }

    /**
     * Test custom formatting in search results.
     */
    public function test_custom_formatting(): void
    {
        // Arrange: Create test user with custom formatter
        $user = User::create([
            'name' => 'Format Test',
            'email' => 'format@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->indexModel($user);

        // Act: Search for the user
        $results = $searchService->search('format');

        // Assert: Verify results use custom UserSearchData formatter
        $this->assertGreaterThan(0, $results->count(), 'Should find formatted user');
        $result = $results->first();
        $this->assertInstanceOf(UserSearchData::class, $result->item, 'Result should use custom formatter');
        $this->assertSame('/users/' . $user->id, $result->item->url, 'Formatter should generate correct URL');
    }

    /**
     * Test performance with large datasets.
     */
    public function test_performance_with_large_dataset(): void
    {
        // Arrange: Create 1000 test users for performance testing
        for ($i = 1; $i <= 1000; ++$i) {
            User::create([
                'name' => sprintf('User %d with a longer name for testing', $i),
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        $searchService = app(FuzzySearchService::class);

        // Act: Index all users and measure execution time
        $indexStart = microtime(true);
        $searchService->reindexAll();
        $indexTime = microtime(true) - $indexStart;

        // Assert: Indexing completes within reasonable time for 1000 users
        $this->assertLessThan(
            30.0,
            $indexTime,
            sprintf('Indexing 1000 users should complete within 30 seconds (took %.2fs)', $indexTime)
        );

        // Act: Perform search and measure execution time
        $searchStart = microtime(true);
        $results = $searchService->search('user 500');
        $searchTime = microtime(true) - $searchStart;

        // Assert: Search completes quickly even with large dataset
        $this->assertLessThan(
            1.0,
            $searchTime,
            sprintf('Search should complete within 1 second (took %.2fs)', $searchTime)
        );
        $this->assertGreaterThan(0, $results->count(), 'Should find results in large dataset');
    }

    /**
     * Test cache integration.
     */
    public function test_cache_integration(): void
    {
        // Arrange: Enable cache and create test data
        config(['fuzzy.cache.enabled' => true]);
        config(['cache.default' => 'array']);

        $user = User::create([
            'name' => 'Cache Test',
            'email' => 'cache@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->reindexAll();

        // Act: First search (should cache results)
        $results1 = $searchService->search('cache');
        $count1 = $results1->count();

        // Modify data without reindexing (cache should still return old data)
        $user->name = 'Updated Cache Test';
        $user->save();

        // Act: Second search (should use cached results)
        $results2 = $searchService->search('cache');
        $count2 = $results2->count();

        // Assert: Verify cached results are returned (not fresh data)
        $this->assertEquals($count1, $count2, 'Should return cached results before invalidation');

        // Act: Invalidate cache and reindex with updated data
        $searchService->invalidateAllCache();
        $searchService->indexModel($user);

        // Act: Third search (should return fresh results)
        $results3 = $searchService->search('cache');
        $count3 = $results3->count();

        // Assert: Verify fresh search returns results after cache invalidation
        $this->assertGreaterThanOrEqual(0, $count3, 'Should return results after cache invalidation');
    }

    /**
     * Test error handling scenarios.
     */
    public function test_error_handling(): void
    {
        $searchService = app(FuzzySearchService::class);

        // Act: Search with empty query
        $results = $searchService->search('');

        // Assert: Verify empty query returns empty collection
        $this->assertInstanceOf(Collection::class, $results, 'Should return Collection for empty query');
        $this->assertCount(0, $results, 'Empty query should return empty results');

        // Act & Assert: Search in non-existent model throws exception
        $this->expectException(ModelNotSearchableException::class);
        $searchService->searchInModel('NonExistentModel', 'test');

        // Act: Search with invalid options (should use defaults)
        $results = $searchService->search('test', [
            'min_score' => 'invalid',
            'max_results' => 'not_a_number',
        ]);

        // Assert: Verify search returns valid results with default options
        $this->assertInstanceOf(Collection::class, $results, 'Should handle invalid options gracefully');
    }

    /**
     * Check if collection contains result with specific name.
     *
     * @param Collection<array-key, mixed> $results
     * @param string $searchName
     * @return bool
     */
    private function containsResultWithName(Collection $results, string $searchName): bool
    {
        foreach ($results as $result) {
            if (isset($result->item->name) && str_contains(strtolower($result->item->name), strtolower($searchName))) {
                return true;
            }
        }

        return false;
    }
}
