<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\ResultFilterInterface;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\SearchProcessorService;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\TestCase;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Mockery;

final class SearchProcessorServiceTest extends TestCase
{
    private SearchProcessorService $searchProcessor;
    private $pipeline;
    private $normalizer;
    private $similarityCalculator;
    private $indexRepository;
    private $scoringEngine;
    private $modelDiscovery;
    private $resultFilter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = Mockery::mock(Pipeline::class);
        $this->normalizer = Mockery::mock(StringNormalizer::class);
        $this->similarityCalculator = Mockery::mock(SimilarityCalculator::class);
        $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
        $this->scoringEngine = Mockery::mock(ScoringEngine::class);
        $this->modelDiscovery = Mockery::mock(ModelDiscoveryInterface::class);
        $this->resultFilter = Mockery::mock(ResultFilterInterface::class);

        $this->searchProcessor = new SearchProcessorService(
            $this->pipeline,
            $this->normalizer,
            $this->similarityCalculator,
            $this->indexRepository,
            $this->scoringEngine,
            $this->modelDiscovery,
            $this->resultFilter
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_in_model_returns_collection_for_valid_model(): void
    {
        $modelClass = 'TestModel';
        $query = 'test';
        $options = [];

        $this->modelDiscovery->shouldReceive('validateModel')->with($modelClass)->once();

        // Mock pour SearchQuery
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        // Mock index repository
        $this->indexRepository->shouldReceive('getIndexDataForModel')
            ->with($modelClass)
            ->once()
            ->andReturn([]);

        // Mock pipeline
        $this->pipeline->shouldReceive('send')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('through')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('then')->once()->andReturn([]);

        // Mock result filter - une fois pour searchInModel
        $this->resultFilter->shouldReceive('filterAndSort')
            ->once()
            ->with(Mockery::type(Collection::class), Mockery::any())
            ->andReturn(collect());

        $result = $this->searchProcessor->searchInModel($modelClass, $query, $options);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_search_in_model_returns_empty_collection_for_empty_query(): void
    {
        $modelClass = 'TestModel';
        $query = '';
        $options = [];

        $this->modelDiscovery->shouldReceive('validateModel')->with($modelClass)->once();

        // Mock pour query vide
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('');
        $this->normalizer->shouldReceive('splitIntoWords')->with('')->andReturn([]);
        $this->normalizer->shouldReceive('normalize')->andReturn('');

        // Aucun appel à indexRepository, pipeline ou resultFilter pour query vide
        $this->indexRepository->shouldReceive('getIndexDataForModel')->never();
        $this->pipeline->shouldReceive('send')->never();
        $this->resultFilter->shouldReceive('filterAndSort')->never();

        $result = $this->searchProcessor->searchInModel($modelClass, $query, $options);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_search_aggregates_results_from_multiple_models(): void
    {
        $query = 'test';
        $modelClasses = ['ModelA', 'ModelB'];
        $options = [];

        // Pour chaque modèle, on appelle validateModel
        $this->modelDiscovery->shouldReceive('validateModel')
            ->with(Mockery::any())
            ->times(count($modelClasses));

        // Mock pour SearchQuery
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        // Mock pour chaque modèle - chaque searchInModel appelle filterAndSort
        foreach ($modelClasses as $modelClass) {
            $this->indexRepository->shouldReceive('getIndexDataForModel')
                ->with($modelClass)
                ->once()
                ->andReturn([]);

            $this->pipeline->shouldReceive('send')->once()->andReturnSelf();
            $this->pipeline->shouldReceive('through')->once()->andReturnSelf();
            $this->pipeline->shouldReceive('then')->once()->andReturn([]);
        }

        // Chaque searchInModel appelle filterAndSort une fois (2 fois)
        // Puis la méthode search appelle filterAndSort une fois (3 fois au total)
        $this->resultFilter->shouldReceive('filterAndSort')
            ->times(3)  // 2 pour les modèles + 1 pour le regroupement
            ->with(Mockery::type(Collection::class), Mockery::any())
            ->andReturn(collect());

        $result = $this->searchProcessor->search($query, $modelClasses, $options);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_search_in_models_filters_invalid_models(): void
    {
        $query = 'test';
        $modelClasses = ['ValidModel', 'InvalidModel'];
        $options = [];

        // Mock isValidModel
        $this->modelDiscovery->shouldReceive('isValidModel')
            ->with('ValidModel')
            ->once()
            ->andReturn(true);
        $this->modelDiscovery->shouldReceive('isValidModel')
            ->with('InvalidModel')
            ->once()
            ->andReturn(false);

        // Mock validateModel pour le modèle valide uniquement
        $this->modelDiscovery->shouldReceive('validateModel')
            ->with('ValidModel')
            ->once();

        // Mock pour SearchQuery
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        // Mock pour le modèle valide uniquement
        $this->indexRepository->shouldReceive('getIndexDataForModel')
            ->with('ValidModel')
            ->once()
            ->andReturn([]);

        $this->pipeline->shouldReceive('send')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('through')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('then')->once()->andReturn([]);

        // searchInModels appelle :
        // - filterAndSort dans searchInModel (1 fois pour le modèle valide)
        // - filterAndSort dans searchInModels (1 fois pour le regroupement)
        $this->resultFilter->shouldReceive('filterAndSort')
            ->times(2)
            ->with(Mockery::type(Collection::class), Mockery::any())
            ->andReturn(collect());

        $result = $this->searchProcessor->searchInModels($modelClasses, $query, $options);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_search_in_model_with_options_passes_correct_min_score(): void
    {
        $modelClass = 'TestModel';
        $query = 'test';
        $options = ['min_score' => 0.7];

        $this->modelDiscovery->shouldReceive('validateModel')->with($modelClass)->once();

        // Mock pour SearchQuery
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        $this->indexRepository->shouldReceive('getIndexDataForModel')
            ->with($modelClass)
            ->once()
            ->andReturn([]);

        $this->pipeline->shouldReceive('send')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('through')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('then')->once()->andReturn([]);

        // Vérifier que le min_score est passé correctement
        $this->resultFilter->shouldReceive('filterAndSort')
            ->once()
            ->with(Mockery::type(Collection::class), 0.7)
            ->andReturn(collect());

        $result = $this->searchProcessor->searchInModel($modelClass, $query, $options);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_search_with_empty_model_list_returns_empty_collection(): void
    {
        $query = 'test';
        $modelClasses = [];
        $options = [];

        // Aucun appel aux dépendances pour les modèles
        $this->modelDiscovery->shouldReceive('validateModel')->never();

        // Mock pour SearchQuery (toujours appelé dans search)
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        $this->indexRepository->shouldReceive('getIndexDataForModel')->never();
        $this->pipeline->shouldReceive('send')->never();

        $this->resultFilter->shouldReceive('filterAndSort')
            ->once()
            ->with(Mockery::type(Collection::class), Mockery::any())
            ->andReturn(collect());

        $result = $this->searchProcessor->search($query, $modelClasses, $options);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    public function test_search_in_model_handles_pipeline_results_correctly(): void
    {
        $modelClass = 'TestModel';
        $query = 'test';
        $options = [];
        $pipelineResults = [
            (object) ['id' => 1, 'score' => 0.9],
            (object) ['id' => 2, 'score' => 0.7]
        ];

        $this->modelDiscovery->shouldReceive('validateModel')->with($modelClass)->once();

        // Mock pour SearchQuery
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        $this->indexRepository->shouldReceive('getIndexDataForModel')
            ->with($modelClass)
            ->once()
            ->andReturn([]);

        $this->pipeline->shouldReceive('send')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('through')->once()->andReturnSelf();
        $this->pipeline->shouldReceive('then')->once()->andReturn($pipelineResults);

        $this->resultFilter->shouldReceive('filterAndSort')
            ->once()
            ->with(Mockery::on(function ($collection) use ($pipelineResults) {
                return $collection instanceof Collection && $collection->count() === 2;
            }), Mockery::any())
            ->andReturn(collect($pipelineResults));

        $result = $this->searchProcessor->searchInModel($modelClass, $query, $options);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_search_in_models_with_only_valid_models(): void
    {
        $query = 'test';
        $modelClasses = ['ValidModel1', 'ValidModel2'];
        $options = [];

        // Mock isValidModel pour les deux modèles valides
        foreach ($modelClasses as $modelClass) {
            $this->modelDiscovery->shouldReceive('isValidModel')
                ->with($modelClass)
                ->once()
                ->andReturn(true);

            $this->modelDiscovery->shouldReceive('validateModel')
                ->with($modelClass)
                ->once();
        }

        // Mock pour SearchQuery
        $this->normalizer->shouldReceive('normalizeQuery')->with($query)->andReturn('test');
        $this->normalizer->shouldReceive('splitIntoWords')->with('test')->andReturn(['test']);
        $this->normalizer->shouldReceive('normalize')->andReturn('test');

        // Mock pour chaque modèle
        foreach ($modelClasses as $modelClass) {
            $this->indexRepository->shouldReceive('getIndexDataForModel')
                ->with($modelClass)
                ->once()
                ->andReturn([]);

            $this->pipeline->shouldReceive('send')->once()->andReturnSelf();
            $this->pipeline->shouldReceive('through')->once()->andReturnSelf();
            $this->pipeline->shouldReceive('then')->once()->andReturn([]);
        }

        // 2 appels pour les searchInModel + 1 pour searchInModels = 3
        $this->resultFilter->shouldReceive('filterAndSort')
            ->times(3)
            ->with(Mockery::type(Collection::class), Mockery::any())
            ->andReturn(collect());

        $result = $this->searchProcessor->searchInModels($modelClasses, $query, $options);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
