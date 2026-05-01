<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for the search processor that orchestrates search operations.
 *
 * Defines the contract for executing search queries across one or multiple
 * models. The processor handles the complete search pipeline including
 * query normalization, match discovery, scoring, and result aggregation.
 *
 * @package Fuzzy\Contracts
 */
interface SearchProcessorInterface
{
    /**
     * Search across all searchable models in the system.
     *
     * Discovers all models that implement MustFuzzySearch and executes
     * the search query against each of them, then aggregates the results.
     *
     * @param string $query The raw search query string
     * @param array<int, class-string<MustFuzzySearch>> $modelClasses List of model classes to search within
     * @param array<string, mixed> $options Search options (fuzzy, threshold, minScore, maxResults, etc.)
     * @return Collection<int, array<string, mixed>> Collection of search results with scores
     */
    public function search(string $query, array $modelClasses, array $options = []): Collection;

    /**
     * Search within a single specific model class.
     *
     * Executes the search query only against the specified model.
     * This is more efficient when you know exactly which model to search.
     *
     * @param string $modelClass Fully qualified model class name (must implement MustFuzzySearch)
     * @param string $query The raw search query string
     * @param array<string, mixed> $options Search options (fuzzy, threshold, minScore, maxResults, etc.)
     * @return Collection<int, array<string, mixed>> Collection of search results with scores
     */
    public function searchInModel(string $modelClass, string $query, array $options = []): Collection;

    /**
     * Search across multiple specific model classes.
     *
     * Executes the search query only against the specified list of models.
     * Useful when you want to limit search to a subset of available models.
     *
     * @param array<int, class-string<MustFuzzySearch>> $modelClasses List of model classes to search within
     * @param string $query The raw search query string
     * @param array<string, mixed> $options Search options (fuzzy, threshold, minScore, maxResults, etc.)
     * @return Collection<int, array<string, mixed>> Collection of search results with scores
     */
    public function searchInModels(array $modelClasses, string $query, array $options = []): Collection;
}
