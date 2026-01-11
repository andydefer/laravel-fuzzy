<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Fuzzy\SearchContext;
use Illuminate\Support\Collection;

/**
 * Interface for index repository operations.
 */
interface IndexRepositoryInterface
{
    /**
     * Get index data for a specific model with optional model IDs filtering.
     */
    public function getIndexDataForModel(string $modelClass, array $modelIds = []): array;

    /**
     * Get models in batch to avoid N+1 queries.
     */
    public function getModelsBatch(string $modelClass, array $ids): Collection;

    /**
     * Get preloaded models map.
     */
    public function getPreloadedModelsMap(): array;

    /**
     * Preload all models from item map.
     */
    public function preloadModels(SearchContext $context): void;

    public function getStats(): array;
}
