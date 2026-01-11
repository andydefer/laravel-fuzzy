<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\SearchContext;
use Fuzzy\Stages;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Main service for fuzzy search operations.
 *
 * Provides search capabilities across searchable models with fuzzy matching,
 * exact matching, and word-based search strategies.
 */
class FuzzySearchService
{
    /**
     * @var Pipeline Laravel pipeline for processing search stages
     */
    protected Pipeline $pipeline;

    /**
     * @var StringNormalizer Service for normalizing strings and queries
     */
    protected StringNormalizer $normalizer;

    /**
     * @var SimilarityCalculator Service for calculating similarity scores
     */
    protected SimilarityCalculator $similarityCalculator;

    /**
     * @var IndexBuilder Service for building and managing search indexes
     */
    protected IndexBuilder $indexBuilder;

    /**
     * @var IndexRepositoryInterface Repository for optimized index operations
     */
    protected IndexRepositoryInterface $indexRepository;

    /**
     * Create a new fuzzy search service instance.
     *
     * @param Pipeline $pipeline
     * @param StringNormalizer $normalizer
     * @param SimilarityCalculator $similarityCalculator
     * @param IndexBuilder $indexBuilder
     * @param IndexRepositoryInterface $indexRepository
     */
    public function __construct(
        Pipeline $pipeline,
        StringNormalizer $normalizer,
        SimilarityCalculator $similarityCalculator,
        IndexBuilder $indexBuilder,
        IndexRepositoryInterface $indexRepository
    ) {
        $this->pipeline = $pipeline;
        $this->normalizer = $normalizer;
        $this->similarityCalculator = $similarityCalculator;
        $this->indexBuilder = $indexBuilder;
        $this->indexRepository = $indexRepository;
    }

    /**
     * Search across all searchable models.
     *
     * @param string $query Search query string
     * @param array $options Search options to override defaults
     * @return Collection<SearchResultData> Collection of search results sorted by score
     */
    public function search(string $query, array $options = []): Collection
    {
        $searchOptions = SearchOptionsData::fromConfig($options);
        $models = $this->getSearchableModels();

        $allResults = collect();

        foreach ($models as $modelClass) {
            $modelResults = $this->searchInModel($modelClass, $query, $options);
            $allResults = $allResults->merge($modelResults);
        }

        return $this->filterAndSortResults($allResults, $searchOptions->minScore);
    }

