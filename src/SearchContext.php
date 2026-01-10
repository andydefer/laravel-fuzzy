<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\IndexBuilder;
use Illuminate\Support\Collection;

/**
 * Encapsulates the context and state for a single fuzzy search operation.
 *
 * This class holds all the necessary data and services required to perform
 * a fuzzy search, including normalized query, computed indexes, intermediate
 * results, and final search outcomes.
 */
class SearchContext
{
    public string $modelClass;
    public string $query;
    public SearchOptionsData $options;
    public StringNormalizer $normalizer;
    public SimilarityCalculator $similarityCalculator;
    public IndexBuilder $indexBuilder;
    public array $indexData;
    public string $normalizedQuery = '';
    public array $queryWords = [];
    public array $wordIndex = [];
    public array $itemMap = [];
    public array $results = [];
    public array $seen = [];
    public bool $hasMultipleWords = false;
    public Collection $finalResults;

    /**
     * Initialize a new search context.
     *
     * @param string $modelClass The model class being searched
     * @param string $query The original search query
     * @param SearchOptionsData $options Configuration options for the search
     * @param StringNormalizer $normalizer Service to normalize strings
     * @param SimilarityCalculator $similarityCalculator Service to calculate similarity scores
     * @param IndexBuilder $indexBuilder Service to build search indexes
     * @param array $indexData Precomputed search index data
     */
    public function __construct(
        string $modelClass,
        string $query,
        SearchOptionsData $options,
        StringNormalizer $normalizer,
        SimilarityCalculator $similarityCalculator,
        IndexBuilder $indexBuilder,
        array $indexData
    ) {
        $this->modelClass = $modelClass;
        $this->query = $query;
        $this->options = $options;
        $this->normalizer = $normalizer;
        $this->similarityCalculator = $similarityCalculator;
        $this->indexBuilder = $indexBuilder;
        $this->indexData = $indexData;
        $this->wordIndex = $indexData['wordIndex'] ?? [];
        $this->itemMap = $indexData['itemMap'] ?? [];
        $this->finalResults = collect();
    }

    /**
     * Retrieve a model instance from the item map by its key.
     *
     * @param string $key The unique identifier for the indexed item
     * @return object|null The retrieved model instance or null if not found
     */
    public function getModelInstance(string $key): ?object
    {
        if (!isset($this->itemMap[$key])) {
            return null;
        }

        $item = $this->itemMap[$key];
        $modelClass = $item['indexable_type'] ?? $this->modelClass;
        $modelId = $item['indexable_id'];

        return $modelClass::find($modelId);
    }
}
