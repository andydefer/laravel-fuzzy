<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\ValueObjects\IndexData;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Encapsulates the context and state for a single fuzzy search operation.
 *
 * This class manages the complete lifecycle of a search request including
 * query processing, model preloading, match discovery, and result scoring.
 *
 * @package Fuzzy
 */
class SearchContext
{
    /** @var SearchQuery The original search query with normalized data */
    public SearchQuery $query;

    /** @var IndexData The index data structure for this search */
    public IndexData $indexData;

    /** @var Collection The final scored and sorted search results */
    public Collection $finalResults;

    /** @var array<string, array> Raw matches before scoring, keyed by model identifier */
    public array $potentialMatches = [];

    /** @var array<string, mixed> Raw results before final processing */
    public array $results = [];

    /** @var array<string, bool> Track already seen items to prevent duplicates */
    public array $seen = [];

    /** @var array<string, object> Preloaded model instances for efficient access */
    public array $preloadedModels = [];

    /** @var SearchOptionsData The search configuration options */
    public SearchOptionsData $options;

    /** @var StringNormalizer Service for normalizing text data */
    public StringNormalizer $normalizer;

    /** @var SimilarityCalculator Service for calculating string similarities */
    public SimilarityCalculator $similarityCalculator;

    /** @var IndexBuilder Service for building search indexes */
    public IndexBuilder $indexBuilder;

    /** @var IndexRepositoryInterface Repository for accessing index data */
    public IndexRepositoryInterface $indexRepository;

    /** @var ScoringEngine Service for scoring and ranking search results */
    public ScoringEngine $scoringEngine;

    /**
     * Initialize a new search context.
     *
     * @param SearchQuery $query The search query
     * @param SearchOptionsData $options Search configuration options
     * @param StringNormalizer $normalizer Text normalization service
     * @param SimilarityCalculator $similarityCalculator String similarity service
     * @param IndexBuilder $indexBuilder Index construction service
     * @param IndexRepositoryInterface $indexRepository Index data repository
     * @param ScoringEngine $scoringEngine Result scoring engine
     * @param array<string, mixed> $indexDataArray Raw index data
     */
    public function __construct(
        SearchQuery $query,
        SearchOptionsData $options,
        StringNormalizer $normalizer,
        SimilarityCalculator $similarityCalculator,
        IndexBuilder $indexBuilder,
        IndexRepositoryInterface $indexRepository,
        ScoringEngine $scoringEngine,
        array $indexDataArray
    ) {
        $this->query = $query;
        $this->options = $options;
        $this->normalizer = $normalizer;
        $this->similarityCalculator = $similarityCalculator;
        $this->indexBuilder = $indexBuilder;
        $this->indexRepository = $indexRepository;
        $this->scoringEngine = $scoringEngine;

        $this->indexData = IndexData::fromArray($indexDataArray);
        $this->finalResults = collect();

        $this->preloadModels();
    }

    /**
     * Preload all required models for efficient access.
     *
     * @return void
     */
    private function preloadModels(): void
    {
        if ($this->indexData->getItemMap() === []) {
            $this->preloadedModels = [];
            return;
        }

        $this->indexRepository->preloadModels($this);
        $this->preloadedModels = $this->indexRepository->getPreloadedModelsMap();
    }

    /**
     * Retrieve a model instance from preloaded models.
     *
     * @param string $key Model identifier (type_id format)
     * @return object|null The model instance or null if not found
     */
    public function getModelInstance(string $key): ?object
    {
        return $this->preloadedModels[$key] ?? null;
    }

    /**
     * Get all model IDs from the item map.
     *
     * @return array<int> Unique model identifiers
     */
    public function getAllModelIds(): array
    {
        $ids = [];
        foreach ($this->indexData->getItemMap() as $item) {
            $ids[] = $item['indexable_id'];
        }

        return array_unique($ids);
    }

    /**
     * Check if the query contains multiple words.
     *
     * @return bool True if query has multiple words
     */
    public function hasMultipleWords(): bool
    {
        return $this->query->isMultiWord;
    }

