<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\ValueObjects\IndexData;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Encapsulates the context and state for a single fuzzy search operation.
 */
class SearchContext
{
    public SearchQuery $query;
    public IndexData $indexData;
    public Collection $finalResults;
    public array $results = [];
    public array $seen = [];
    public array $preloadedModels = [];

    public function __construct(
        SearchQuery $query,
        public SearchOptionsData $options,
        public StringNormalizer $normalizer,
        public SimilarityCalculator $similarityCalculator,
        public IndexBuilder $indexBuilder,
        public IndexRepositoryInterface $indexRepository,
        array $indexDataArray
    ) {
        $this->query = $query;
        $this->indexData = IndexData::fromArray($indexDataArray);
        $this->finalResults = collect();

        $this->preloadModels();
    }

    /**
     * Précharger tous les modèles nécessaires.
     */
    private function preloadModels(): void
    {
        // Utiliser les getters du Value Object IndexData
        if (empty($this->indexData->getItemMap())) {
            $this->preloadedModels = [];
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
        return $this->preloadedModels[$key] ?? null;
    }

    /**
     * Get all model IDs from item map.
     */
    public function getAllModelIds(): array
    {
        $ids = [];
        foreach ($this->indexData->getItemMap() as $key => $item) {
            $ids[] = $item['indexable_id'];
        }
        return array_unique($ids);
    }

    /**
     * Check if query has multiple words.
     */
    public function hasMultipleWords(): bool
    {
        return $this->query->isMultiWord;
    }

    /**
     * Get query words.
     */
    public function getQueryWords(): array
    {
        return $this->query->words;
    }

    /**
     * Get normalized query.
     */
    public function getNormalizedQuery(): string
    {
        return $this->query->normalizedQuery;
    }

    /**
     * Get word index.
     */
    public function getWordIndex(): array
    {
        return $this->indexData->getWordIndex();
    }

    /**
     * Get item map.
     */
    public function getItemMap(): array
    {
        return $this->indexData->getItemMap();
    }

    /**
     * Get index entries for a model.
     */
    public function getIndexEntriesForModel(string $modelType, $modelId): array
    {
        return $this->indexData->getEntriesForModel($modelType, $modelId);
    }

    /**
     * Get model class from index data.
     */
    public function getModelClass(): string
    {
        return $this->indexData->getModelClass();
    }
}
