<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Contracts\IndexRepositoryInterface;
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
    public IndexRepositoryInterface $indexRepository;
    public array $indexData;
    public string $normalizedQuery = '';
    public array $queryWords = [];
    public array $wordIndex = [];
    public array $itemMap = [];
    public array $results = [];
    public array $seen = [];
    public bool $hasMultipleWords = false;
    public Collection $finalResults;
    public array $preloadedModels = [];

    /**
     * Initialize a new search context.
     */
    public function __construct(
        string $modelClass,
        string $query,
        SearchOptionsData $options,
        StringNormalizer $normalizer,
        SimilarityCalculator $similarityCalculator,
        IndexBuilder $indexBuilder,
        IndexRepositoryInterface $indexRepository,
        array $indexData
    ) {
        $this->modelClass = $modelClass;
        $this->query = $query;
        $this->options = $options;
        $this->normalizer = $normalizer;
        $this->similarityCalculator = $similarityCalculator;
        $this->indexBuilder = $indexBuilder;
        $this->indexRepository = $indexRepository;
        $this->indexData = $indexData;
        $this->wordIndex = $indexData['wordIndex'] ?? [];
        $this->itemMap = $indexData['itemMap'] ?? [];
        $this->finalResults = collect();

        // Précharger les modèles pour éviter N+1
        $this->preloadModels();
    }

    /**
     * Précharger tous les modèles nécessaires.
     */
    private function preloadModels(): void
    {
        if (empty($this->itemMap)) {
            return;
        }

        $this->indexRepository->preloadModels($this);
        $this->preloadedModels = $this->indexRepository->getPreloadedModelsMap();
    }

    /**
     * Retrieve a model instance from the preloaded models.
     */
    public function getModelInstance(string $key): ?object
    {
        if (!isset($this->preloadedModels[$key])) {
            return null;
        }

        return $this->preloadedModels[$key];
    }

    /**
     * Get all model IDs from item map.
     */
    public function getAllModelIds(): array
    {
        $ids = [];
        foreach ($this->itemMap as $key => $item) {
            $ids[] = $item['indexable_id'];
        }
        return array_unique($ids);
    }
}
