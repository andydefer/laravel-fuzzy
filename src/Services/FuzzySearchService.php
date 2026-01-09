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
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use ReflectionClass;

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

        $allResults = collect();

        foreach ($models as $modelClass) {
            $modelResults = $this->searchInModel($modelClass, $query, $options);
            $allResults = $allResults->merge($modelResults);
        }

        // Filtrer et trier tous les résultats
        return $allResults
            ->filter(fn($result) => $result->score >= $searchOptions->minScore)
            ->sortByDesc('score')
            ->values();
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

        $results = $this->pipeline
            ->send($context)
            ->through($this->getPipelineStages())
            ->then(fn(SearchContext $context) => $context->results);

        // Convertir le tableau en Collection et filtrer par minScore
        return collect($results)
            ->filter(fn($result) => $result !== null && $result->score >= $searchOptions->minScore)
            ->sortByDesc('score')
            ->values();
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
        if ($model->shouldBeIndexed()) {
            $this->indexBuilder->indexModel($model);
        }
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

        // Index all instances that should be indexed
        $modelClass::chunk(100, function ($models) {
            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $this->indexModel($model);
                }
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
     * Get all searchable models with hybrid approach
     * Priority: 1. Manual config, 2. Auto-discovery
     */
    protected function getSearchableModels(): array
    {
        // 1. Priorité aux modèles configurés manuellement
        $configuredModels = config('fuzzy.searchable_models', []);
        if (!empty($configuredModels)) {
            return array_filter($configuredModels, function ($modelClass) {
                return $this->isModelSearchable($modelClass);
            });
        }

        // 2. Auto-détection si pas de configuration manuelle
        return $this->discoverSearchableModels();
    }

    /**
     * Discover models implementing MustFuzzySearch interface
     */
    private function discoverSearchableModels(): array
    {
        $models = [];

        // Scanner le dossier Models et sous-dossiers
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
     * Get fully qualified class name from file path
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // Extraire le namespace
        $namespace = '';
        if (preg_match('/namespace\s+(.+?);/s', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extraire le nom de classe
        $className = '';
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
     * Check if model is searchable
     */
    protected function isModelSearchable(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        // Vérifier si la classe implémente l'interface
        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
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
            \Fuzzy\Stages\SortAndLimitStage::class,
        ];
    }
}
