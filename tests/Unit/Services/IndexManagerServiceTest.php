<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Enums\IndexationLevel;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\IndexManagerService;
use Fuzzy\Tests\Fixtures\CreateAndUpdateUser;
use Fuzzy\Tests\Fixtures\CreateOnlyUser;
use Fuzzy\Tests\Fixtures\DeleteOnlyUser;
use Fuzzy\Tests\Fixtures\NoneUser;
use Fuzzy\Tests\Fixtures\NonIndexableUser;
use Fuzzy\Tests\Fixtures\UpdateOnlyUser;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test suite for IndexManagerService.
 *
 * Verifies index lifecycle operations including creation, update, removal,
 * reindexing, and statistics collection for searchable models.
 */
final class IndexManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    private IndexManagerService $indexManager;
    private IndexBuilder $indexBuilder;
    private IndexRepositoryInterface $indexRepository;
    private ModelDiscoveryInterface $modelDiscovery;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Use real implementations from the container
        $this->indexBuilder = app(IndexBuilder::class);
        $this->indexRepository = app(IndexRepositoryInterface::class);
        $this->modelDiscovery = app(ModelDiscoveryInterface::class);

        $this->indexManager = new IndexManagerService(
            indexBuilder: $this->indexBuilder,
            indexRepository: $this->indexRepository,
            modelDiscovery: $this->modelDiscovery
        );
    }

    /**
     * Test that indexModel calls the index builder when the model should be indexed.
     */
    public function test_index_model_calls_index_builder_when_should_be_indexed(): void
    {
        // Arrange: Create a real model that should be indexed
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        // Act: Index the model
        $this->indexManager->indexModel($user);

        // Assert: The model should appear in the search index
        $stats = $this->indexManager->getStats();
        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertEquals(2, $stats['models'][User::class]['count']);
    }

    /**
     * Test that indexModel skips indexing when the model should not be indexed.
     */
    public function test_index_model_skips_when_should_not_be_indexed(): void
    {
        // Arrange: Create a real model that should NOT be indexed
        $user = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Act: Attempt to index the model
        $this->indexManager->indexModel($user);

        // Assert: The model should NOT appear in the search index
        $stats = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NonIndexableUser::class, $stats['models']);
    }

    /**
     * Test that indexModel removes previously indexed models when they become non-indexable.
     *
     * This test verifies the critical behavior: when a model that was previously indexable
     * becomes non-indexable (e.g., user type changes from 'user' to 'admin'), it should
     * be automatically removed from the search index to prevent stale entries.
     */
    public function test_index_model_removes_from_index_when_should_not_be_indexed(): void
    {
        // Arrange: Create a model that is initially indexable
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        // First, index the model
        $this->indexManager->indexModel($user);

        // Verify it was indexed successfully
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(2, $statsBefore['models'][User::class]['count']);

        // Change the model to non-indexable state
        $user->type = 'admin';
        $user->save();

        // Act: Call indexModel (should detect state change and remove)
        $this->indexManager->indexModel($user);

        // Assert: The model should be completely removed from index
        $statsAfter = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(User::class, $statsAfter['models']);
    }

    /**
     * Test that updateModelIndex removes and re-indexes when the model should be indexed.
     */
    public function test_update_model_index_removes_and_indexes_when_should_be_indexed(): void
    {
        // Arrange: Create an indexable model
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        // Initially index the model
        $this->indexManager->indexModel($user);

        // Verify it's in the index
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(2, $statsBefore['models'][User::class]['count']);

        // Change the model data
        $user->name = 'John Updated';
        $user->save();

        // Act: Update model index (should remove then re-add)
        $this->indexManager->updateModelIndex($user);

        // Assert: Model should still be indexed with updated content
        $statsAfter = $this->indexManager->getStats();
        $this->assertEquals(2, $statsAfter['models'][User::class]['count']);
    }

    /**
     * Test that updateModelIndex only removes when the model should not be indexed.
     *
     * Verifies that when a model becomes non-indexable, updateModelIndex removes it
     * from the index without attempting to re-index it.
     */
    public function test_update_model_index_only_removes_when_should_not_be_indexed(): void
    {
        // Arrange: Create an initially indexable model
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        // Index the model
        $this->indexManager->indexModel($user);

        // Verify it's indexed
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(2, $statsBefore['models'][User::class]['count']);

        // Change to non-indexable state
        $user->type = 'admin';
        $user->save();

        // Act: Update model index (should only remove, not index)
        $this->indexManager->updateModelIndex($user);

        // Assert: Model should be removed from index
        $statsAfter = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(User::class, $statsAfter['models']);
    }

    /**
     * Test that removeModel completely deletes a model from the index.
     */
    public function test_remove_model_deletes_from_index(): void
    {
        // Arrange: Create and index a model
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        $this->indexManager->indexModel($user);

        // Verify it's indexed
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(2, $statsBefore['models'][User::class]['count']);

        // Act: Remove model from index
        $this->indexManager->removeModel($user);

        // Assert: Model should be completely removed
        $statsAfter = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(User::class, $statsAfter['models']);
    }

    /**
     * Test that reindexAll rebuilds the entire index for all registered models.
     */
    public function test_reindex_all_reindexes_all_models(): void
    {
        // Arrange: Create multiple models of different types
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        $nonIndexableUser = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Manually index the models
        $this->indexManager->indexModel($user1);
        $this->indexManager->indexModel($user2);
        $this->indexManager->indexModel($nonIndexableUser);

        // Verify initial state - only indexable users are indexed
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(4, $statsBefore['models'][User::class]['count']);
        $this->assertArrayNotHasKey(NonIndexableUser::class, $statsBefore['models']);

        // Clear all indexes to simulate need for reindex
        FuzzyIndex::truncate();

        // Verify indexes are cleared
        $statsAfterTruncate = $this->indexManager->getStats();
        $this->assertEquals(0, $statsAfterTruncate['total_entries']);

        // Act: Reindex specific models (simulating reindexAll behavior)
        $this->indexManager->reindexModel(User::class);
        $this->indexManager->reindexModel(NonIndexableUser::class);

        // Assert: Only indexable models should be reindexed
        $statsAfter = $this->indexManager->getStats();
        $this->assertEquals(4, $statsAfter['models'][User::class]['count']);
        $this->assertArrayNotHasKey(NonIndexableUser::class, $statsAfter['models']);
    }

    /**
     * Test that getStats returns comprehensive statistics from the repository.
     */
    public function test_get_stats_returns_stats_from_repository(): void
    {
        // Arrange: Create and index a model
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'type' => 'user']);
        $this->indexManager->indexModel($user);

        // Act: Retrieve statistics
        $stats = $this->indexManager->getStats();

        // Assert: Statistics contain expected structure
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);
        $this->assertArrayHasKey(User::class, $stats['models']);
    }

    /**
     * Test that getPreciseModelStats returns detailed statistics with correct values.
     */
    public function test_get_precise_model_stats_returns_detailed_stats(): void
    {
        // Arrange: Create and index multiple models
        $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'type' => 'user']);
        $nonIndexableUser = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Index only the searchable users
        $this->indexManager->indexModel($user1);
        $this->indexManager->indexModel($user2);

        // Act: Get precise model statistics for User model
        $stats = $this->indexManager->getPreciseModelStats(User::class);

        // Assert: Statistics contain all required keys
        $this->assertArrayHasKey('total_records', $stats);
        $this->assertArrayHasKey('indexable_records', $stats);
        $this->assertArrayHasKey('indexed_entries', $stats);
        $this->assertArrayHasKey('estimated_indexed_models', $stats);
        $this->assertArrayHasKey('fields_per_model', $stats);
        $this->assertArrayHasKey('coverage_percentage', $stats);

        // Assert: Values are mathematically correct
        $this->assertEquals(2, $stats['total_records']);
        $this->assertEquals(2, $stats['indexable_records']);
        $this->assertEquals(4, $stats['indexed_entries']);
        $this->assertEquals(2, $stats['fields_per_model']);
        $this->assertEquals(2, $stats['estimated_indexed_models']);
        $this->assertEquals(100, $stats['coverage_percentage']);
    }

    /**
     * Test that reindexModel validates and rebuilds a specific model class.
     */
    public function test_reindex_model_validates_and_reindexes(): void
    {
        // Arrange: Create and initially index a model
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'type' => 'user']);
        $this->indexManager->indexModel($user);

        // Verify it's indexed
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(2, $statsBefore['models'][User::class]['count']);

        // Update the model data
        $user->name = 'Updated Name';
        $user->save();

        // Act: Reindex the entire User model class
        $this->indexManager->reindexModel(User::class);

        // Assert: Model should still be indexed with updated data
        $statsAfter = $this->indexManager->getStats();
        $this->assertEquals(2, $statsAfter['models'][User::class]['count']);
    }

    /**
     * Test that reindexModel throws an exception for non-searchable model classes.
     */
    public function test_reindex_model_throws_exception_for_non_searchable_model(): void
    {
        // Arrange: Use a non-existent model class that cannot be searchable
        $nonSearchableClass = 'Fuzzy\\Tests\\Fixtures\\NonExistentModel';

        // Act & Assert: Exception should be thrown during model validation
        $this->expectException(ModelNotSearchableException::class);
        $this->expectExceptionMessageMatches('/must implement.*MustFuzzySearch/i');

        $this->indexManager->reindexModel($nonSearchableClass);
    }

    /**
     * Test that reindexModel validates each model instance before indexing.
     *
     * Verifies that index corruption is prevented by ensuring only models implementing
     * MustFuzzySearch are added to the search index during the reindexing process.
     */
    public function test_reindex_model_validates_each_model_before_indexing(): void
    {
        // Arrange: Create valid searchable models
        $validUser = User::create(['name' => 'Valid User', 'email' => 'valid@example.com', 'type' => 'user']);
        $validUser2 = User::create(['name' => 'Valid User 2', 'email' => 'valid2@example.com', 'type' => 'user']);

        // Act: Reindex all users
        $this->indexManager->reindexModel(User::class);

        // Assert: Both valid users should be indexed (2 users × 2 fields = 4 entries)
        $stats = $this->indexManager->getStats();
        $this->assertEquals(4, $stats['models'][User::class]['count']);
    }

    /**
     * Test the complete transition flow from indexable to non-indexable state.
     *
     * Simulates a real-world scenario where a user changes from 'user' type (indexable)
     * to 'admin' type (non-indexable). The index should reflect this change without
     * leaving stale entries.
     */
    public function test_model_transition_from_indexable_to_non_indexable_removes_from_index(): void
    {
        // Arrange: Create an initially indexable model
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        // Act - Part 1: Index the model when it should be indexed
        $this->indexManager->indexModel($user);

        // Verify it's in the index
        $statsBefore = $this->indexManager->getStats();
        $this->assertEquals(2, $statsBefore['models'][User::class]['count']);

        // Change state to non-indexable
        $user->type = 'admin';
        $user->save();

        // Act - Part 2: Update model (should remove from index without re-adding)
        $this->indexManager->updateModelIndex($user);

        // Assert: Model should be completely removed from index
        $statsAfter = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(User::class, $statsAfter['models']);
    }

    /**
     * Test that getPreciseModelStats correctly handles an empty index state.
     *
     * When no records have been indexed, coverage percentage should be 0%
     * and indexed_entries should be 0.
     */
    public function test_get_precise_model_stats_handles_empty_index(): void
    {
        // Arrange: Clear any existing index entries
        FuzzyIndex::truncate();

        // Create models but do NOT index them
        $this->disableAutomaticIndexing();

        User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'type' => 'user']);
        User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'type' => 'user']);

        // Verify no automatic indexing occurred
        $this->assertEquals(0, FuzzyIndex::count(), 'Automatic indexing should be disabled for this test');

        // Act: Get precise model statistics without any indexing performed
        $stats = $this->indexManager->getPreciseModelStats(User::class);

        // Assert: Coverage should be 0% when nothing is indexed
        $this->assertEquals(2, $stats['total_records']);
        $this->assertEquals(2, $stats['indexable_records']);
        $this->assertEquals(0, $stats['indexed_entries']);
        $this->assertEquals(0, $stats['estimated_indexed_models']);
        $this->assertEquals(0, $stats['coverage_percentage']);

        $this->enableAutomaticIndexing();
    }

    /**
     * Test that getPreciseModelStats handles zero indexable records correctly.
     *
     * When all records are non-indexable, coverage should be 0% even if
     * the index contains no entries.
     */
    public function test_get_precise_model_stats_handles_zero_indexable_records(): void
    {
        // Arrange: Create only non-indexable models
        NonIndexableUser::create(['name' => 'Admin 1', 'email' => 'admin1@example.com', 'type' => 'admin']);
        NonIndexableUser::create(['name' => 'Admin 2', 'email' => 'admin2@example.com', 'type' => 'admin']);

        // Act: Get precise model statistics for non-indexable model
        $stats = $this->indexManager->getPreciseModelStats(NonIndexableUser::class);

        // Assert: Coverage should be 0% when no records are eligible for indexing
        $this->assertEquals(2, $stats['total_records']);
        $this->assertEquals(0, $stats['indexable_records']);
        $this->assertEquals(0, $stats['indexed_entries']);
        $this->assertEquals(0, $stats['estimated_indexed_models']);
        $this->assertEquals(0, $stats['coverage_percentage']);
    }

    /**
     * Test that a model becoming indexable after being non-indexable is properly indexed.
     *
     * Verifies that models can be added to the index when they transition from
     * a non-indexable state to an indexable state.
     */
    public function test_model_transition_from_non_indexable_to_indexable_adds_to_index(): void
    {
        // Arrange: Create a non-indexable model
        $user = NonIndexableUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Initially, it should not be indexed
        $this->indexManager->indexModel($user);
        $statsBefore = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NonIndexableUser::class, $statsBefore['models']);

        // Create a new indexable user to demonstrate the transition
        $indexableUser = User::create([
            'name' => 'Former Admin',
            'email' => 'former-admin@example.com',
            'type' => 'user',
        ]);

        // Act: Index the new indexable model
        $this->indexManager->indexModel($indexableUser);

        // Assert: Model should be added to index successfully
        $statsAfter = $this->indexManager->getStats();
        $this->assertEquals(2, $statsAfter['models'][User::class]['count']);
    }

    /**
     * Test that models can customize which events trigger indexing via IndexationLevel.
     *
     * Verifies that models can override getIndexationLevel() to control whether
     * create, update, or delete events trigger automatic index updates.
     */
    public function test_models_can_customize_indexation_level(): void
    {
        // Arrange: Create a model that only indexes on create events
        $user = CreateOnlyUser::create([
            'name' => 'Create Only User',
            'email' => 'create@example.com',
            'type' => 'user',
        ]);

        // Manually index to simulate create event
        $this->indexManager->indexModel($user);

        // Assert: Model should be indexed on create
        $statsAfterCreate = $this->indexManager->getStats();
        $this->assertArrayHasKey(CreateOnlyUser::class, $statsAfterCreate['models']);

        $initialCount = $statsAfterCreate['models'][CreateOnlyUser::class]['count'];

        // Update the model - should NOT trigger re-indexing with CREATE_ONLY
        $user->name = 'Updated Name';
        $user->save();

        // Assert: Count should remain the same (no update indexing occurred)
        $statsAfterUpdate = $this->indexManager->getStats();
        $this->assertEquals($initialCount, $statsAfterUpdate['models'][CreateOnlyUser::class]['count']);
    }

    /**
     * Test that models using CREATE_AND_UPDATE indexation level work correctly.
     */
    public function test_create_and_update_indexation_level(): void
    {
        // Arrange: Create a model that indexes on create and update
        $user = CreateAndUpdateUser::create([
            'name' => 'Create Update User',
            'email' => 'createupdate@example.com',
            'type' => 'user',
        ]);

        // Manually index to simulate create event
        $this->indexManager->indexModel($user);

        // Check stats after create
        $statsAfterCreate = $this->indexManager->getStats();

        // Assert: Model should be indexed on create
        $this->assertArrayHasKey(CreateAndUpdateUser::class, $statsAfterCreate['models']);

        $initialCount = $statsAfterCreate['models'][CreateAndUpdateUser::class]['count'] ?? 0;

        // Update the model - should trigger re-indexing
        $user->name = 'Updated Name';
        $user->save();

        // Simulate update event
        $this->indexManager->updateModelIndex($user);

        // Check stats after update
        $statsAfterUpdate = $this->indexManager->getStats();

        // Assert: Model should still be indexed with same count
        $this->assertArrayHasKey(CreateAndUpdateUser::class, $statsAfterUpdate['models']);
        $this->assertEquals($initialCount, $statsAfterUpdate['models'][CreateAndUpdateUser::class]['count']);

        // Delete the model - SHOULD trigger removal because CREATE_AND_UPDATE does NOT include delete
        $user->delete();
        $this->indexManager->removeModel($user);

        // Check stats after delete
        $statsAfterDelete = $this->indexManager->getStats();

        // Assert: Model should be REMOVED from index (delete not allowed by level)
        $this->assertArrayNotHasKey(CreateAndUpdateUser::class, $statsAfterDelete['models']);
    }

    /**
     * Test that models using UPDATE_ONLY indexation level work correctly.
     */
    public function test_update_only_indexation_level(): void
    {
        // Arrange: Create a model that only indexes on update
        $user = UpdateOnlyUser::create([
            'name' => 'Update Only User',
            'email' => 'update@example.com',
            'type' => 'user',
        ]);

        // Assert: Model should NOT be indexed on create
        $statsAfterCreate = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(UpdateOnlyUser::class, $statsAfterCreate['models']);

        // Update the model - should trigger indexing
        $user->name = 'Updated Name';
        $user->save();

        // Manually index to simulate update event
        $this->indexManager->indexModel($user);

        // Assert: Model should now be indexed after update
        $statsAfterUpdate = $this->indexManager->getStats();
        $this->assertArrayHasKey(UpdateOnlyUser::class, $statsAfterUpdate['models']);
        $this->assertEquals(2, $statsAfterUpdate['models'][UpdateOnlyUser::class]['count']);
    }

    /**
     * Test that models using DELETE_ONLY indexation level work correctly.
     */
    public function test_delete_only_indexation_level(): void
    {
        // Arrange: Create a model that only indexes on delete
        $user = DeleteOnlyUser::create([
            'name' => 'Delete Only User',
            'email' => 'delete@example.com',
            'type' => 'user',
        ]);

        // Manually add to index to simulate existing index entry
        $this->indexManager->indexModel($user);

        // Verify it's in index
        $statsBefore = $this->indexManager->getStats();
        $this->assertArrayHasKey(DeleteOnlyUser::class, $statsBefore['models']);
        $initialCount = $statsBefore['models'][DeleteOnlyUser::class]['count'];

        // Update the model - should NOT trigger re-indexing
        $user->name = 'Updated Name';
        $user->save();

        // Assert: Count should remain the same
        $statsAfterUpdate = $this->indexManager->getStats();
        $this->assertEquals($initialCount, $statsAfterUpdate['models'][DeleteOnlyUser::class]['count']);

        // Delete the model - should trigger removal
        $user->delete();
        $this->indexManager->removeModel($user);

        // Assert: Model should be removed from index
        $statsAfterDelete = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(DeleteOnlyUser::class, $statsAfterDelete['models']);
    }

    /**
     * Test that IndexationLevel enum correctly converts to events array.
     */
    public function test_indexation_level_enum_converts_to_events_array(): void
    {
        // Assert: NONE returns empty array
        $events = IndexationLevel::NONE->toEventsArray();
        $this->assertEquals([], $events);
        $this->assertFalse(IndexationLevel::NONE->hasEvent('create'));
        $this->assertFalse(IndexationLevel::NONE->hasEvent('update'));
        $this->assertFalse(IndexationLevel::NONE->hasEvent('delete'));

        // Assert: CREATE_ONLY returns only create event
        $events = IndexationLevel::CREATE_ONLY->toEventsArray();
        $this->assertEquals(['create'], $events);
        $this->assertTrue(IndexationLevel::CREATE_ONLY->hasEvent('create'));
        $this->assertFalse(IndexationLevel::CREATE_ONLY->hasEvent('update'));
        $this->assertFalse(IndexationLevel::CREATE_ONLY->hasEvent('delete'));

        // Assert: ALL returns all three events
        $events = IndexationLevel::ALL->toEventsArray();
        $this->assertEquals(['create', 'update', 'delete'], $events);
        $this->assertTrue(IndexationLevel::ALL->hasEvent('create'));
        $this->assertTrue(IndexationLevel::ALL->hasEvent('update'));
        $this->assertTrue(IndexationLevel::ALL->hasEvent('delete'));

        // Assert: CREATE_AND_UPDATE returns create and update
        $events = IndexationLevel::CREATE_AND_UPDATE->toEventsArray();
        $this->assertEquals(['create', 'update'], $events);
        $this->assertTrue(IndexationLevel::CREATE_AND_UPDATE->hasEvent('create'));
        $this->assertTrue(IndexationLevel::CREATE_AND_UPDATE->hasEvent('update'));
        $this->assertFalse(IndexationLevel::CREATE_AND_UPDATE->hasEvent('delete'));

        // Assert: UPDATE_AND_DELETE returns update and delete
        $events = IndexationLevel::UPDATE_AND_DELETE->toEventsArray();
        $this->assertEquals(['update', 'delete'], $events);

        // Assert: CREATE_AND_DELETE returns create and delete
        $events = IndexationLevel::CREATE_AND_DELETE->toEventsArray();
        $this->assertEquals(['create', 'delete'], $events);
    }

    /**
     * Test that backward compatibility is maintained for models without getIndexationLevel.
     */
    public function test_backward_compatibility_for_models_without_indexation_level(): void
    {
        // Arrange: Use a model that doesn't define getIndexationLevel (should default to ALL)
        $user = User::create([
            'name' => 'Compatibility User',
            'email' => 'compat@example.com',
            'type' => 'user',
        ]);

        // Assert: Model should be indexed on create (ALL default)
        $stats = $this->indexManager->getStats();
        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertEquals(2, $stats['models'][User::class]['count']);

        // Update the model
        $user->name = 'Updated Compat User';
        $user->save();

        // Assert: Update should also trigger re-indexing
        $statsAfterUpdate = $this->indexManager->getStats();
        $this->assertEquals(2, $statsAfterUpdate['models'][User::class]['count']);
    }

    /**
     * Test that models using NONE indexation level never auto-index on any event.
     *
     * NONE level means NO automatic indexing on create/update/delete events.
     * This is independent from shouldBeIndexed() which controls IF indexing is allowed.
     */
    public function test_none_indexation_level_never_auto_indexes(): void
    {
        // Arrange: Create a model with NONE indexation level and shouldBeIndexed() = true
        $user = NoneUser::create([
            'name' => 'None User',
            'email' => 'none@example.com',
            'type' => 'user',
        ]);

        // Assert: Model should NOT be auto-indexed on create (NONE level)
        $statsAfterCreate = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $statsAfterCreate['models']);

        // Update the model - should NOT trigger auto-indexing
        $user->name = 'Updated Name';
        $user->save();

        // Assert: Still no index entries
        $statsAfterUpdate = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $statsAfterUpdate['models']);

        // Delete the model - should NOT trigger auto-removal (nothing to remove)
        $user->delete();
        $this->indexManager->removeModel($user);

        // Assert: Still no index entries
        $statsAfterDelete = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $statsAfterDelete['models']);
    }

    /**
     * Test that NONE level still allows manual indexing.
     *
     * Even with NONE level, developers can manually index models.
     * This tests the separation of concerns: IndexationLevel controls AUTO events,
     * while manual calls always work regardless of the level.
     */
    public function test_none_indexation_level_allows_manual_indexing(): void
    {
        // Arrange: Create a model with NONE indexation level and shouldBeIndexed() = true
        $user = NoneUser::create([
            'name' => 'Manual User',
            'email' => 'manual@example.com',
            'type' => 'user',
        ]);

        // Initially, no index entries (auto-indexing is disabled)
        $statsBefore = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $statsBefore['models']);

        // Act: Manually index the model (bypassing auto events)
        $this->indexManager->indexModel($user);

        // Assert: Model should be indexed after manual call
        $statsAfter = $this->indexManager->getStats();
        $this->assertArrayHasKey(NoneUser::class, $statsAfter['models']);
        $this->assertEquals(2, $statsAfter['models'][NoneUser::class]['count']);

        // Manual update should also work
        $user->name = 'Updated Manual';
        $user->save();
        $this->indexManager->updateModelIndex($user);

        // Manual removal should work
        $this->indexManager->removeModel($user);
        $statsAfterRemove = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $statsAfterRemove['models']);
    }

    /**
     * Test that NONE level respects shouldBeIndexed().
     *
     * Even with manual indexing, shouldBeIndexed() must return true.
     * This demonstrates that IndexationLevel and shouldBeIndexed() are orthogonal:
     * - IndexationLevel: controls AUTO events
     * - shouldBeIndexed(): controls IF indexing is allowed at all (manual or auto)
     */
    public function test_none_indexation_level_respects_should_be_indexed(): void
    {
        // Arrange: Create a model with NONE level but shouldBeIndexed() = false
        $user = NoneUser::create([
            'name' => 'None Admin',
            'email' => 'noneadmin@example.com',
            'type' => 'admin',
        ]);

        // Act: Try to manually index
        $this->indexManager->indexModel($user);

        // Assert: No index entries because shouldBeIndexed() is false
        $stats = $this->indexManager->getStats();
        $this->assertArrayNotHasKey(NoneUser::class, $stats['models']);
    }

    /**
     * Disable automatic indexing for tests.
     *
     * This prevents model events from automatically creating index entries,
     * allowing precise control over when indexing occurs during testing.
     */
    private function disableAutomaticIndexing(): void
    {
        User::unsetEventDispatcher();
        NonIndexableUser::unsetEventDispatcher();
        CreateOnlyUser::unsetEventDispatcher();
        CreateAndUpdateUser::unsetEventDispatcher();
        UpdateOnlyUser::unsetEventDispatcher();
        DeleteOnlyUser::unsetEventDispatcher();
        NoneUser::unsetEventDispatcher();
    }

    /**
     * Re-enable automatic indexing after test completion.
     *
     * Restores the event dispatcher to ensure other tests behave normally.
     */
    private function enableAutomaticIndexing(): void
    {
        User::flushEventListeners();
        NonIndexableUser::flushEventListeners();
        CreateOnlyUser::flushEventListeners();
        CreateAndUpdateUser::flushEventListeners();
        UpdateOnlyUser::flushEventListeners();
        DeleteOnlyUser::flushEventListeners();
        NoneUser::flushEventListeners();
    }
}
