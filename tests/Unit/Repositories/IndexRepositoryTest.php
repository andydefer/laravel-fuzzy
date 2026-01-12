<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Repositories;

use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Repositories\IndexRepository;
use Fuzzy\SearchContext;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\IndexData;
use Fuzzy\ValueObjects\SearchQuery;
use Illuminate\Support\Collection;

/**
 * Unit tests for IndexRepository.
 */
final class IndexRepositoryTest extends TestCase
{
    private IndexRepository $repository;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new IndexRepository();
    }

    /**
     * @test
     */
    public function test_returns_empty_index_data_for_model_with_empty_database(): void
    {
        // Arrange : Prepare repository with empty database

        // Act : Get index data for non-existent model entries
        $data = $this->repository->getIndexDataForModel(User::class);

        // Assert : Verify all index structure keys exist but are empty
        $this->assertArrayHasKey('wordIndex', $data);
        $this->assertArrayHasKey('itemMap', $data);
        $this->assertArrayHasKey('modelIndex', $data);
        $this->assertArrayHasKey('rawEntries', $data);

        $this->assertEmpty($data['wordIndex']);
        $this->assertEmpty($data['itemMap']);
        $this->assertEmpty($data['modelIndex']);
    }

    /**
     * @test
     */
    public function test_returns_index_data_for_model_with_existing_data(): void
    {
        // Arrange : Create a user and its index entries
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        $this->createIndexEntryForUserField(
            userId: $user->id,
            field: 'name',
            originalValue: 'John Doe',
            normalizedValue: 'john doe',
            words: ['john', 'doe']
        );

        $this->createIndexEntryForUserField(
            userId: $user->id,
            field: 'email',
            originalValue: 'john@example.com',
            normalizedValue: 'john@example.com',
            words: ['john', 'example', 'com']
        );

        // Act : Retrieve index data for the user model
        $data = $this->repository->getIndexDataForModel(User::class);

        // Assert : Verify words are indexed and user appears in maps
        $this->assertArrayHasKey('john', $data['wordIndex']);
        $this->assertArrayHasKey('doe', $data['wordIndex']);
        $this->assertArrayHasKey('example', $data['wordIndex']);
        $this->assertArrayHasKey('com', $data['wordIndex']);

        $userKey = User::class . '_' . $user->id;
        $this->assertArrayHasKey($userKey, $data['itemMap']);
        $this->assertArrayHasKey($userKey, $data['modelIndex']);
    }

    /**
     * @test
     */
    public function test_filters_index_data_by_specific_model_ids(): void
    {
        // Arrange : Create two users with index entries
        $user1 = User::create(['name' => 'User One', 'email' => 'one@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User Two', 'email' => 'two@example.com', 'type' => 'user']);

        $this->createIndexEntryForUserField(
            userId: $user1->id,
            field: 'name',
            originalValue: 'User One',
            normalizedValue: 'user one',
            words: ['user', 'one']
        );

        $this->createIndexEntryForUserField(
            userId: $user2->id,
            field: 'name',
            originalValue: 'User Two',
            normalizedValue: 'user two',
            words: ['user', 'two']
        );

        // Act : Get index data only for the first user
        $data = $this->repository->getIndexDataForModel(User::class, [$user1->id]);

        // Assert : Verify only first user appears in item map
        $user1Key = User::class . '_' . $user1->id;
        $user2Key = User::class . '_' . $user2->id;

        $this->assertArrayHasKey($user1Key, $data['itemMap']);
        $this->assertArrayNotHasKey($user2Key, $data['itemMap']);
    }

    /**
     * @test
     */
    public function test_skips_short_words_when_building_index(): void
    {
        // Arrange : Create a user with short words in name field
        $user = User::create([
            'name' => 'A B',
            'email' => 'ab@cd.ef',
            'type' => 'user',
        ]);

        $this->createIndexEntryForUserField(
            userId: $user->id,
            field: 'name',
            originalValue: 'A B',
            normalizedValue: 'a b',
            words: ['a', 'b']
        );

        $this->createIndexEntryForUserField(
            userId: $user->id,
            field: 'email',
            originalValue: 'ab@cd.ef',
            normalizedValue: 'ab@cd.ef',
            words: ['ab', 'cd', 'ef']
        );

        // Act : Retrieve index data
        $data = $this->repository->getIndexDataForModel(User::class);

        // Assert : Verify short words are excluded while longer words are included
        $this->assertArrayNotHasKey('a', $data['wordIndex']);
        $this->assertArrayNotHasKey('b', $data['wordIndex']);
        $this->assertArrayHasKey('ab', $data['wordIndex']);
        $this->assertArrayHasKey('cd', $data['wordIndex']);
        $this->assertArrayHasKey('ef', $data['wordIndex']);
    }

    /**
     * @test
     */
    public function test_returns_empty_collection_for_empty_ids_batch(): void
    {
        // Arrange : Prepare repository with empty ID list

        // Act : Get models batch with empty IDs
        $models = $this->repository->getModelsBatch(User::class, []);

        // Assert : Verify empty collection is returned
        $this->assertInstanceOf(Collection::class, $models);
        $this->assertTrue($models->isEmpty());
    }

    /**
     * @test
     */
    public function test_returns_specific_models_for_given_ids_batch(): void
    {
        // Arrange : Create three users in database
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'type' => 'user']);

        // Act : Retrieve only the first two users by their IDs
        $models = $this->repository->getModelsBatch(User::class, [$user1->id, $user2->id]);

        // Assert : Verify correct models are returned in correct order
        $this->assertCount(2, $models);
        $this->assertEquals([$user1->id, $user2->id], $models->pluck('id')->sort()->values()->toArray());
    }

    /**
     * @test
     */
    public function test_preloads_models_into_context_cache(): void
    {
        // Arrange : Create two users with index entries and prepare search context
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);

        $this->createIndexEntryForUserField(
            userId: $user1->id,
            field: 'name',
            originalValue: 'User 1',
            normalizedValue: 'user 1',
            words: ['user', '1']
        );

        $this->createIndexEntryForUserField(
            userId: $user2->id,
            field: 'name',
            originalValue: 'User 2',
            normalizedValue: 'user 2',
            words: ['user', '2']
        );

        $indexData = $this->repository->getIndexDataForModel(User::class);
        $context = $this->createSearchContext($indexData);

        // Act : Preload models into repository cache
        $this->repository->preloadModels($context);

        // Assert : Verify models are cached and accessible by their keys
        $modelsMap = $this->repository->getPreloadedModelsMap();

        $user1Key = User::class . '_' . $user1->id;
        $user2Key = User::class . '_' . $user2->id;

        $this->assertArrayHasKey($user1Key, $modelsMap);
        $this->assertArrayHasKey($user2Key, $modelsMap);

        $this->assertInstanceOf(User::class, $modelsMap[$user1Key]);
        $this->assertInstanceOf(User::class, $modelsMap[$user2Key]);
    }

    /**
     * @test
     */
    public function test_handles_preload_with_empty_search_context(): void
    {
        // Arrange : Create search context with empty index data
        $context = $this->createSearchContext([]);

        // Act : Attempt to preload models with empty context
        $this->repository->preloadModels($context);

        // Assert : Verify models map remains empty
        $modelsMap = $this->repository->getPreloadedModelsMap();
        $this->assertEmpty($modelsMap);
    }

    /**
     * @test
     */
    public function test_returns_empty_statistics_with_no_data(): void
    {
        // Arrange : Prepare empty database

        // Act : Get statistics from repository
        $stats = $this->repository->getStats();

        // Assert : Verify zero entries and empty models array
        $this->assertEquals(0, $stats['total_entries']);
        $this->assertEmpty($stats['models']);
    }

    /**
     * @test
     */
    public function test_returns_statistics_with_indexed_data(): void
    {
        // Arrange : Create users and products with multiple index entries
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);
        $product = Product::create(['name' => 'Product', 'description' => 'Test', 'price' => 100]);

        $this->createIndexEntryForUserField(
            userId: $user->id,
            field: 'name',
            originalValue: 'Test',
            normalizedValue: 'test',
            words: ['test']
        );

        $this->createIndexEntryForUserField(
            userId: $user->id,
            field: 'email',
            originalValue: 'test@example.com',
            normalizedValue: 'test@example.com',
            words: ['test', 'example', 'com']
        );

        $this->createIndexEntryForProductField(
            productId: $product->id,
            field: 'name',
            originalValue: 'Product',
            normalizedValue: 'product',
            words: ['product']
        );

        $this->createIndexEntryForProductField(
            productId: $product->id,
            field: 'description',
            originalValue: 'Test',
            normalizedValue: 'test',
            words: ['test']
        );

        // Act : Retrieve statistics
        $stats = $this->repository->getStats();

        // Assert : Verify correct counts and structure
        $this->assertEquals(4, $stats['total_entries']);
        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertArrayHasKey(Product::class, $stats['models']);

        $this->assertEquals(2, $stats['models'][User::class]['count']);
        $this->assertArrayHasKey('name', $stats['models'][User::class]['fields']);
        $this->assertArrayHasKey('email', $stats['models'][User::class]['fields']);
    }

    /**
     * Helper method to create index entries for user fields.
     */
    private function createIndexEntryForUserField(int $userId, string $field, string $originalValue, string $normalizedValue, array $words): void
    {
        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $userId,
                'field' => $field,
            ],
            [
                'original_value' => $originalValue,
                'normalized_value' => $normalizedValue,
                'words' => $words,
                'weight' => 1.0,
                'metadata' => [
                    'word_count' => count($words),
                    'value_length' => strlen($originalValue),
                    'normalized_length' => strlen($normalizedValue),
                ],
            ]
        );
    }

    /**
     * Helper method to create index entries for product fields.
     */
    private function createIndexEntryForProductField(int $productId, string $field, string $originalValue, string $normalizedValue, array $words): void
    {
        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => Product::class,
                'indexable_id' => $productId,
                'field' => $field,
            ],
            [
                'original_value' => $originalValue,
                'normalized_value' => $normalizedValue,
                'words' => $words,
                'weight' => 1.0,
            ]
        );
    }

    /**
     * Helper method to create a search context for testing.
     */
    private function createSearchContext(array $indexData): SearchContext
    {
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();
        $indexBuilder = $this->createStub(IndexBuilder::class);
        $scoringEngine = $this->createStub(ScoringEngine::class);

        return new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $indexBuilder,
            indexRepository: $this->repository,
            scoringEngine: $scoringEngine,
            indexDataArray: $indexData
        );
    }
}
