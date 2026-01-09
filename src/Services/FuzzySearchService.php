<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Illuminate\Support\Collection;
use Illuminate\Pipeline\Pipeline;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\SearchContext;
use Fuzzy\Models\FuzzyIndex;

class FuzzySearchService
{
    protected Pipeline $pipeline;
    protected StringNormalizer $normalizer;
    protected SimilarityCalculator $similarityCalculator;
    protected IndexBuilder $indexBuilder;

    public function __construct(
        Pipeline $pipeline,
        StringNormalizer $normalizer,
        SimilarityCalculator $similarityCalculator,
        IndexBuilder $indexBuilder
    ) {
        $this->pipeline = $pipeline;
        $this->normalizer = $normalizer;
        $this->similarityCalculator = $similarityCalculator;
        $this->indexBuilder = $indexBuilder;
    }

    /**
     * Search across all searchable models
     */
    public function search(string $query, array $options = []): Collection
    {
        $searchOptions = SearchOptionsData::fromConfig($options);
        $models = $this->getSearchableModels();

        $results = collect();

        foreach ($models as $modelClass) {
            $modelResults = $this->searchInModel($modelClass, $query, $options);
            $results = $results->merge($modelResults);
        }

        return $results->sortByDesc('score')->values();
    }

    /**
     * Search in specific model
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection
    {
        $this->validateModel($modelClass);

        $searchOptions = SearchOptionsData::fromConfig($options);
        $indexData = $this->getIndexDataForModel($modelClass);

        $context = new SearchContext(
            modelClass: $modelClass,
            query: $query,
            options: $searchOptions,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->indexBuilder,
            indexData: $indexData
        );

        return $this->pipeline
            ->send($context)
            ->through($this->getPipelineStages())
            ->then(fn(SearchContext $context) => $context->results);
    }

    /**
     * Search in multiple specific models
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
     * Index a specific model instance
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        $this->indexBuilder->indexModel($model);
    }

    /**
     * Update index for a model instance
     */
    public function updateModelIndex(MustFuzzySearch $model): void
    {
        $this->removeModelFromIndex($model);
        $this->indexModel($model);
    }

    /**
     * Remove a model instance from index
     */
    public function removeModelFromIndex(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();

        FuzzyIndex::forModelInstance($modelType, $modelId)->delete();
    }

    /**
     * Reindex all searchable models
     */
    public function reindexAll(): void
    {
        $models = $this->getSearchableModels();

        foreach ($models as $modelClass) {
            $this->reindexModel($modelClass);
        }
    }

    /**
     * Reindex specific model
     */
    public function reindexModel(string $modelClass): void
    {
        $this->validateModel($modelClass);

        // Clear existing index
        FuzzyIndex::forModel($modelClass)->delete();

        // Index all instances
        $modelClass::chunk(100, function ($models) {
            foreach ($models as $model) {
                $this->indexModel($model);
            }
        });
    }

    /**
     * Calculate similarity between two strings
     */
    public function calculateSimilarity(string $str1, string $str2): float
    {
        return $this->similarityCalculator->calculateSimilarity($str1, $str2);
    }

    /**
     * Normalize a string
     */
    public function normalize(string $str): string
    {
        return $this->normalizer->normalize($str);
    }

    /**
     * Split string into words
     */
    public function splitIntoWords(string $str): array
    {
        return $this->normalizer->splitIntoWords($str);
    }

    /**
     * Normalize query for search
     */
    public function normalizeQuery(string $query): string
    {
        return $this->normalizer->normalizeQuery($query);
    }

    /**
     * Get index statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_entries' => FuzzyIndex::count(),
            'models' => [],
        ];

        $models = $this->getSearchableModels();

        foreach ($models as $modelClass) {
            $count = FuzzyIndex::forModel($modelClass)->count();
            $stats['models'][$modelClass] = [
                'count' => $count,
                'fields' => FuzzyIndex::forModel($modelClass)
                    ->selectRaw('field, COUNT(*) as count')
                    ->groupBy('field')
                    ->get()
                    ->pluck('count', 'field')
                    ->toArray(),
            ];
        }

        return $stats;
    }

    /**
     * Get all searchable models from config
     */
    protected function getSearchableModels(): array
    {
        $models = config('fuzzy.searchable_models', []);

        return array_filter($models, function ($modelClass) {
            return $this->isModelSearchable($modelClass);
        });
    }

    /**
     * Check if model is searchable
     */
    protected function isModelSearchable(string $modelClass): bool
    {
        return class_exists($modelClass) &&
            in_array(MustFuzzySearch::class, class_implements($modelClass));
    }

    /**
     * Validate model implements MustFuzzySearch
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
     * Get index data for model from database
     */
    protected function getIndexDataForModel(string $modelClass): array
    {
        $indexEntries = FuzzyIndex::forModel($modelClass)->get();

        $wordIndex = [];
        $itemMap = [];

        foreach ($indexEntries as $entry) {
            // Build word index
            foreach ($entry->words as $word) {
                if (strlen($word) >= 2) {
                    if (!isset($wordIndex[$word])) {
                        $wordIndex[$word] = [];
                    }

                    $wordIndex[$word][] = [
                        'indexable_type' => $entry->indexable_type,
                        'indexable_id' => $entry->indexable_id,
                        'field' => $entry->field,
                        'original_value' => $entry->original_value,
                        'normalized_words' => $entry->words,
                        'weight' => $entry->weight,
                    ];
                }
            }

            // Build item map
            $key = $entry->indexable_type . '_' . $entry->indexable_id;
            if (!isset($itemMap[$key])) {
                $itemMap[$key] = [
                    'indexable_type' => $entry->indexable_type,
                    'indexable_id' => $entry->indexable_id,
                ];
            }
        }

        return [
            'wordIndex' => $wordIndex,
            'itemMap' => $itemMap,
        ];
    }

    /**
     * Get pipeline stages
     */
    protected function getPipelineStages(): array
    {
        return [
            \Fuzzy\Stages\NormalizeQueryStage::class,
            \Fuzzy\Stages\ExactMatchStage::class,
            \Fuzzy\Stages\WordMatchStage::class,
            \Fuzzy\Stages\FuzzyMatchStage::class,
            \Fuzzy\Stages\SimilarityBonusStage::class,
            \Fuzzy\Stages\MultiWordProcessingStage::class,
            \Fuzzy\Stages\SortAndLimitStage::class,
        ];
    }
}
