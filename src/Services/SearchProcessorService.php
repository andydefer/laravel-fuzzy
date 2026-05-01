<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\ResultFilterInterface;
use Fuzzy\Contracts\ScoringEngineInterface;
use Fuzzy\Contracts\SearchProcessorInterface;
use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\SearchContext;
use Fuzzy\ValueObjects\SearchQuery;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;

/**
 * Service responsible for processing search requests through the pipeline.
 *
 * Orchestrates the search flow including:
 * - Query normalization
 * - Index data retrieval
 * - Pipeline stage execution
 * - Result filtering and sorting
 */
class SearchProcessorService implements SearchProcessorInterface
{
    /**
     * Constructor.
     *
     * @param Pipeline $pipeline Laravel pipeline for stage execution
     * @param StringNormalizer $normalizer String normalization service
     * @param SimilarityCalculator $similarityCalculator Similarity calculation service
     * @param IndexRepositoryInterface $indexRepository Index data repository
     * @param ScoringEngineInterface $scoringEngine Scoring engine for relevance calculation
     * @param ModelDiscoveryInterface $modelDiscovery Model discovery service
     * @param ResultFilterInterface $resultFilter Result filtering service
     */
    public function __construct(
        private Pipeline $pipeline,
        private StringNormalizer $normalizer,
        private SimilarityCalculator $similarityCalculator,
        private IndexRepositoryInterface $indexRepository,
        private ScoringEngineInterface $scoringEngine,
        private ModelDiscoveryInterface $modelDiscovery,
        private ResultFilterInterface $resultFilter
    ) {}

    /**
     * {@inheritDoc}
     */
    public function search(string $query, array $modelClasses, array $options = []): Collection
    {
        $allResults = collect();

        foreach ($modelClasses as $modelClass) {
            $modelResults = $this->searchInModel($modelClass, $query, $options);
            $allResults = $allResults->merge($modelResults);
        }

        $searchOptions = SearchOptionsData::fromConfig($options);
        return $this->resultFilter->filterAndSort($allResults, $searchOptions->minScore);
    }

    /**
     * {@inheritDoc}
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        $this->modelDiscovery->validateModel($modelClass);
        $searchOptions = SearchOptionsData::fromConfig($options);
        $searchQuery = SearchQuery::create($query, $this->normalizer);

        if ($searchQuery->isEmpty()) {
            return collect();
        }

        $indexData = $this->indexRepository->getIndexDataForModel($modelClass);
        $context = $this->createSearchContext($searchQuery, $searchOptions, $indexData);

        $results = $this->processSearchPipeline($context);

        return $this->resultFilter->filterAndSort(collect($results), $searchOptions->minScore);
    }

    /**
     * {@inheritDoc}
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection
    {
        $results = collect();

        foreach ($modelClasses as $modelClass) {
            if ($this->modelDiscovery->isValidModel($modelClass)) {
                $modelResults = $this->searchInModel($modelClass, $query, $options);
                $results = $results->merge($modelResults);
            }
        }

        $searchOptions = SearchOptionsData::fromConfig($options);
        return $this->resultFilter->filterAndSort($results, $searchOptions->minScore);
    }

    /**
     * Create a search context for pipeline processing.
     *
     * @param SearchQuery $searchQuery Normalized search query
     * @param SearchOptionsData $searchOptions Search configuration options
     * @param array<string, mixed> $indexData Preloaded index data
     * @return SearchContextInterface Configured search context
     */
    private function createSearchContext(
        SearchQuery $searchQuery,
        SearchOptionsData $searchOptions,
        array $indexData
    ): SearchContextInterface {
        return new SearchContext(
            query: $searchQuery,
            options: $searchOptions,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: new IndexBuilder($this->normalizer),
            indexRepository: $this->indexRepository,
            scoringEngine: $this->scoringEngine,
            indexDataArray: $indexData
        );
    }

    /**
     * Execute the search pipeline with the given context.
     *
     * @param SearchContextInterface $context Search context
     * @return array<int, mixed> Raw search results
     */
    private function processSearchPipeline(SearchContextInterface $context): array
    {
        $stages = $this->getPipelineStages();

        return $this->pipeline
            ->send($context)
            ->through($stages)
            ->then(fn(SearchContextInterface $context): array => $context->results);
    }

    /**
     * Get the configured pipeline stages.
     *
     * @return array<int, string> Array of stage class names
     */
    private function getPipelineStages(): array
    {
        return config('fuzzy.pipeline.stages', [
            \Fuzzy\Stages\NormalizeQueryStage::class,
            \Fuzzy\Stages\MatchDiscoveryStage::class,
            \Fuzzy\Stages\ScoringStage::class,
            \Fuzzy\Stages\RelevanceScoringStage::class,
            \Fuzzy\Stages\SortAndLimitStage::class,
        ]);
    }
}
