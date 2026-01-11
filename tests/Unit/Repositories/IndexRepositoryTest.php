<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Repositories;

use Fuzzy\Tests\TestCase;
use Fuzzy\Repositories\IndexRepository;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\SearchContext;
use Fuzzy\ValueObjects\IndexData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class IndexRepositoryTest extends TestCase
{
    private IndexRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new IndexRepository();
    }


    public function test_get_index_data_for_model_with_empty_database(): void
    {
        // Act
        $data = $this->repository->getIndexDataForModel(User::class);

        // Assert
        $this->assertArrayHasKey('wordIndex', $data);
        $this->assertArrayHasKey('itemMap', $data);
        $this->assertArrayHasKey('modelIndex', $data);
        $this->assertArrayHasKey('rawEntries', $data);

        $this->assertEmpty($data['wordIndex']);
        $this->assertEmpty($data['itemMap']);
        $this->assertEmpty($data['modelIndex']);
    }

    public function test_get_index_data_for_model_with_data(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'John Doe',
                'normalized_value' => 'john doe',
                'words' => ['john', 'doe'],
                'weight' => 1.0,
                'metadata' => [
                    'word_count' => 2,
                    'value_length' => 8,
                    'normalized_length' => 7,
                ],
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user->id,
                'field' => 'email',
            ],
            [
                'original_value' => 'john@example.com',
                'normalized_value' => 'john@example.com',
                'words' => ['john', 'example', 'com'],
                'weight' => 1.0,
                'metadata' => [
                    'word_count' => 3,
                    'value_length' => 16,
                    'normalized_length' => 16,
                ],
            ]
        );

        // Act
        $data = $this->repository->getIndexDataForModel(User::class);

        // Assert
        $this->assertArrayHasKey('john', $data['wordIndex']);
        $this->assertArrayHasKey('doe', $data['wordIndex']);
        $this->assertArrayHasKey('example', $data['wordIndex']);
        $this->assertArrayHasKey('com', $data['wordIndex']);

        $userKey = User::class . '_' . $user->id;
        $this->assertArrayHasKey($userKey, $data['itemMap']);
        $this->assertArrayHasKey($userKey, $data['modelIndex']);
    }

    public function test_get_index_data_for_model_with_model_ids_filter(): void
    {
        // Arrange
        $user1 = User::create(['name' => 'User One', 'email' => 'one@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User Two', 'email' => 'two@example.com', 'type' => 'user']);

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user1->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'User One',
                'normalized_value' => 'user one',
                'words' => ['user', 'one'],
                'weight' => 1.0,
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user2->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'User Two',
                'normalized_value' => 'user two',
                'words' => ['user', 'two'],
                'weight' => 1.0,
            ]
        );

        // Act: Get data only for user1
        $data = $this->repository->getIndexDataForModel(User::class, [$user1->id]);

        // Assert
        $user1Key = User::class . '_' . $user1->id;
        $user2Key = User::class . '_' . $user2->id;

        $this->assertArrayHasKey($user1Key, $data['itemMap']);
        $this->assertArrayNotHasKey($user2Key, $data['itemMap']);
    }

    public function test_get_index_data_skips_short_words(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'A B', // Short words
            'email' => 'ab@cd.ef',
            'type' => 'user',
        ]);

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'A B',
                'normalized_value' => 'a b',
                'words' => ['a', 'b'],
                'weight' => 1.0,
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user->id,
                'field' => 'email',
            ],
            [
                'original_value' => 'ab@cd.ef',
                'normalized_value' => 'ab@cd.ef',
                'words' => ['ab', 'cd', 'ef'],
                'weight' => 1.0,
            ]
        );

        // Act
        $data = $this->repository->getIndexDataForModel(User::class);

        // Assert: Should skip words shorter than 2 characters
        $this->assertArrayNotHasKey('a', $data['wordIndex']);
        $this->assertArrayNotHasKey('b', $data['wordIndex']);
        $this->assertArrayHasKey('ab', $data['wordIndex']);
        $this->assertArrayHasKey('cd', $data['wordIndex']);
        $this->assertArrayHasKey('ef', $data['wordIndex']);
    }

    public function test_get_models_batch_empty_ids(): void
    {
        $models = $this->repository->getModelsBatch(User::class, []);

        $this->assertInstanceOf(Collection::class, $models);
        $this->assertTrue($models->isEmpty());
    }

    public function test_get_models_batch_with_ids(): void
    {
        // Arrange
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com', 'type' => 'user']);

        // Act
        $models = $this->repository->getModelsBatch(User::class, [$user1->id, $user2->id]);

        // Assert
        $this->assertCount(2, $models);
        $this->assertEquals([$user1->id, $user2->id], $models->pluck('id')->sort()->values()->toArray());
    }

    public function test_preload_models(): void
    {
        // Arrange
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'type' => 'user']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'type' => 'user']);

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user1->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'User 1',
                'normalized_value' => 'user 1',
                'words' => ['user', '1'],
                'weight' => 1.0,
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user2->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'User 2',
                'normalized_value' => 'user 2',
                'words' => ['user', '2'],
                'weight' => 1.0,
            ]
        );

        // Create context with index data
        $indexData = $this->repository->getIndexDataForModel(User::class);

        $normalizer = new \Fuzzy\Services\StringNormalizer();
        $query = \Fuzzy\ValueObjects\SearchQuery::create('test', $normalizer);
        $options = new \Fuzzy\Data\SearchOptionsData();

        // CORRECTION : Utiliser createStub() au lieu de createMock()
        $indexBuilder = $this->createStub(\Fuzzy\Services\IndexBuilder::class);
        $scoringEngine = $this->createStub(\Fuzzy\Services\Scoring\ScoringEngine::class);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new \Fuzzy\Services\SimilarityCalculator(),
            $indexBuilder,
            $this->repository,
            $scoringEngine,
            $indexData
        );

        // Act
        $this->repository->preloadModels($context);

        // Assert
        $modelsMap = $this->repository->getPreloadedModelsMap();

        $user1Key = User::class . '_' . $user1->id;
        $user2Key = User::class . '_' . $user2->id;

        $this->assertArrayHasKey($user1Key, $modelsMap);
        $this->assertArrayHasKey($user2Key, $modelsMap);

        $this->assertInstanceOf(User::class, $modelsMap[$user1Key]);
        $this->assertInstanceOf(User::class, $modelsMap[$user2Key]);
    }

    public function test_preload_models_with_empty_context(): void
    {
        // Arrange
        $indexData = IndexData::fromArray(['itemMap' => []]);
        $normalizer = new \Fuzzy\Services\StringNormalizer();
        $query = \Fuzzy\ValueObjects\SearchQuery::create('test', $normalizer);
        $options = new \Fuzzy\Data\SearchOptionsData();

        // CORRECTION : Utiliser createStub() au lieu de createMock()
        $indexBuilder = $this->createStub(\Fuzzy\Services\IndexBuilder::class);
        $scoringEngine = $this->createStub(\Fuzzy\Services\Scoring\ScoringEngine::class);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new \Fuzzy\Services\SimilarityCalculator(),
            $indexBuilder,
            $this->repository,
            $scoringEngine,
            []
        );

        // Act
        $this->repository->preloadModels($context);

        // Assert
        $modelsMap = $this->repository->getPreloadedModelsMap();
        $this->assertEmpty($modelsMap);
    }

    public function test_get_stats_empty(): void
    {
        // Act
        $stats = $this->repository->getStats();

        // Assert
        $this->assertEquals(0, $stats['total_entries']);
        $this->assertEmpty($stats['models']);
    }

    public function test_get_stats_with_data(): void
    {
        // Arrange
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com', 'type' => 'user']);
        $product = Product::create(['name' => 'Product', 'description' => 'Test', 'price' => 100]);

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'Test',
                'normalized_value' => 'test',
                'words' => ['test'],
                'weight' => 1.0,
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => User::class,
                'indexable_id' => $user->id,
                'field' => 'email',
            ],
            [
                'original_value' => 'test@example.com',
                'normalized_value' => 'test@example.com',
                'words' => ['test', 'example', 'com'],
                'weight' => 1.0,
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => Product::class,
                'indexable_id' => $product->id,
                'field' => 'name',
            ],
            [
                'original_value' => 'Product',
                'normalized_value' => 'product',
                'words' => ['product'],
                'weight' => 1.0,
            ]
        );

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => Product::class,
                'indexable_id' => $product->id,
                'field' => 'description',
            ],
            [
                'original_value' => 'Test',
                'normalized_value' => 'test',
                'words' => ['test'],
                'weight' => 0.8,
            ]
        );

        // Act
        $stats = $this->repository->getStats();

        // Assert
        $this->assertEquals(4, $stats['total_entries']); // 2 users fields + 2 product fields
        $this->assertArrayHasKey(User::class, $stats['models']);
        $this->assertArrayHasKey(Product::class, $stats['models']);

        $this->assertEquals(2, $stats['models'][User::class]['count']);
        $this->assertArrayHasKey('name', $stats['models'][User::class]['fields']);
        $this->assertArrayHasKey('email', $stats['models'][User::class]['fields']);
    }

    public function test_process_index_entry(): void
    {
        // Use reflection to test private method
        $method = new \ReflectionMethod($this->repository, 'processIndexEntry');
        $method->setAccessible(true);

        $wordIndex = [];
        $itemMap = [];

        $entry = new \stdClass();
        $entry->indexable_type = 'User';
        $entry->indexable_id = 1;
        $entry->words = ['test', 'entry'];
        $entry->field = 'name';
        $entry->original_value = 'Test Entry';
        $entry->weight = 1.0;

        // Act - Passez les variables par référence
        $method->invokeArgs($this->repository, [$entry, &$wordIndex, &$itemMap]);

        // Assert
        $this->assertArrayHasKey('test', $wordIndex);
        $this->assertArrayHasKey('entry', $wordIndex);
        $this->assertArrayHasKey('User_1', $itemMap);

        $this->assertCount(1, $wordIndex['test']);
        $this->assertEquals('User', $wordIndex['test'][0]['indexable_type']);
        $this->assertEquals(1, $wordIndex['test'][0]['indexable_id']);
        $this->assertEquals('name', $wordIndex['test'][0]['field']);
    }

    public function test_process_index_entry_skips_short_words(): void
    {
        $method = new \ReflectionMethod($this->repository, 'processIndexEntry');
        $method->setAccessible(true);

        $wordIndex = [];
        $itemMap = [];

        $entry = new \stdClass();
        $entry->indexable_type = 'User';
        $entry->indexable_id = 1;
        $entry->words = ['a', 'ab']; // 'a' is too short
        $entry->field = 'name';
        $entry->original_value = 'A Ab';
        $entry->weight = 1.0;

        // Act - Passez les variables par référence
        $method->invokeArgs($this->repository, [$entry, &$wordIndex, &$itemMap]);

        // Assert
        $this->assertArrayNotHasKey('a', $wordIndex); // Skipped
        $this->assertArrayHasKey('ab', $wordIndex); // Included
        $this->assertArrayHasKey('User_1', $itemMap);
    }
}
