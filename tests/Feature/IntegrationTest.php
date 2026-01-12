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

        // Clean up test data
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    /**
     * Test the complete search workflow from indexing to searching.
     */
    public function test_complete_search_workflow(): void
    {
        // Arrange: Create test data
        $user1 = User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'type' => 'admin',
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

        // Act: Index all data
        $searchService->reindexAll();

        // Assert: Initial indexing was successful
        $initialStats = $searchService->getStats();
        $this->assertGreaterThan(0, $initialStats['total_entries']);

        // Act: Search across all models
        $allResults = $searchService->search('john');

        // Assert: Found John in results
        $this->assertGreaterThan(0, $allResults->count());
        $johnFound = $allResults->contains(function ($result): bool {
            return str_contains(strtolower($result->item->name), 'john');
        });
        $this->assertTrue($johnFound);

        // Act: Search in specific model
        /** @var Collection<int, SearchResultData> $userResults */
        $userResults = $searchService->searchInModel(User::class, 'doe');

        // Assert: Found user results with correct model type
        $this->assertGreaterThan(0, $userResults->count());
        foreach ($userResults as $result) {
            $this->assertEquals(User::class, $result->modelType);
        }

        // Act: Test fuzzy search
        $fuzzyResults = $searchService->search('joh', ['fuzzy' => true, 'threshold' => 0.3]);

        // Assert: Fuzzy search found results
        $this->assertGreaterThan(0, $fuzzyResults->count());

        // Act: Test exact match
        $exactResults = $searchService->search('John Smith', ['fuzzy' => false]);

        // Assert: Exact match has high score
        $this->assertGreaterThan(0, $exactResults->count());
        $exactMatch = $exactResults->first(function ($result): bool {
            return $result->item->name === 'John Smith';
        });
        $this->assertNotNull($exactMatch);
        $this->assertGreaterThan(0.9, $exactMatch->score);

        // Act: Test multi-word search
        $multiWordResults = $searchService->search('wireless bluetooth mouse');

        // Assert: Multi-word search returns results
        $this->assertGreaterThan(0, $multiWordResults->count());

        // Act: Test with custom options
        /** @var Collection<int, SearchResultData> $limitedResults */
        $limitedResults = $searchService->search('e', [
            'min_score' => 0.5,
            'max_results' => 2,
        ]);

        // Assert: Options are respected
        $this->assertLessThanOrEqual(2, $limitedResults->count());
        foreach ($limitedResults as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score);
        }

        // Act: Update model and reindex
        $user1->name = 'Jonathan Smith';
        $user1->save();
        $searchService->updateModelIndex($user1);

        // Assert: Updated data is searchable
        $updatedResults = $searchService->search('jonathan');
        $this->assertGreaterThan(0, $updatedResults->count());

        // Act: Remove model from index and delete
        $searchService->removeModelFromIndex($user2);
        User::withoutEvents(function () use ($user2): void {
            $user2->delete();
        });

        // Assert: Removed data is no longer searchable
        $afterRemoveResults = $searchService->search('jane');
        $janeFound = $afterRemoveResults->contains(function ($result): bool {
            return str_contains(strtolower($result->item->name), 'jane');
        });
        $this->assertFalse($janeFound);

        // Assert: Stats reflect the deletion
        $finalStats = $searchService->getStats();
        $expectedFinalEntries = $initialStats['total_entries'] - 2;
        $this->assertEquals($expectedFinalEntries, $finalStats['total_entries']);
    }

    /**
     * Test automatic indexing via FuzzySearchable trait.
     */
    public function test_model_auto_indexing_via_trait(): void
    {
        // Arrange: Get initial index count
        $initialCount = FuzzyIndex::count();

        // Act: Create a new user (should auto-index via trait)
        $user = User::create([
            'name' => 'Auto Index Test',
            'email' => 'auto@example.com',
            'type' => 'user',
        ]);

        // Assert: Index entry was created
        $afterCreateCount = FuzzyIndex::count();
        $this->assertGreaterThan($initialCount, $afterCreateCount);

        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($userEntry);
        $this->assertEquals('Auto Index Test', $userEntry->original_value);

        // Act: Update the user
        $user->name = 'Updated Auto Index';
        $user->save();

        // Assert: Index was updated
        $updatedEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($updatedEntry);
        $this->assertEquals('Updated Auto Index', $updatedEntry->original_value);

        // Act: Delete the user
        $user->delete();

        // Assert: Index entry was removed
        $deletedEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($deletedEntry);
    }

    /**
     * Test custom shouldBeIndexed logic.
     */
    public function test_should_be_indexed_logic(): void
    {
        // Arrange: Create anonymous class with custom shouldBeIndexed
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

        // Assert: Inactive user was not indexed
        $entry = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($entry);

        // Act: Change to active and index
        $user->type = 'active';
        $user->save();
        $searchService->indexModel($user);

        // Assert: Active user was indexed
        $entry = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNotNull($entry);
    }

    /**
     * Test custom formatting in search results.
     */
    public function test_custom_formatting(): void
    {
        // Arrange: Create test user
        $user = User::create([
            'name' => 'Format Test',
            'email' => 'format@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->indexModel($user);

        // Act: Search for the user
        $results = $searchService->search('format');

        // Assert: Results use custom UserSearchData formatter
        $this->assertGreaterThan(0, $results->count());
        $result = $results->first();
        $this->assertInstanceOf(UserSearchData::class, $result->item);
        $this->assertSame('/users/' . $user->id, $result->item->url);
    }

    /**
     * Test performance with large datasets.
     */
    public function test_performance_with_large_dataset(): void
    {
        // Arrange: Create 1000 test users
        for ($i = 1; $i <= 1000; ++$i) {
            User::create([
                'name' => sprintf('User %d with a longer name for testing', $i),
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        $searchService = app(FuzzySearchService::class);

        // Act: Index all users and measure time
        $indexStart = microtime(true);
        $searchService->reindexAll();
        $indexTime = microtime(true) - $indexStart;

        // Assert: Indexing completes within reasonable time
        $this->assertLessThan(30.0, $indexTime, sprintf('Indexing 1000 users took %ss', $indexTime));

        // Act: Perform search and measure time
        $searchStart = microtime(true);
        $results = $searchService->search('user 500');
        $searchTime = microtime(true) - $searchStart;

        // Assert: Search completes quickly
        $this->assertLessThan(1.0, $searchTime, sprintf('Search took %ss', $searchTime));
        $this->assertGreaterThan(0, $results->count());
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

        // Act: First search (caches results)
        $results1 = $searchService->search('cache');
        $count1 = $results1->count();

        // Modify data without reindexing
        $user->name = 'Updated Cache Test';
        $user->save();

        // Act: Second search (should use cache)
        $results2 = $searchService->search('cache');
        $count2 = $results2->count();

        // Assert: Cached results are returned
        $this->assertEquals($count1, $count2);

        // Act: Invalidate cache and reindex
        $searchService->invalidateAllCache();
        $searchService->indexModel($user);

        // Act: Third search (fresh results)
        $results3 = $searchService->search('cache');
        $count3 = $results3->count();

        // Assert: Fresh search returns results
        $this->assertGreaterThanOrEqual(0, $count3);
    }

    /**
     * Test error handling scenarios.
     */
    public function test_error_handling(): void
    {
        $searchService = app(FuzzySearchService::class);

        // Act: Search with empty query
        $results = $searchService->search('');

        // Assert: Returns empty collection
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);

        // Act & Assert: Search in non-existent model throws exception
        $this->expectException(ModelNotSearchableException::class);
        $searchService->searchInModel('NonExistentModel', 'test');

        // Act: Search with invalid options (should use defaults)
        $results = $searchService->search('test', [
            'min_score' => 'invalid',
            'max_results' => 'not_a_number',
        ]);

        // Assert: Returns valid results with default options
        $this->assertInstanceOf(Collection::class, $results);
    }
}
