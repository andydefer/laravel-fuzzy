<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\IndexBuilder;
use Illuminate\Support\Collection;

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
     * Get model instance from item map
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
