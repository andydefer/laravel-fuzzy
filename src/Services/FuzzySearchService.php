<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\SearchContext;
use Fuzzy\Stages;
use Fuzzy\Services\Scoring\ScoringEngine;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * Main service for fuzzy search operations.
 */
class FuzzySearchService
{
    /**
     * @param Pipeline $pipeline Laravel pipeline for processing search stages
     * @param StringNormalizer $normalizer Service for normalizing strings and queries
     * @param SimilarityCalculator $similarityCalculator Service for calculating similarity scores
     * @param IndexBuilder $indexBuilder Service for building and managing search indexes
     * @param IndexRepositoryInterface $indexRepository Repository for optimized index operations
     * @param ScoringEngine $scoringEngine Unified scoring engine
     */
    public function __construct(
        protected Pipeline $pipeline,
        protected StringNormalizer $normalizer,
        protected SimilarityCalculator $similarityCalculator,
        protected IndexBuilder $indexBuilder,
        protected IndexRepositoryInterface $indexRepository,
        protected ScoringEngine $scoringEngine
    ) {}

    /**
     * Search across all searchable models.
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
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        $this->validateModel($modelClass);
        $searchOptions = SearchOptionsData::fromConfig($options);
        $searchQuery = SearchQuery::create($query, $this->normalizer);

        if ($searchQuery->isEmpty()) {
            return collect();
        }

        $indexData = $this->indexRepository->getIndexDataForModel($modelClass);
        $context = new SearchContext(
            query: $searchQuery,
            options: $searchOptions,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->indexBuilder,
            indexRepository: $this->indexRepository,
            scoringEngine: $this->scoringEngine, // Ajouté
            indexDataArray: $indexData
        );

        $results = $this->pipeline
            ->send($context)
            ->through($this->getPipelineStages())
            ->then(fn(SearchContext $context) => $context->results);

        return $this->filterAndSortResults(collect($results), $searchOptions->minScore);
    }

    /**
     * Search across multiple specific models.
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
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);
        }
    }

    /**
     * Update the search index for a model instance.
     */
    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModelFromIndex($model);
        $this->indexModel($model);
    }

    /**
     * Remove a model instance from the search index.
     */
    public function removeModelFromIndex(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();

        FuzzyIndex::forModelInstance($modelType, $modelId)->delete();
    }

    /**
     * Reindex all searchable models.
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
     */
    public function calculateSimilarity(string $firstString, string $secondString): float
    {
        return $this->similarityCalculator->calculateSimilarity($firstString, $secondString);
    }

    /**
     * Normalize a string for search operations.
     */
    public function normalize(string $string): string
    {
        return $this->normalizer->normalize($string);
    }

    /**
     * Split a string into individual words.
     */
    public function splitIntoWords(string $string): array
    {
        return $this->normalizer->splitIntoWords($string);
    }

    /**
     * Normalize a search query.
     */
    public function normalizeQuery(string $query): string
    {
        return $this->normalizer->normalizeQuery($query);
    }

    /**
     * Get search index statistics.
     */
    public function getStats(): array
    {
        return $this->indexRepository->getStats();
    }

    /**
     * Get all searchable models.
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
     */
    private function discoverSearchableModels(): array
    {
        $models = [];
        $finder = new \Symfony\Component\Finder\Finder();

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
     */
    protected function getPipelineStages(): array
    {
        return [
            Stages\NormalizeQueryStage::class,
            Stages\MatchDiscoveryStage::class,    // NOUVEAU : fusion des 4 anciens stages
            Stages\ScoringStage::class,           // NOUVEAU : scoring unifié
            Stages\SortAndLimitStage::class,
        ];
    }

    /**
     * Filter and sort search results.
     */
    private function filterAndSortResults(Collection $results, float $minScore): Collection
    {
        return $results
            ->filter(fn($result) => $result !== null && $result->score >= $minScore)
            ->sortByDesc('score')
            ->values();
    }
}
