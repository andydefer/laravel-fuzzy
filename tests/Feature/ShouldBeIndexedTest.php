<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\CreateAndUpdateUser;
use Fuzzy\Tests\Fixtures\CreateOnlyUser;
use Fuzzy\Tests\Fixtures\DeleteOnlyUser;
use Fuzzy\Tests\Fixtures\NoneUser;
use Fuzzy\Tests\Fixtures\NonIndexableCreateOnlyUser;
use Fuzzy\Tests\Fixtures\NonIndexableUpdateOnlyUser;
use Fuzzy\Tests\Fixtures\NonIndexableUser;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\UpdateOnlyUser;
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
        CreateOnlyUser::query()->delete();
        CreateAndUpdateUser::query()->delete();
        UpdateOnlyUser::query()->delete();
        DeleteOnlyUser::query()->delete();
        NoneUser::query()->delete();
        NonIndexableCreateOnlyUser::query()->delete();
        NonIndexableUpdateOnlyUser::query()->delete();

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

        $this->assertEquals(2, $indexableUserEntries);
        $this->assertEquals(0, $nonIndexableUserEntries);
        $this->assertEquals(2, $productEntries);
    }

    /**
     * Test that updating a model respects shouldBeIndexed.
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

        $this->searchService->getIndexManager()->updateModelIndex($user);

        // Assert: User should no longer be indexed
        $entriesAfter = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->count();
        $this->assertEquals(0, $entriesAfter);
    }

    /**
     * Test that shouldBeIndexed overrides IndexationLevel for manual indexing.
     */
    public function test_should_be_indexed_overrides_indexation_level_for_manual_indexing(): void
    {
        // Arrange: Create a model with ALL events but shouldBeIndexed() = false
        $user = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Act: Try to manually index
        $this->searchService->getIndexManager()->indexModel($user);

        // Assert: No index entries because shouldBeIndexed() is false
        $entries = FuzzyIndex::where('indexable_type', NonIndexableUser::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(0, $entries);
    }

    /**
     * Test that shouldBeIndexed overrides IndexationLevel for auto indexing.
     */
    public function test_should_be_indexed_overrides_indexation_level_for_auto_created(): void
    {
        // Arrange: Create a model with ALL events but shouldBeIndexed() = false
        $user = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Assert: No index entries were auto-created
        $entries = FuzzyIndex::where('indexable_type', NonIndexableUser::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(0, $entries);
    }

    /**
     * Test that shouldBeIndexed false prevents ALL auto-indexing events.
     */
    public function test_should_be_indexed_false_prevents_all_auto_events(): void
    {
        // Arrange: Create a model with ALL events but shouldBeIndexed() = false
        $user = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Verify no index on create
        $this->assertEquals(0, FuzzyIndex::where('indexable_type', NonIndexableUser::class)->count());

        // Act: Update the model
        $user->name = 'Updated Admin';
        $user->save();

        // Assert: Still no index entries
        $this->assertEquals(0, FuzzyIndex::where('indexable_type', NonIndexableUser::class)->count());

        // Act: Delete the model
        $user->delete();

        // Assert: Still no index entries
        $this->assertEquals(0, FuzzyIndex::where('indexable_type', NonIndexableUser::class)->count());
    }

    /**
     * Test that shouldBeIndexed false prevents CREATE_ONLY auto-indexing.
     */
    public function test_should_be_indexed_false_prevents_create_only_auto_index(): void
    {
        // Arrange: Create a model with CREATE_ONLY but shouldBeIndexed() = false
        $user = new class extends CreateOnlyUser {
            public function shouldBeIndexed(): bool
            {
                return false;
            }
        };

        $user->name = 'Create Only Admin';
        $user->email = 'createonlyadmin@example.com';
        $user->type = 'admin';
        $user->save();

        // Assert: No index entries because shouldBeIndexed() is false
        $entries = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(0, $entries);
    }

    /**
     * Test that shouldBeIndexed false prevents UPDATE_ONLY auto-indexing.
     */
    public function test_should_be_indexed_false_prevents_update_only_auto_index(): void
    {
        // Arrange: Create a model with shouldBeIndexed() = true first
        $user = UpdateOnlyUser::create([
            'name' => 'Update Only Admin',
            'email' => 'updateonlyadmin@example.com',
            'type' => 'user',
        ]);

        // Manually index (works because shouldBeIndexed = true)
        $this->searchService->getIndexManager()->indexModel($user);

        // Verify it's indexed
        $count = FuzzyIndex::where('indexable_type', UpdateOnlyUser::class)
            ->where('indexable_id', $user->id)
            ->count();
        $this->assertEquals(2, $count);

        // Change type to make shouldBeIndexed() return false
        $user->type = 'admin';
        $user->save();

        // Act: Update the model
        $user->name = 'Updated Admin Name';
        $user->save();

        // The index should still have the old name (not updated)
        $entry = FuzzyIndex::where('indexable_type', UpdateOnlyUser::class)
            ->where('indexable_id', $user->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Update Only Admin', $entry->original_value);
        $this->assertNotEquals('Updated Admin Name', $entry->original_value);
    }

    /**
     * Test DELETE_ONLY auto-removal behavior with shouldBeIndexed false.
     */
    public function test_should_be_indexed_false_does_not_prevent_delete_only_auto_removal(): void
    {
        // Arrange: Create a model with shouldBeIndexed() = true first
        $user = DeleteOnlyUser::create([
            'name' => 'Delete Only Admin',
            'email' => 'deleteonlyadmin@example.com',
            'type' => 'user',
        ]);

        // Manually index
        $this->searchService->getIndexManager()->indexModel($user);

        // Verify it's indexed
        $count = FuzzyIndex::where('indexable_type', DeleteOnlyUser::class)
            ->where('indexable_id', $user->id)
            ->count();
        $this->assertEquals(2, $count);

        // Change type to make shouldBeIndexed() return false
        $user->type = 'admin';
        $user->save();

        // Act: Delete the model
        $user->delete();

        // Note: Delete event does NOT check shouldBeIndexed() in the trait
        // So entries are always removed on delete
        $entries = FuzzyIndex::where('indexable_type', DeleteOnlyUser::class)
            ->where('indexable_id', $user->id)
            ->get();

        $this->assertCount(0, $entries);
    }

    /**
     * Test the priority: shouldBeIndexed has higher priority than IndexationLevel.
     */
    public function test_should_be_indexed_has_higher_priority_than_indexation_level(): void
    {
        // Scenario 1: ALL level + shouldBeIndexed() = false -> NO indexing
        $adminUser = NonIndexableUser::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);
        $this->assertEquals(0, FuzzyIndex::where('indexable_type', NonIndexableUser::class)->count());

        // Scenario 2: NONE level + shouldBeIndexed() = true -> manual indexing works
        $noneUser = NoneUser::create([
            'name' => 'None User',
            'email' => 'none@example.com',
            'type' => 'user',
        ]);

        $this->searchService->getIndexManager()->indexModel($noneUser);
        $this->assertEquals(2, FuzzyIndex::where('indexable_type', NoneUser::class)->count());

        // Scenario 3: CREATE_ONLY level + shouldBeIndexed() = false -> NO auto-indexing
        $createOnlyAdmin = NonIndexableCreateOnlyUser::create([
            'name' => 'Create Only Admin',
            'email' => 'createonlyadmin@example.com',
            'type' => 'admin',
        ]);
        $this->assertEquals(0, FuzzyIndex::where('indexable_type', NonIndexableCreateOnlyUser::class)->count());

        // Scenario 4: UPDATE_ONLY level + shouldBeIndexed() = false -> NO auto-indexing
        // First create with shouldBeIndexed = true
        $updateOnlyUser = UpdateOnlyUser::create([
            'name' => 'Update Only Admin',
            'email' => 'updateonlyadmin@example.com',
            'type' => 'user',
        ]);

        $this->searchService->getIndexManager()->indexModel($updateOnlyUser);
        $this->assertEquals(2, FuzzyIndex::where('indexable_type', UpdateOnlyUser::class)
            ->where('indexable_id', $updateOnlyUser->id)
            ->count());

        // Change type to make shouldBeIndexed = false
        $updateOnlyUser->type = 'admin';
        $updateOnlyUser->save();

        // Update the model
        $updateOnlyUser->name = 'Updated Admin Name';
        $updateOnlyUser->save();

        // Index still has old name
        $entry = FuzzyIndex::where('indexable_type', UpdateOnlyUser::class)
            ->where('indexable_id', $updateOnlyUser->id)
            ->where('field', 'name')
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Update Only Admin', $entry->original_value);
        $this->assertNotEquals('Updated Admin Name', $entry->original_value);
    }

    /**
     * Test that shouldBeIndexed returning true allows indexing regardless of IndexationLevel.
     */
    public function test_should_be_indexed_true_allows_indexing_regardless_of_level(): void
    {
        // NONE level + shouldBeIndexed() = true
        $noneUser = NoneUser::create([
            'name' => 'None User',
            'email' => 'none@example.com',
            'type' => 'user',
        ]);

        $this->searchService->getIndexManager()->indexModel($noneUser);
        $this->assertEquals(2, FuzzyIndex::where('indexable_type', NoneUser::class)->count());

        // DELETE_ONLY level + shouldBeIndexed() = true
        $deleteUser = DeleteOnlyUser::create([
            'name' => 'Delete User',
            'email' => 'delete@example.com',
            'type' => 'user',
        ]);

        $this->searchService->getIndexManager()->indexModel($deleteUser);
        $this->assertEquals(2, FuzzyIndex::where('indexable_type', DeleteOnlyUser::class)->count());

        // UPDATE_ONLY level + shouldBeIndexed() = true
        $updateUser = UpdateOnlyUser::create([
            'name' => 'Update User',
            'email' => 'update@example.com',
            'type' => 'user',
        ]);

        $this->searchService->getIndexManager()->indexModel($updateUser);
        $this->assertEquals(2, FuzzyIndex::where('indexable_type', UpdateOnlyUser::class)->count());
    }
}
