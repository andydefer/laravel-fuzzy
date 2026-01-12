<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Error;
use Fuzzy\Tests\TestCase;
use Fuzzy\FuzzySearch;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Collection;

/**
 * Feature tests for the FuzzySearch facade.
 */
final class FacadeTest extends TestCase
{
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

    private function loadTestMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    private function createTestData(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'admin',
        ]);

        User::create([
            'name' => 'Chris Proctor',
            'email' => 'chris@example.com',
            'type' => 'admin',
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

    public function test_search_facade(): void
    {
        // Act
        $results = FuzzySearch::search('john');

        // Assert
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());

        $johnResult = $results->first(function ($result): bool {
            return str_contains(strtolower($result->item->name), 'john');
        });

        $this->assertNotNull($johnResult);
    }

    public function test_search_in_model_facade(): void
    {
        // Act
        /** @var Collection<int, SearchResultData $results> */
        $results = FuzzySearch::searchInModel(User::class, 'john');

        // Assert
        $this->assertInstanceOf(Collection::class, $results);

        // Should only find users
        foreach ($results as $result) {
            $this->assertEquals(User::class, $result->modelType);
        }
    }

    public function test_search_in_models_facade(): void
    {
        // Act
        $results = FuzzySearch::searchInModels([User::class, Product::class], 'pro');

        // Assert
        $this->assertInstanceOf(Collection::class, $results);

        // Should find both users and products with 'pro'
        $foundTypes = $results->pluck('modelType')->unique()->toArray();

        // CORRECTION : Utiliser les noms de classe complets
        $this->assertContains(User::class, $foundTypes);
        $this->assertContains(Product::class, $foundTypes);
    }

    public function test_index_model_facade(): void
    {
        // Arrange
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'type' => 'user',
        ]);

        // Act
        FuzzySearch::indexModel($newUser);

        // Assert
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $newUser->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('New User', $entry->original_value);
    }

    public function test_update_model_index_facade(): void
    {
        // Arrange
        $user = User::first();
        $user->name = 'Updated Name';
        $user->save();

        // Act
        FuzzySearch::updateModelIndex($user);

        // Assert
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Updated Name', $entry->original_value);
    }

    public function test_remove_model_from_index_facade(): void
    {
        // Arrange
        $user = User::first();
        $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();

        // Act
        FuzzySearch::removeModelFromIndex($user);

        // Assert
        $newCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertLessThan($initialCount, $newCount);

        $userEntry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->first();

        $this->assertNull($userEntry);
    }

    public function test_reindex_all_facade(): void
    {
        // Arrange
        $initialCount = FuzzyIndex::count();

        // Delete some entries
        FuzzyIndex::limit(2)->delete();
        $afterDeleteCount = FuzzyIndex::count();
        $this->assertLessThan($initialCount, $afterDeleteCount);

        // Act
        FuzzySearch::reindexAll();

        // Assert
        $finalCount = FuzzyIndex::count();
        $this->assertEquals($initialCount, $finalCount);
    }

    public function test_reindex_model_facade(): void
    {
        // Arrange
        $initialUserCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $initialProductCount = FuzzyIndex::where('indexable_type', Product::class)->count();

        // Delete user entries
        FuzzyIndex::where('indexable_type', User::class)->delete();
        $afterDeleteUserCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $this->assertEquals(0, $afterDeleteUserCount);

        // Act
        FuzzySearch::reindexModel(User::class);

        // Assert
        $finalUserCount = FuzzyIndex::where('indexable_type', User::class)->count();
        $finalProductCount = FuzzyIndex::where('indexable_type', Product::class)->count();

        $this->assertEquals($initialUserCount, $finalUserCount);
        $this->assertEquals($initialProductCount, $finalProductCount); // Should be unchanged
    }

    public function test_get_stats_facade(): void
    {
        // Act
        $stats = FuzzySearch::getStats();

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);
        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertArrayHasKey(Product::class, $stats['models']);
    }

    public function test_calculate_similarity_facade(): void
    {
        // Act
        $similarity = FuzzySearch::calculateSimilarity('hello', 'hello');

        // Assert
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);

        $similarity2 = FuzzySearch::calculateSimilarity('hello', 'helo');
        $this->assertGreaterThan(0.0, $similarity2);
        $this->assertLessThan(1.0, $similarity2);
    }

    public function test_normalize_facade(): void
    {
        // Act
        $normalized = FuzzySearch::normalize('Héllò Wörld!');

        // Assert
        $this->assertEquals('hello world', $normalized);
    }

    public function test_split_into_words_facade(): void
    {
        // Act
        $words = FuzzySearch::splitIntoWords('hello-world test');

        // Assert
        $this->assertEquals(['hello', 'world', 'test'], $words);
    }

    public function test_search_with_options_facade(): void
    {
        // Act
        $results = FuzzySearch::search('j', [
            'min_score' => 0.8,
            'max_results' => 1,
            'fuzzy' => true,
            'threshold' => 0.3,
        ]);

        // Assert
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertLessThanOrEqual(1, $results->count());

        if ($results->count() > 0) {
            $this->assertGreaterThanOrEqual(0.8, $results->first()->score);
        }
    }

    public function test_facade_missing_method(): void
    {
        // Test that calling non-existent method throws appropriate error
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined method');

        FuzzySearch::nonExistentMethod();
    }

    public function test_facade_singleton_instance(): void
    {
        // Test that facade returns same instance
        $instance1 = FuzzySearch::getFacadeRoot();
        $instance2 = FuzzySearch::getFacadeRoot();

        $this->assertSame($instance1, $instance2);
    }
}
