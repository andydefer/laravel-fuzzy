<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\NonIndexableUser;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;

/**
 * Tests for the shouldBeIndexed method functionality.
 *
 * This test suite verifies that models can control their indexing behavior
 * through the shouldBeIndexed method, preventing unwanted records from
 * being added to the search index.
 */
final class ShouldBeIndexedTest extends TestCase
{
    private SearchServiceInterface $searchService;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
        NonIndexableUser::query()->delete();

        $this->searchService = app(SearchServiceInterface::class);
    }

    /**
     * Test that default shouldBeIndexed returns true for users with type 'user'.
     */
    public function test_default_should_be_indexed_returns_true_for_users(): void
    {
        // Arrange: Create a user instance with type 'user'
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        // Act & Assert: Verify default behavior returns true for type 'user'
        $this->assertTrue($user->shouldBeIndexed());
    }

    /**
     * Test that shouldBeIndexed returns false for users with type 'admin'.
     */
    public function test_should_be_indexed_returns_false_for_admins(): void
    {
        // Arrange: Create a non-indexable user with type 'admin'
        $user = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Act & Assert: Verify returns false for type 'admin'
        $this->assertFalse($user->shouldBeIndexed());
    }

    /**
     * Test that shouldBeIndexed prevents indexing when returning false.
     */
    public function test_should_be_indexed_prevents_indexing(): void
    {
        // Arrange: Create non-indexable user (type 'admin')
        $user = NonIndexableUser::create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Act: Attempt to index the user via IndexManager
        $this->searchService->getIndexManager()->indexModel($user);

        // Assert: No index entry should be created for non-indexable user
        $entry = FuzzyIndex::where('indexable_type', NonIndexableUser::class)
            ->where('indexable_id', 1)
            ->first();

        $this->assertNull($entry);
    }

    /**
     * Test that shouldBeIndexed allows indexing when returning true.
     */
    public function test_should_be_indexed_allows_indexing(): void
    {
        // Arrange: Create indexable user with type 'user'
        $user = User::create([
            'id' => 1,
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'type' => 'user',
        ]);

        // Act: Index the user via IndexManager
        $this->searchService->getIndexManager()->indexModel($user);

        // Assert: Two index entries should be created (name and email)
        $entries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', 1)
            ->get();

        $this->assertCount(2, $entries);
    }

    /**
     * Test shouldBeIndexed with multiple conditions using Product model.
     * Note: Product always returns true for shouldBeIndexed.
     */
    public function test_should_be_indexed_with_conditions(): void
    {
        // Arrange: Product always returns true for shouldBeIndexed
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100,
        ]);

        // Act: Index the product via IndexManager
        $this->searchService->getIndexManager()->indexModel($product);

        // Assert: Two index entries should be created (name and description)
        $entries = FuzzyIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->get();

        $this->assertCount(2, $entries);
    }

    /**
     * Test that products are always indexed (shouldBeIndexed returns true).
     */
    public function test_product_always_indexed(): void
    {
        // Arrange: Create product
        $product = Product::create([
            'name' => 'Always Indexed Product',
            'description' => 'This product should always be indexed',
            'price' => 99.99,
        ]);

        // Assert: Product should always be indexable
        $this->assertTrue($product->shouldBeIndexed());

        // Act: Index the product via IndexManager
        $this->searchService->getIndexManager()->indexModel($product);

        // Assert: Verify product was indexed
        $entries = FuzzyIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->get();

        $this->assertCount(2, $entries);
    }

    /**
     * Test mixed indexing scenarios with multiple model types.
     */
    public function test_mixed_indexing_scenarios(): void
    {
        // Arrange: Create one indexable user and one non-indexable user
        $indexableUser = User::create([
            'name' => 'Indexable User',
            'email' => 'indexable@example.com',
            'type' => 'user',
        ]);

        $nonIndexableUser = NonIndexableUser::create([
            'name' => 'Non-Indexable User',
            'email' => 'nonindexable@example.com',
            'type' => 'admin',
        ]);

        $product = Product::create([
            'name' => 'Mixed Test Product',
            'description' => 'Product for mixed test',
            'price' => 50,
        ]);

        // Act: Index all models via IndexManager
        $this->searchService->getIndexManager()->indexModel($indexableUser);
        $this->searchService->getIndexManager()->indexModel($nonIndexableUser);
        $this->searchService->getIndexManager()->indexModel($product);

        // Assert: Only indexable user and product should be indexed
        $indexableUserEntries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $indexableUser->id)
            ->count();

        $nonIndexableUserEntries = FuzzyIndex::where('indexable_type', NonIndexableUser::class)
            ->where('indexable_id', $nonIndexableUser->id)
            ->count();

        $productEntries = FuzzyIndex::where('indexable_type', Product::class)
            ->where('indexable_id', $product->id)
            ->count();

        $this->assertEquals(2, $indexableUserEntries); // 2 fields: name, email
        $this->assertEquals(0, $nonIndexableUserEntries);
        $this->assertEquals(2, $productEntries); // 2 fields: name, description
    }

    /**
     * Test that updating a model respects shouldBeIndexed.
     * On utilise updateModelIndex pour mettre à jour l'index après modification.
     */
    public function test_update_respects_should_be_indexed(): void
    {
        // Arrange: Create a user and index it
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'type' => 'user',
        ]);

        $this->searchService->getIndexManager()->indexModel($user);

        // Verify initial indexing
        $entries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->count();
        $this->assertEquals(2, $entries);

        // Act: Change user type to non-indexable and update the index
        $user->type = 'admin';
        $user->save();

        // Utiliser updateModelIndex pour supprimer les anciennes entrées et recréer
        // Comme shouldBeIndexed() retourne maintenant false, updateModelIndex supprimera les entrées
        $this->searchService->getIndexManager()->updateModelIndex($user);

        // Assert: User should no longer be indexed
        $entriesAfter = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->count();
        $this->assertEquals(0, $entriesAfter);
    }
}