    /**
     * Get individual query words.
     *
     * @return array<int, string> Array of query words
     */
    public function getQueryWords(): array
    {
        return $this->query->words;
    }

    /**
     * Get the normalized query string.
     *
     * @return string Normalized search query
     */
    public function getNormalizedQuery(): string
    {
        return $this->query->normalizedQuery;
    }

    /**
     * Get the word index from index data.
     *
     * @return array<string, mixed> Word index mapping
     */
    public function getWordIndex(): array
    {
        return $this->indexData->getWordIndex();
    }

    /**
     * Get the item map from index data.
     *
     * @return array<string, mixed> Item mapping data
     */
    public function getItemMap(): array
    {
        return $this->indexData->getItemMap();
    }

    /**
     * Get the model index from index data.
     *
     * @return array<string, mixed> Model index mapping
     */
    public function getModelIndex(): array
    {
        return $this->indexData->getModelIndex();
    }

    /**
     * Get index entries for a specific model.
     *
     * @param string $modelType Type of the model
     * @param string $modelId Model identifier
     * @return array<string, mixed> Index entries for the model
     */
    public function getIndexEntriesForModel(string $modelType, string $modelId): array
    {
        return $this->indexData->getEntriesForModel($modelType, $modelId);
    }

    /**
     * Get the model class from index data.
     *
     * @return string Fully qualified model class name
     */
    public function getModelClass(): string
    {
        return $this->indexData->getModelClass();
    }

    /**
     * Add a potential match before scoring.
     *
     * @param array<string, mixed> $match Raw match data
     * @return void
     */
    public function addPotentialMatch(array $match): void
    {
        $key = $this->generateModelKey(
            $match['indexable_type'],
            $match['indexable_id']
        );

        if (!isset($this->potentialMatches[$key])) {
            $this->potentialMatches[$key] = [];
        }

        if ($this->isDuplicateMatch($this->potentialMatches[$key], $match)) {
            return;
        }

        $this->potentialMatches[$key][] = $match;
    }

    /**
     * Generate a unique key for model identification.
     *
     * @param string $modelType Type of the model
     * @param string|int $modelId Model identifier
     * @return string Unique model key
     */
    private function generateModelKey(string $modelType, string|int $modelId): string
    {
        return $modelType . '_' . $modelId;
    }

    /**
     * Check if a match is a duplicate of existing matches.
     *
     * @param array<array<string, mixed>> $existingMatches Array of existing matches
     * @param array<string, mixed> $newMatch New match to check
     * @return bool True if duplicate exists
     */
    private function isDuplicateMatch(array $existingMatches, array $newMatch): bool
    {
        $newSignature = $this->createMatchSignature($newMatch);

        foreach ($existingMatches as $existingMatch) {
            if ($this->createMatchSignature($existingMatch) === $newSignature) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a unique signature for match comparison.
     *
     * @param array<string, mixed> $match Match data
     * @return string Unique match signature
     */
    private function createMatchSignature(array $match): string
    {
        return md5(serialize([
            'type' => $match['indexable_type'],
            'id' => $match['indexable_id'],
            'field' => $match['field'] ?? null,
            'original_value' => $match['original_value'] ?? null,
        ]));
    }

    /**
     * Get all potential matches for a specific model.
     *
     * @param string $key Model identifier (type_id format)
     * @return array<array<string, mixed>> Array of potential matches
     */
    public function getPotentialMatchesForModel(string $key): array
    {
        return $this->potentialMatches[$key] ?? [];
    }

    /**
     * Get all potential matches across all models.
     *
     * @return array<string, array<array<string, mixed>>> All potential matches
     */
    public function getAllPotentialMatches(): array
    {
        return $this->potentialMatches;
    }

    /**
     * Check if a model has any potential matches.
     *
     * @param string $key Model identifier (type_id format)
     * @return bool True if model has potential matches
     */
    public function hasPotentialMatches(string $key): bool
    {
        return !empty($this->potentialMatches[$key]);
    }
}
