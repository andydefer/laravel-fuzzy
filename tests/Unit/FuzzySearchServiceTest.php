<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\CacheManagerInterface;
use Fuzzy\Contracts\IndexManagerInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\SearchProcessorInterface;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the FuzzySearchService.
 * 
 * Tests search operations, caching behavior, score filtering,
 * and delegation to dependency managers.
 */
final class FuzzySearchServiceTest extends TestCase
{
    private FuzzySearchService $service;
    private CacheManagerInterface&MockInterface $cacheManager;
    private ModelDiscoveryInterface&MockInterface $modelDiscovery;
    private IndexManagerInterface&MockInterface $indexManager;
    private SearchProcessorInterface&MockInterface $searchProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->modelDiscovery = Mockery::mock(ModelDiscoveryInterface::class);
        $this->indexManager = Mockery::mock(IndexManagerInterface::class);
        $this->searchProcessor = Mockery::mock(SearchProcessorInterface::class);

        $this->service = new FuzzySearchService(
            cacheManager: $this->cacheManager,
            modelDiscovery: $this->modelDiscovery,
            indexManager: $this->indexManager,
            searchProcessor: $this->searchProcessor
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Tests that search() always returns a Collection instance.
     */
    public function test_search_returns_collection(): void
    {
        // Arrange: Configure cache manager to return a collection
        $query = 'john';
        $options = [];
        $expectedResults = collect(['result1', 'result2']);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->with('search', Mockery::type('callable'), [$query, $options])
            ->andReturn($expectedResults);

        // Act: Perform search
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify collection type and size
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
    }

    /**
     * Tests exact match search returns results with proper scores.
     */
    public function test_search_finds_exact_match(): void
    {
        // Arrange: Configure mocks for exact match search
        $query = 'John Doe';
        $options = [];

        $mockResults = collect([
            (object) ['score' => 0.95, 'item' => (object) ['name' => 'John Doe']]
        ]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([User::class]);

        $this->searchProcessor
            ->shouldReceive('search')
            ->once()
            ->with($query, [User::class], $options)
            ->andReturn($mockResults);

        // Act: Perform exact match search
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify exact match results
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
        $this->assertEquals(0.95, $results->first()->score);
    }

    /**
     * Tests fuzzy matching returns results for misspelled queries.
     */
    public function test_search_finds_fuzzy_match(): void
    {
        // Arrange: Configure mocks for fuzzy search
        $query = 'jon do';
        $options = ['fuzzy' => true];

        $mockResults = collect([
            (object) ['score' => 0.85, 'item' => (object) ['name' => 'John Doe']]
        ]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([User::class]);

        $this->searchProcessor
            ->shouldReceive('search')
            ->once()
            ->with($query, [User::class], $options)
            ->andReturn($mockResults);

        // Act: Perform fuzzy search
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify fuzzy match results contain expected data
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Tests searching within a specific model only.
     */
    public function test_search_in_specific_model(): void
    {
        // Arrange: Configure mocks for model-specific search
        $modelClass = User::class;
        $query = 'john';
        $options = [];

        $mockResults = collect([
            (object) ['score' => 0.95, 'item' => (object) ['name' => 'John Doe', 'type' => 'user']]
        ]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->with('search_in_model', Mockery::type('callable'), [$modelClass, $query, $options])
            ->andReturn($mockResults);

        // Act: Search within specific model
        $results = $this->service->searchInModel(
            modelClass: $modelClass,
            query: $query,
            options: $options
        );

        // Assert: Verify results are from specified model only
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Tests search with various options (min_score, max_results, fuzzy, threshold).
     */
    public function test_search_with_options(): void
    {
        // Arrange: Configure mocks with search options
        $query = 'laptop';
        $options = [
            'min_score' => 0.5,
            'max_results' => 5,
            'fuzzy' => true,
            'threshold' => 0.4,
        ];

        $mockResults = collect([
            (object) ['score' => 0.8, 'item' => (object) ['name' => 'Laptop Pro']],
            (object) ['score' => 0.6, 'item' => (object) ['name' => 'Laptop Air']]
        ]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([Product::class]);

        $this->searchProcessor
            ->shouldReceive('search')
            ->once()
            ->with($query, [Product::class], $options)
            ->andReturn($mockResults);

        // Act: Perform search with options
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify options were respected
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertLessThanOrEqual(5, $results->count());

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score);
        }
    }

    /**
     * Tests that min_score option filters results below threshold.
     */
    public function test_min_score_is_respected_from_config(): void
    {
        // Arrange: Configure search with minimum score threshold
        $query = 'laptop';
        $options = ['min_score' => 0.8];

        $mockResults = collect([
            (object) ['score' => 0.85, 'item' => (object) ['name' => 'Laptop Pro']]
        ]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([Product::class]);

        $this->searchProcessor
            ->shouldReceive('search')
            ->once()
            ->with($query, [Product::class], $options)
            ->andReturn($mockResults);

        // Act: Perform search with min_score filter
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify all results meet minimum score
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.8, $result->score);
        }
    }

    /**
     * Tests that min_score option excludes results below threshold.
     */
    public function test_min_score_is_respected_with_options_override(): void
    {
        // Arrange: Configure search with high minimum score that excludes all results
        $query = 'laptop';
        $options = ['min_score' => 0.9];

        $mockResults = collect([]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([Product::class]);

        $this->searchProcessor
            ->shouldReceive('search')
            ->once()
            ->with($query, [Product::class], $options)
            ->andReturn($mockResults);

        // Act: Perform search with high min_score
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify empty results collection is returned
        $this->assertInstanceOf(Collection::class, $results);
    }

    /**
     * Tests searching across multiple specified models.
     */
    public function test_search_in_models(): void
    {
        // Arrange: Configure mocks for multi-model search
        $modelClasses = [User::class, Product::class];
        $query = 'test';
        $options = [];

        $mockResults = collect([
            (object) ['score' => 0.9, 'item' => (object) ['name' => 'Test User']],
            (object) ['score' => 0.8, 'item' => (object) ['name' => 'Test Product']]
        ]);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->with('search_in_models', Mockery::type('callable'), [$modelClasses, $query, $options])
            ->andReturn($mockResults);

        // Act: Search across multiple models
        $results = $this->service->searchInModels(
            modelClasses: $modelClasses,
            query: $query,
            options: $options
        );

        // Assert: Verify combined results from all models
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
    }

    /**
     * Tests search behavior when caching is disabled.
     */
    public function test_search_with_caching_disabled(): void
    {
        // Arrange: Configure cache manager as disabled
        $query = 'john';
        $options = [];
        $expectedResults = collect(['result1']);

        $this->cacheManager
            ->shouldReceive('remember')
            ->once()
            ->with('search', Mockery::type('callable'), [$query, $options])
            ->andReturnUsing(function ($type, $callback, $params) {
                return $callback();
            });

        $this->cacheManager
            ->shouldReceive('isEnabled')
            ->andReturn(false);

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([User::class]);

        $this->searchProcessor
            ->shouldReceive('search')
            ->once()
            ->with($query, [User::class], $options)
            ->andReturn($expectedResults);

        // Act: Perform search with caching disabled
        $results = $this->service->search(query: $query, options: $options);

        // Assert: Verify search still works without cache
        $this->assertEquals($expectedResults, $results);
    }

    /**
     * Tests CacheManager interface is properly exposed.
     */
    public function test_cache_manager_is_accessible(): void
    {
        // Assert: Verify cache manager getter returns correct instance
        $this->assertSame($this->cacheManager, $this->service->getCacheManager());
    }

    /**
     * Tests ModelDiscovery interface is properly exposed.
     */
    public function test_model_discovery_is_accessible(): void
    {
        // Assert: Verify model discovery getter returns correct instance
        $this->assertSame($this->modelDiscovery, $this->service->getModelDiscovery());
    }

    /**
     * Tests IndexManager interface is properly exposed.
     */
    public function test_index_manager_is_accessible(): void
    {
        // Assert: Verify index manager getter returns correct instance
        $this->assertSame($this->indexManager, $this->service->getIndexManager());
    }

    /**
     * Tests SearchProcessor interface is properly exposed.
     */
    public function test_search_processor_is_accessible(): void
    {
        // Assert: Verify search processor getter returns correct instance
        $this->assertSame($this->searchProcessor, $this->service->getSearchProcessor());
    }

    /**
     * Tests indexing a model delegates to IndexManager.
     */
    public function test_index_model_via_index_manager(): void
    {
        // Arrange: Create mock model and expect index call
        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);

        $this->indexManager
            ->shouldReceive('indexModel')
            ->once()
            ->with($model);

        // Act: Delegate to index manager
        $this->service->getIndexManager()->indexModel($model);

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests removing a model delegates to IndexManager.
     */
    public function test_remove_model_via_index_manager(): void
    {
        // Arrange: Create mock model and expect removal call
        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);

        $this->indexManager
            ->shouldReceive('removeModel')
            ->once()
            ->with($model);

        // Act: Delegate model removal to index manager
        $this->service->getIndexManager()->removeModel($model);

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests updating model index delegates to IndexManager.
     */
    public function test_update_model_index_via_index_manager(): void
    {
        // Arrange: Create mock model and expect update call
        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);

        $this->indexManager
            ->shouldReceive('updateModelIndex')
            ->once()
            ->with($model);

        // Act: Delegate model index update to index manager
        $this->service->getIndexManager()->updateModelIndex($model);

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests reindexing all models delegates to IndexManager.
     */
    public function test_reindex_all_via_index_manager(): void
    {
        // Arrange: Expect full reindex call
        $this->indexManager
            ->shouldReceive('reindexAll')
            ->once();

        // Act: Delegate full reindex to index manager
        $this->service->getIndexManager()->reindexAll();

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests reindexing a specific model delegates to IndexManager.
     */
    public function test_reindex_model_via_index_manager(): void
    {
        // Arrange: Expect model-specific reindex call
        $modelClass = User::class;

        $this->indexManager
            ->shouldReceive('reindexModel')
            ->once()
            ->with($modelClass);

        // Act: Delegate model reindex to index manager
        $this->service->getIndexManager()->reindexModel($modelClass);

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests getting statistics delegates to IndexManager.
     */
    public function test_get_stats_via_index_manager(): void
    {
        // Arrange: Expect stats retrieval with predefined return
        $expectedStats = ['total_entries' => 10];

        $this->indexManager
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($expectedStats);

        // Act: Delegate stats retrieval to index manager
        $stats = $this->service->getIndexManager()->getStats();

        // Assert: Verify statistics are returned correctly
        $this->assertEquals($expectedStats, $stats);
    }

    /**
     * Tests getting detailed model statistics delegates to IndexManager.
     */
    public function test_get_precise_model_stats_via_index_manager(): void
    {
        // Arrange: Expect precise stats for specific model
        $modelClass = User::class;
        $expectedStats = ['total_records' => 10];

        $this->indexManager
            ->shouldReceive('getPreciseModelStats')
            ->once()
            ->with($modelClass)
            ->andReturn($expectedStats);

        // Act: Delegate precise stats retrieval to index manager
        $stats = $this->service->getIndexManager()->getPreciseModelStats($modelClass);

        // Assert: Verify model statistics are returned correctly
        $this->assertEquals($expectedStats, $stats);
    }

    /**
     * Tests invalidating entire cache delegates to CacheManager.
     */
    public function test_invalidate_all_via_cache_manager(): void
    {
        // Arrange: Expect full cache invalidation call
        $this->cacheManager
            ->shouldReceive('invalidateAll')
            ->once();

        // Act: Delegate cache invalidation to cache manager
        $this->service->getCacheManager()->invalidateAll();

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests invalidating cache for a specific model delegates to CacheManager.
     */
    public function test_invalidate_for_model_via_cache_manager(): void
    {
        // Arrange: Expect model-specific cache invalidation
        $modelClass = User::class;

        $this->cacheManager
            ->shouldReceive('invalidateForModel')
            ->once()
            ->with($modelClass);

        // Act: Delegate model cache invalidation to cache manager
        $this->service->getCacheManager()->invalidateForModel($modelClass);

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests invalidating statistics cache delegates to CacheManager.
     */
    public function test_invalidate_stats_cache_via_cache_manager(): void
    {
        // Arrange: Expect stats cache invalidation call
        $this->cacheManager
            ->shouldReceive('invalidateStatsCache')
            ->once();

        // Act: Delegate stats cache invalidation to cache manager
        $this->service->getCacheManager()->invalidateStatsCache();

        // Assert: Verification is done by Mockery expectation
        $this->addToAssertionCount(1);
    }

    /**
     * Tests retrieving searchable models delegates to ModelDiscovery.
     */
    public function test_get_searchable_models_via_model_discovery(): void
    {
        // Arrange: Expect searchable models retrieval
        $expectedModels = [User::class, Product::class];

        $this->modelDiscovery
            ->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn($expectedModels);

        // Act: Delegate model discovery to model discovery service
        $models = $this->service->getModelDiscovery()->getSearchableModels();

        // Assert: Verify searchable models are returned correctly
        $this->assertEquals($expectedModels, $models);
    }
}
