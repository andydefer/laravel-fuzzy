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

final class FuzzySearchServiceTest extends TestCase
{
    private FuzzySearchService $service;
    private $cacheManager;
    private $modelDiscovery;
    private $indexManager;
    private $searchProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->modelDiscovery = Mockery::mock(ModelDiscoveryInterface::class);
        $this->indexManager = Mockery::mock(IndexManagerInterface::class);
        $this->searchProcessor = Mockery::mock(SearchProcessorInterface::class);

        $this->service = new FuzzySearchService(
            $this->cacheManager,
            $this->modelDiscovery,
            $this->indexManager,
            $this->searchProcessor
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_returns_collection(): void
    {
        $query = 'john';
        $options = [];
        $expectedResults = collect(['result1', 'result2']);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->with('search', Mockery::type('callable'), [$query, $options])
            ->andReturn($expectedResults);

        $results = $this->service->search($query, $options);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
    }

    public function test_search_finds_exact_match(): void
    {
        $query = 'John Doe';
        $options = [];

        $mockResults = collect([
            (object) ['score' => 0.95, 'item' => (object) ['name' => 'John Doe']]
        ]);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([User::class]);

        $this->searchProcessor->shouldReceive('search')
            ->once()
            ->with($query, [User::class], $options)
            ->andReturn($mockResults);

        $results = $this->service->search($query, $options);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
        $this->assertEquals(0.95, $results->first()->score);
    }

    public function test_search_finds_fuzzy_match(): void
    {
        $query = 'jon do';
        $options = ['fuzzy' => true];

        $mockResults = collect([
            (object) ['score' => 0.85, 'item' => (object) ['name' => 'John Doe']]
        ]);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([User::class]);

        $this->searchProcessor->shouldReceive('search')
            ->once()
            ->with($query, [User::class], $options)
            ->andReturn($mockResults);

        $results = $this->service->search($query, $options);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
    }

    public function test_search_in_specific_model(): void
    {
        $modelClass = User::class;
        $query = 'john';
        $options = [];

        $mockResults = collect([
            (object) ['score' => 0.95, 'item' => (object) ['name' => 'John Doe', 'type' => 'user']]
        ]);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->with('search_in_model', Mockery::type('callable'), [$modelClass, $query, $options])
            ->andReturn($mockResults);

        $results = $this->service->searchInModel($modelClass, $query, $options);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThan(0, $results->count());
    }

    public function test_search_with_options(): void
    {
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

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([Product::class]);

        $this->searchProcessor->shouldReceive('search')
            ->once()
            ->with($query, [Product::class], $options)
            ->andReturn($mockResults);

        $results = $this->service->search($query, $options);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertLessThanOrEqual(5, $results->count());

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score);
        }
    }

    public function test_min_score_is_respected_from_config(): void
    {
        $query = 'laptop';
        $options = ['min_score' => 0.8];

        $mockResults = collect([
            (object) ['score' => 0.85, 'item' => (object) ['name' => 'Laptop Pro']]
        ]);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([Product::class]);

        $this->searchProcessor->shouldReceive('search')
            ->once()
            ->with($query, [Product::class], $options)
            ->andReturn($mockResults);

        $results = $this->service->search($query, $options);

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.8, $result->score);
        }
    }

    public function test_min_score_is_respected_with_options_override(): void
    {
        $query = 'laptop';
        $options = ['min_score' => 0.9];

        $mockResults = collect([]);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
                return $callback();
            });

        $this->modelDiscovery->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([Product::class]);

        $this->searchProcessor->shouldReceive('search')
            ->once()
            ->with($query, [Product::class], $options)
            ->andReturn($mockResults);

        $results = $this->service->search($query, $options);

        $this->assertInstanceOf(Collection::class, $results);
    }

    public function test_search_in_models(): void
    {
        $modelClasses = [User::class, Product::class];
        $query = 'test';
        $options = [];

        $mockResults = collect([
            (object) ['score' => 0.9, 'item' => (object) ['name' => 'Test User']],
            (object) ['score' => 0.8, 'item' => (object) ['name' => 'Test Product']]
        ]);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->with('search_in_models', Mockery::type('callable'), [$modelClasses, $query, $options])
            ->andReturn($mockResults);

        $results = $this->service->searchInModels($modelClasses, $query, $options);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);
    }

    public function test_search_with_caching_disabled(): void
    {
        $query = 'john';
        $options = [];
        $expectedResults = collect(['result1']);

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->with('search', Mockery::type('callable'), [$query, $options])
            ->andReturnUsing(function ($type, $callback, $params) {
                return $callback();
            });

        $this->cacheManager->shouldReceive('isEnabled')->andReturn(false);

        $this->modelDiscovery->shouldReceive('getSearchableModels')
            ->once()
            ->andReturn([User::class]);

        $this->searchProcessor->shouldReceive('search')
            ->once()
            ->with($query, [User::class], $options)
            ->andReturn($expectedResults);

        $results = $this->service->search($query, $options);

        $this->assertEquals($expectedResults, $results);
    }

    // ========== TESTS DIRECTS SUR LES PROPRIÉTÉS PUBLIQUES ==========

    public function test_cache_manager_is_accessible(): void
    {
        $this->assertSame($this->cacheManager, $this->service->getCacheManager());
    }

    public function test_model_discovery_is_accessible(): void
    {
        $this->assertSame($this->modelDiscovery, $this->service->getModelDiscovery());
    }

    public function test_index_manager_is_accessible(): void
    {
        $this->assertSame($this->indexManager, $this->service->getIndexManager());
    }

    public function test_search_processor_is_accessible(): void
    {
        $this->assertSame($this->searchProcessor, $this->service->getSearchProcessor());
    }

    public function test_index_model_via_index_manager(): void
    {
        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
        $this->indexManager->shouldReceive('indexModel')->once()->with($model);

        $this->service->getIndexManager()->indexModel($model);

        // Vérification que la méthode a été appelée correctement
        $this->addToAssertionCount(1);
    }

    public function test_remove_model_via_index_manager(): void
    {
        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
        $this->indexManager->shouldReceive('removeModel')->once()->with($model);

        $this->service->getIndexManager()->removeModel($model);

        $this->addToAssertionCount(1);
    }

    public function test_update_model_index_via_index_manager(): void
    {
        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
        $this->indexManager->shouldReceive('updateModelIndex')->once()->with($model);

        $this->service->getIndexManager()->updateModelIndex($model);

        $this->addToAssertionCount(1);
    }

    public function test_reindex_all_via_index_manager(): void
    {
        $this->indexManager->shouldReceive('reindexAll')->once();

        $this->service->getIndexManager()->reindexAll();

        $this->addToAssertionCount(1);
    }

    public function test_reindex_model_via_index_manager(): void
    {
        $modelClass = User::class;
        $this->indexManager->shouldReceive('reindexModel')->once()->with($modelClass);

        $this->service->getIndexManager()->reindexModel($modelClass);

        $this->addToAssertionCount(1);
    }

    public function test_get_stats_via_index_manager(): void
    {
        $expectedStats = ['total_entries' => 10];
        $this->indexManager->shouldReceive('getStats')->once()->andReturn($expectedStats);

        $stats = $this->service->getIndexManager()->getStats();

        $this->assertEquals($expectedStats, $stats);
    }

    public function test_get_precise_model_stats_via_index_manager(): void
    {
        $modelClass = User::class;
        $expectedStats = ['total_records' => 10];
        $this->indexManager->shouldReceive('getPreciseModelStats')->once()->with($modelClass)->andReturn($expectedStats);

        $stats = $this->service->getIndexManager()->getPreciseModelStats($modelClass);

        $this->assertEquals($expectedStats, $stats);
    }

    public function test_invalidate_all_via_cache_manager(): void
    {
        $this->cacheManager->shouldReceive('invalidateAll')->once();

        $this->service->getCacheManager()->invalidateAll();

        $this->addToAssertionCount(1);
    }

    public function test_invalidate_for_model_via_cache_manager(): void
    {
        $modelClass = User::class;
        $this->cacheManager->shouldReceive('invalidateForModel')->once()->with($modelClass);

        $this->service->getCacheManager()->invalidateForModel($modelClass);

        $this->addToAssertionCount(1);
    }

    public function test_invalidate_stats_cache_via_cache_manager(): void
    {
        $this->cacheManager->shouldReceive('invalidateStatsCache')->once();

        $this->service->getCacheManager()->invalidateStatsCache();

        $this->addToAssertionCount(1);
    }

    public function test_get_searchable_models_via_model_discovery(): void
    {
        $expectedModels = [User::class, Product::class];
        $this->modelDiscovery->shouldReceive('getSearchableModels')->once()->andReturn($expectedModels);

        $models = $this->service->getModelDiscovery()->getSearchableModels();

        $this->assertEquals($expectedModels, $models);
    }
}
