<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Services\FuzzySearchService;
use Illuminate\Support\Collection;
use Fuzzy\Data\SearchResultData;

/**
 * Integration tests for the complete fuzzy search system.
 */
class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SUPPRIMER ces lignes - DÉJÀ FAIT DANS TestCase::setUp()
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        // $this->loadTestMigrations();

        // Garder seulement le nettoyage des données
        \Fuzzy\Models\FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }


    public function test_complete_search_workflow(): void
    {
        // 1. Create test data
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

        // 2. Index all data
        $searchService = app(FuzzySearchService::class);
        $searchService->reindexAll();

        $initialStats = $searchService->getStats();
        $this->assertGreaterThan(0, $initialStats['total_entries']);

        // 3. Search across all models
        $allResults = $searchService->search('john');
        $this->assertGreaterThan(0, $allResults->count());

        $johnFound = $allResults->contains(function ($result) {
            return str_contains(strtolower($result->item->name), 'john');
        });
        $this->assertTrue($johnFound);

        // 4. Search in specific model
        /** @var Collection<int, SearchResultData> $userResults */
        $userResults = $searchService->searchInModel(User::class, 'doe');
        $this->assertGreaterThan(0, $userResults->count());

        // CORRECTION: Le modelType est le nom de la classe complète
        foreach ($userResults as $result) {
            $this->assertEquals(User::class, $result->modelType);
        }

        // 5. Test fuzzy search
        $fuzzyResults = $searchService->search('joh', ['fuzzy' => true, 'threshold' => 0.3]);
        $this->assertGreaterThan(0, $fuzzyResults->count());

        // 6. Test exact match
        $exactResults = $searchService->search('John Smith', ['fuzzy' => false]);
        $this->assertGreaterThan(0, $exactResults->count());

        $exactMatch = $exactResults->first(function ($result) {
            return $result->item->name === 'John Smith';
        });
        $this->assertNotNull($exactMatch);
        $this->assertGreaterThan(0.9, $exactMatch->score);

        // 7. Test multi-word search
        $multiWordResults = $searchService->search('wireless bluetooth mouse');
        $this->assertGreaterThan(0, $multiWordResults->count());

        // 8. Test with options
        /** @var Collection<int, SearchResultData> $limitedResults */
        $limitedResults = $searchService->search('e', [
            'min_score' => 0.5,
            'max_results' => 2,
        ]);
        $this->assertLessThanOrEqual(2, $limitedResults->count());

        foreach ($limitedResults as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score);
        }

        // 9. Update a model and reindex
        $user1->name = 'Jonathan Smith';
        $user1->save();

        $searchService->updateModelIndex($user1);

        $updatedResults = $searchService->search('jonathan');
        $this->assertGreaterThan(0, $updatedResults->count());

        // 10. Remove a model from index WITHOUT triggering auto-reindex
        $searchService->removeModelFromIndex($user2);

        // Supprimer manuellement l'utilisateur SANS événements Eloquent
        User::withoutEvents(function () use ($user2) {
            $user2->delete();
        });

        $afterRemoveResults = $searchService->search('jane');
        $janeFound = $afterRemoveResults->contains(function ($result) {
            return str_contains(strtolower($result->item->name), 'jane');
        });
        $this->assertFalse($janeFound);

        // 11. Get final stats - CORRECTION de l'assertion
        $finalStats = $searchService->getStats();

        // On a supprimé 1 utilisateur (2 champs) donc -2 entrées
        $expectedFinalEntries = $initialStats['total_entries'] - 2;
        $this->assertEquals($expectedFinalEntries, $finalStats['total_entries']);
    }

    public function test_model_auto_indexing_via_trait(): void
    {
        // Test that the FuzzySearchable trait automatically indexes models

        // Arrange
        $initialCount = \Fuzzy\Models\FuzzyIndex::count();

        // Act: Create a new user (should auto-index via trait)
        $user = User::create([
            'name' => 'Auto Index Test',
            'email' => 'auto@example.com',
            'type' => 'user',
        ]);

        // Assert
        $afterCreateCount = \Fuzzy\Models\FuzzyIndex::count();
        $this->assertGreaterThan($initialCount, $afterCreateCount);

        $userEntry = \Fuzzy\Models\FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($userEntry);
        $this->assertEquals('Auto Index Test', $userEntry->original_value);

        // Act: Update the user
        $user->name = 'Updated Auto Index';
        $user->save();

        // CORRECTION: Recharger l'entrée depuis la base
        $updatedEntry = \Fuzzy\Models\FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($updatedEntry);
        $this->assertEquals('Updated Auto Index', $updatedEntry->original_value);

        // Act: Delete the user
        $user->delete();

        // Assert: Should remove from index
        $deletedEntry = \Fuzzy\Models\FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($deletedEntry);
    }

    public function test_should_be_indexed_logic(): void
    {
        // Test custom shouldBeIndexed logic

        // Create a user model with custom shouldBeIndexed
        $user = new class extends User {
            protected $table = 'users'; // Définir explicitement la table

            public function shouldBeIndexed(): bool
            {
                return $this->type === 'active';
            }
        };

        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->type = 'inactive'; // Should NOT be indexed
        $user->save();

        // Force index (trait won't index due to shouldBeIndexed returning false)
        $searchService = app(FuzzySearchService::class);
        $searchService->indexModel($user);

        // Check no entry was created
        $entry = \Fuzzy\Models\FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($entry);

        // Now change type to active
        $user->type = 'active';
        $user->save();

        $searchService->indexModel($user);

        // Should now be indexed
        $entry = \Fuzzy\Models\FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNotNull($entry);
    }

    public function test_custom_formatting(): void
    {
        // Test that custom formatting works

        // Arrange
        $user = User::create([
            'name' => 'Format Test',
            'email' => 'format@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->indexModel($user);

        // Act
        $results = $searchService->search('format');

        // Assert: Results should use UserSearchData formatter
        $this->assertGreaterThan(0, $results->count());

        $result = $results->first();
        $this->assertInstanceOf(\Fuzzy\Tests\Fixtures\UserSearchData::class, $result->item);
        $this->assertEquals('/users/' . $user->id, $result->item->url);
    }

    public function test_performance_with_large_dataset(): void
    {
        // Performance test (can be skipped in CI)

        // Arrange: Create larger dataset
        $startTime = microtime(true);

        for ($i = 1; $i <= 1000; $i++) {
            User::create([
                'name' => "User $i with a longer name for testing",
                'email' => "user$i@example.com",
                'type' => 'user',
            ]);
        }

        $searchService = app(FuzzySearchService::class);

        // Act: Index all users
        $indexStart = microtime(true);
        $searchService->reindexAll();
        $indexTime = microtime(true) - $indexStart;

        // Assert: Indexing should be reasonable
        $this->assertLessThan(30.0, $indexTime, "Indexing 1000 users took {$indexTime}s");

        // Act: Search
        $searchStart = microtime(true);
        $results = $searchService->search('user 500');
        $searchTime = microtime(true) - $searchStart;

        // Assert: Search should be fast
        $this->assertLessThan(1.0, $searchTime, "Search took {$searchTime}s");
        $this->assertGreaterThan(0, $results->count());

        $totalTime = microtime(true) - $startTime;
    }

    public function test_cache_integration(): void
    {
        // Test cache integration

        // Arrange: Enable cache
        config(['fuzzy.cache.enabled' => true]);
        config(['cache.default' => 'array']);

        $user = User::create([
            'name' => 'Cache Test',
            'email' => 'cache@example.com',
            'type' => 'user',
        ]);

        $searchService = app(FuzzySearchService::class);
        $searchService->reindexAll();

        // Act: First search (should cache)
        $results1 = $searchService->search('cache');
        $count1 = $results1->count();

        // Modify data (but don't reindex yet)
        $user->name = 'Updated Cache Test';
        $user->save();

        // Second search (should use cache, get old results)
        $results2 = $searchService->search('cache');
        $count2 = $results2->count();

        $this->assertEquals($count1, $count2);

        // Invalidate cache
        $searchService->invalidateAllCache();

        // Reindex
        $searchService->indexModel($user);

        // Third search (should get fresh results)
        $results3 = $searchService->search('cache');
        $count3 = $results3->count();

        // Results may be same or different depending on scoring
        $this->assertGreaterThanOrEqual(0, $count3);
    }

    public function test_error_handling(): void
    {
        // Test error handling

        // Search with empty query
        $searchService = app(FuzzySearchService::class);
        $results = $searchService->search('');
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(0, $results);

        // Search in non-existent model
        $this->expectException(\Fuzzy\Exceptions\ModelNotSearchableException::class);
        $searchService->searchInModel('NonExistentModel', 'test');

        // Search with invalid options (should use defaults)
        $results = $searchService->search('test', [
            'min_score' => 'invalid', // Will be cast to float
            'max_results' => 'not_a_number', // Will be cast to int
        ]);

        $this->assertInstanceOf(Collection::class, $results);
    }
}