    /**
     * Search within a specific model.
     *
     * @param string $modelClass Fully qualified model class name
     * @param string $query Search query string
     * @param array $options Search options to override defaults
     * @return Collection<SearchResultData> Search results for the specified model
     * @throws ModelNotSearchableException If model doesn't implement MustFuzzySearch
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        $this->validateModel($modelClass);

        $searchOptions = SearchOptionsData::fromConfig($options);

        // Utiliser le repository optimisé pour éviter N+1
        $indexData = $this->indexRepository->getIndexDataForModel($modelClass);

        $context = new SearchContext(
            modelClass: $modelClass,
            query: $query,
            options: $searchOptions,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->indexBuilder,
            indexRepository: $this->indexRepository,
            indexData: $indexData
        );

        $results = $this->pipeline
            ->send($context)
            ->through($this->getPipelineStages())
            ->then(fn(SearchContext $context) => $context->results);

        return $this->filterAndSortResults(collect($results), $searchOptions->minScore);
    }

    /**
     * Search across multiple specific models.
     *
     * @param array $modelClasses Array of fully qualified model class names
     * @param string $query Search query string
     * @param array $options Search options to override defaults
     * @return Collection<SearchResultData> Combined results from all specified models
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection
    {
        $results = collect();

        foreach ($modelClasses as $modelClass) {
            if ($this->isModelSearchable($modelClass)) {
                $modelResults = $this->searchInModel($modelClass, $query, $options);
                $results = $results->merge($modelResults);
            }
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * Index a specific model instance for search.
     *
     * @param MustFuzzySearch $model Model instance to index
     * @return void
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);
        }
    }

    /**
     * Update the search index for a model instance.
     *
     * @param MustFuzzySearch $model Model instance to update
     * @return void
     */
    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModelFromIndex($model);
        $this->indexModel($model);
    }

    /**
     * Remove a model instance from the search index.
     *
     * @param MustFuzzySearch $model Model instance to remove
     * @return void
     */
    public function removeModelFromIndex(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();

        FuzzyIndex::forModelInstance($modelType, $modelId)->delete();
    }

    /**
     * Reindex all searchable models.
     *
     * @return void
     */
    public function reindexAll(): void
    {
        $models = $this->getSearchableModels();

        foreach ($models as $modelClass) {
            $this->reindexModel($modelClass);
        }
    }

    /**
     * Reindex all instances of a specific model.
     *
     * @param string $modelClass Fully qualified model class name
     * @return void
     * @throws ModelNotSearchableException If model doesn't implement MustFuzzySearch
     */
    public function reindexModel(string $modelClass): void
    {
        $this->validateModel($modelClass);

        FuzzyIndex::forModel($modelClass)->delete();

        $modelClass::chunk(100, function ($models) {
            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $this->indexModel($model);
                }
            }
        });
    }

    /**
     * Calculate similarity score between two strings.
     *
     * @param string $firstString First string to compare
     * @param string $secondString Second string to compare
     * @return float Similarity score between 0 and 1
     */
    public function calculateSimilarity(string $firstString, string $secondString): float
    {
        return $this->similarityCalculator->calculateSimilarity($firstString, $secondString);
    }

    /**
     * Normalize a string for search operations.
     *
     * @param string $string String to normalize
     * @return string Normalized string
     */
    public function normalize(string $string): string
    {
        return $this->normalizer->normalize($string);
    }

    /**
     * Split a string into individual words.
     *
     * @param string $string String to split
     * @return array<string> Array of words
     */
    public function splitIntoWords(string $string): array
    {
        return $this->normalizer->splitIntoWords($string);
    }

    /**
     * Normalize a search query.
     *
     * @param string $query Search query to normalize
     * @return string Normalized query
     */
    public function normalizeQuery(string $query): string
    {
        return $this->normalizer->normalizeQuery($query);
    }

    /**
     * Get search index statistics using the optimized repository.
     *
     * @return array Statistics including total entries and per-model counts
     */
    public function getStats(): array
    {
        return $this->indexRepository->getStats();
    }

    /**
     * Get all searchable models using hybrid approach.
     * Priority: 1. Manual configuration, 2. Auto-discovery
     *
     * @return array<string> Array of fully qualified model class names
     */
    protected function getSearchableModels(): array
    {
        $configuredModels = config('fuzzy.searchable_models', []);

        if (!empty($configuredModels)) {
            return array_filter($configuredModels, function ($modelClass) {
                return $this->isModelSearchable($modelClass);
            });
        }

        return $this->discoverSearchableModels();
    }

    /**
     * Discover models implementing MustFuzzySearch interface.
     *
     * @return array<string> Array of discovered model class names
     */
    private function discoverSearchableModels(): array
    {
        $models = [];
        $finder = new Finder();

        $finder->files()
            ->in(app_path('Models'))
            ->name('*.php');

        foreach ($finder as $file) {
            $modelClass = $this->getClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isModelSearchable($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Extract fully qualified class name from a file.
     *
     * @param string $filePath Path to the PHP file
     * @return string|null Fully qualified class name or null if not found
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        $namespace = '';
        $className = '';

        if (preg_match('/namespace\s+(.+?);/s', $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/', $content, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            $fullClassName = $namespace . '\\' . $className;
            return class_exists($fullClassName) ? $fullClassName : null;
        }

        return null;
    }

    /**
     * Check if a model implements the MustFuzzySearch interface.
     *
     * @param string $modelClass Model class name to check
     * @return bool True if model is searchable
     */
    protected function isModelSearchable(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
    }

    /**
     * Validate that a model implements MustFuzzySearch interface.
     *
     * @param string $modelClass Model class name to validate
     * @throws ModelNotSearchableException If model is not searchable
     */
    protected function validateModel(string $modelClass): void
    {
        if (!$this->isModelSearchable($modelClass)) {
            throw new ModelNotSearchableException(
                "Model {$modelClass} must implement " . MustFuzzySearch::class
            );
        }
    }

    /**
     * Get the pipeline stages for search processing.
     *
     * @return array<string> Array of pipeline stage class names
     */
    protected function getPipelineStages(): array
    {
        return [
            Stages\NormalizeQueryStage::class,
            Stages\ExactMatchStage::class,
            Stages\WordMatchStage::class,
            Stages\FuzzyMatchStage::class,
            Stages\MultiWordProcessingStage::class,
            Stages\ScoreAggregationStage::class,
            Stages\SortAndLimitStage::class,
        ];
    }

    /**
     * Filter and sort search results.
     *
     * @param Collection $results Collection of search results
     * @param float $minScore Minimum score threshold
     * @return Collection Filtered and sorted results
     */
    private function filterAndSortResults(Collection $results, float $minScore): Collection
    {
        return $results
            ->filter(fn($result) => $result !== null && $result->score >= $minScore)
            ->sortByDesc('score')
            ->values();
    }
}
