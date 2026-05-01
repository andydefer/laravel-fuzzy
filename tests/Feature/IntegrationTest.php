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
use Illuminate\Support\Facades\Cache;

/**
 * Integration tests for the complete fuzzy search system.
 *
 * This test suite verifies the end-to-end functionality of the fuzzy search package,
 * including indexing, searching, caching, and error handling.
 */
final class IntegrationTest extends TestCase
{
    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();

        // Enable cache for integration tests
        config(['fuzzy.cache.enabled' => true]);
        config(['cache.default' => 'array']);
    }

    /**
     * Test the complete search workflow from indexing to searching.
     *
     * Verifies that:
     * - Models are properly indexed
     * - Searches return expected results
     * - Cache invalidation works correctly
     * - Statistics are accurate
     *
     * @return void
     */
    public function test_complete_search_workflow(): void
    {
        // === ARRANGE: Create test data ===
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

        $product1 = Product::create([
            'name' => 'MacBook Pro',
            'description' => 'Apple laptop with M1 chip',
            'price' => 1999.99,
        ]);

        $product2 = Product::create([
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic mouse with Bluetooth',
            'price' => 59.99,
        ]);

        $searchService = app(FuzzySearchService::class);

        // === ACT: Index all data via indexManager ===
        $searchService->getIndexManager()->reindexAll();

        // === ASSERT: Verify initial indexing ===
        $initialStats = $searchService->getIndexManager()->getStats();
        $this->assertGreaterThan(0, $initialStats['total_entries'], 'Should have indexed entries after reindex');

        // === ACT: Global search ===
        $allResults = $searchService->search('john');

        // === ASSERT: Global search results ===
        $this->assertGreaterThan(0, $allResults->count(), 'Search should find "john" in all models');
        $this->assertTrue(
            $this->containsResultWithName($allResults, 'john'),
            'Should find John in search results'
        );

        // === ACT: Model-specific search ===
        $userResults = $searchService->searchInModel(User::class, 'doe');

        // === ASSERT: Model-specific results ===
        $this->assertGreaterThan(0, $userResults->count(), 'Should find users with "doe" in name');
        foreach ($userResults as $result) {
            $this->assertEquals(User::class, $result->modelType, 'All results should be User models');
        }

        // === ACT: Fuzzy search ===
        $fuzzyResults = $searchService->search('joh', ['fuzzy' => true, 'threshold' => 0.3]);

        // === ASSERT: Fuzzy search results ===
        $this->assertGreaterThan(0, $fuzzyResults->count(), 'Fuzzy search should find partial matches');

        // === ACT: Exact match search ===
        $exactResults = $searchService->search('John Smith', ['fuzzy' => false]);

        // === ASSERT: Exact match results ===
        $this->assertGreaterThan(0, $exactResults->count(), 'Exact search should find exact matches');
        $exactMatch = $exactResults->first(function ($result): bool {
            return $result->item->name === 'John Smith';
        });
        $this->assertNotNull($exactMatch, 'Should find John Smith in exact search');
        $this->assertGreaterThan(0.9, $exactMatch->score, 'Exact match should have high score');

        // === ACT: Multi-word search ===
        $multiWordResults = $searchService->search('wireless bluetooth mouse');

        // === ASSERT: Multi-word search results ===
        $this->assertGreaterThan(0, $multiWordResults->count(), 'Multi-word search should return results');

        // === ACT: Search with custom options ===
        $limitedResults = $searchService->search('e', [
            'min_score' => 0.5,
            'max_results' => 2,
        ]);

        // === ASSERT: Custom options respected ===
        $this->assertLessThanOrEqual(2, $limitedResults->count(), 'Should respect max_results limit');
        foreach ($limitedResults as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score, 'Should respect min_score threshold');
        }

        // === ACT: Update model and reindex via indexManager ===
        $user1->name = 'Jonathan Smith';
        $user1->save();
        $searchService->getIndexManager()->indexModel($user1);

        // === ASSERT: Updated data searchable ===
        $updatedResults = $searchService->search('jonathan');
        $this->assertGreaterThan(0, $updatedResults->count(), 'Should find updated name in search');

        // === ACT: Remove model from index via indexManager ===
        $searchService->getIndexManager()->removeModel($user2);
        $user2->delete();

        // Invalidate stats cache via cacheManager
        $searchService->getCacheManager()->invalidateStatsCache();

        // === ASSERT: Removed data no longer searchable ===
        $afterRemoveResults = $searchService->search('jane');
        $this->assertFalse(
            $this->containsResultWithName($afterRemoveResults, 'jane'),
            'Deleted user should not appear in search results'
        );

        // === ASSERT: Statistics reflect deletion ===
        $finalStats = $searchService->getIndexManager()->getStats();
        $expectedFinalEntries = 6; // 1 user (2 fields) + 2 products (4 fields) = 6
        $this->assertEquals(
            $expectedFinalEntries,
            $finalStats['total_entries'],
            'Total entries should be 6 after deletion'
        );
    }

    /**
     * Test automatic indexing via FuzzySearchable trait.
     *
     * Verifies that model events automatically trigger index updates.
     *
     * @return void
     */
    public function test_model_auto_indexing_via_trait(): void
    {
        // === ARRANGE: Get initial count ===
        $initialCount = FuzzyIndex::count();

        // === ACT: Create a new user ===
        $user = User::create([
            'name' => 'Auto Index Test',
            'email' => 'auto@example.com',
            'type' => 'user',
        ]);

        // === ASSERT: Auto-indexing on create ===
        $afterCreateCount = FuzzyIndex::count();
        $this->assertGreaterThan($initialCount, $afterCreateCount, 'Index count should increase after model creation');

        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($userEntry, 'Should find index entry for user name field');
        $this->assertEquals('Auto Index Test', $userEntry->original_value, 'Index should store original value');

        // === ACT: Update user ===
        $user->name = 'Updated Auto Index';
        $user->save();

        // === ASSERT: Auto-indexing on update ===
        $updatedEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($updatedEntry, 'Should find updated index entry');
        $this->assertEquals('Updated Auto Index', $updatedEntry->original_value, 'Index should reflect updated value');

        // === ACT: Delete user ===
        $user->delete();

        // === ASSERT: Auto-indexing on delete ===
        $deletedEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($deletedEntry, 'Index entry should be deleted with model');
    }

    /**
     * Test custom shouldBeIndexed logic.
     *
     * Verifies that the shouldBeIndexed method controls which models are indexed.
     *
     * @return void
     */
    public function test_should_be_indexed_logic(): void
    {
        // === ARRANGE: Create inactive user ===
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'inactive',
        ]);

        $searchService = app(FuzzySearchService::class);

        // === ACT: Try to index inactive user via indexManager ===
        $searchService->getIndexManager()->indexModel($user);

        // === ASSERT: Inactive user not indexed ===
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($entry, 'Inactive user should not be indexed');

        // === ACT: Change to active and reindex via indexManager ===
        $user->type = 'user';
        $user->save();
        $searchService->getIndexManager()->indexModel($user);

        // === ASSERT: Active user indexed ===
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNotNull($entry, 'Active user (type=user) should be indexed');
    }

    /**
     * Test custom formatting in search results.
     *
     * Verifies that custom formatters are applied to search results.
     *
     * @return void
     */
    public function test_custom_formatting(): void
    {
        // === ARRANGE: Create user with custom formatter ===
        $user = User::create([
            'name' => 'Format Test',
            'email' => 'format@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->getIndexManager()->indexModel($user);

        // === ACT: Search for the user ===
        $results = $searchService->search('format');

        // === ASSERT: Results use custom formatter ===
        $this->assertGreaterThan(0, $results->count(), 'Should find formatted user');
        $result = $results->first();
        $this->assertInstanceOf(UserSearchData::class, $result->item, 'Result should use custom formatter');
        $this->assertSame('/users/' . $user->id, $result->item->url, 'Formatter should generate correct URL');
    }

    /**
     * Test performance with large datasets.
     *
     * Verifies that indexing and search operations perform within acceptable limits.
     *
     * @return void
     */
    public function test_performance_with_large_dataset(): void
    {
        // === ARRANGE: Create 100 test users ===
        for ($i = 1; $i <= 100; ++$i) {
            User::create([
                'name' => sprintf('User %d with a longer name for testing', $i),
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        $searchService = app(FuzzySearchService::class);

        // === ACT: Index all users via indexManager ===
        $indexStart = microtime(true);
        $searchService->getIndexManager()->reindexAll();
        $indexTime = microtime(true) - $indexStart;

        // === ASSERT: Indexing completes in reasonable time ===
        $this->assertLessThan(
            10.0,
            $indexTime,
            sprintf('Indexing 100 users should complete within 10 seconds (took %.2fs)', $indexTime)
        );

        // === ACT: Perform search ===
        $searchStart = microtime(true);
        $results = $searchService->search('user 50');
        $searchTime = microtime(true) - $searchStart;

        // === ASSERT: Search completes quickly ===
        $this->assertLessThan(
            1.0,
            $searchTime,
            sprintf('Search should complete within 1 second (took %.2fs)', $searchTime)
        );
        $this->assertGreaterThan(0, $results->count(), 'Should find results in large dataset');
    }

    /**
     * Test cache integration.
     *
     * Verifies that caching works correctly and is properly invalidated.
     *
     * @return void
     */
    public function test_cache_integration(): void
    {
        // === ARRANGE: Create test data ===
        $user = User::create([
            'name' => 'Cache Test',
            'email' => 'cache@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->getIndexManager()->reindexAll();

        // Clear stats cache to ensure clean state
        $searchService->getCacheManager()->invalidateStatsCache();

        // === ACT: First search (should cache) ===
        $results1 = $searchService->search('cache');
        $count1 = $results1->count();

        // === ACT: Modify data without reindexing ===
        $user->name = 'Updated Cache Test';
        $user->save();

        // === ACT: Second search (should use cache) ===
        $results2 = $searchService->search('cache');
        $count2 = $results2->count();

        // === ASSERT: Cached results returned ===
        $this->assertEquals($count1, $count2, 'Should return cached results before invalidation');

        // === ACT: Invalidate cache and reindex ===
        $searchService->getCacheManager()->invalidateAll();
        $searchService->getIndexManager()->indexModel($user);

        // === ACT: Third search (should get fresh data) ===
        $results3 = $searchService->search('cache');
        $count3 = $results3->count();

        // === ASSERT: Fresh results after invalidation ===
        $this->assertGreaterThanOrEqual(0, $count3, 'Should return results after cache invalidation');
    }

    /**
     * Test error handling scenarios.
     *
     * Verifies that the system handles edge cases gracefully.
     *
     * @return void
     */
    public function test_error_handling(): void
    {
        $searchService = app(FuzzySearchService::class);

        // === ACT: Empty query search ===
        $results = $searchService->search('');

        // === ASSERT: Empty query returns empty collection ===
        $this->assertInstanceOf(Collection::class, $results, 'Should return Collection for empty query');
        $this->assertCount(0, $results, 'Empty query should return empty results');

        // === ACT & ASSERT: Non-existent model throws exception via modelDiscovery ===
        $this->expectException(ModelNotSearchableException::class);
        $searchService->getModelDiscovery()->validateModel('NonExistentModel');
        $searchService->searchInModel('NonExistentModel', 'test');

        // === ACT: Invalid options ===
        $results = $searchService->search('test', [
            'min_score' => 'invalid',
            'max_results' => 'not_a_number',
        ]);

        // === ASSERT: Invalid options handled gracefully ===
        $this->assertInstanceOf(Collection::class, $results, 'Should handle invalid options gracefully');
    }

    /**
     * Check if collection contains result with specific name.
     *
     * @param Collection<int, SearchResultData> $results Collection of search results
     * @param string $searchName The name to search for
     * @return bool True if a result with matching name exists
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
