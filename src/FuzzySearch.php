<?php

declare(strict_types=1);

namespace Fuzzy;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Collection;
use Fuzzy\Contracts\MustFuzzySearch;

/**
 * Facade for the fuzzy search service.
 *
 * Provides static access to all fuzzy search functionality including:
 * - Full-text search across searchable models
 * - Model-specific searching
 * - Index management operations
 * - String similarity calculations
 *
 * @method static Collection search(string $query, array $options = []) Search across all searchable models
 * @method static Collection searchInModel(string $modelClass, string $query, array $options = []) Search within a specific model
 * @method static Collection searchInModels(array $modelClasses, string $query, array $options = []) Search across multiple specific models
 * @method static void indexModel(MustFuzzySearch $model) Index a specific model instance for search
 * @method static void updateModelIndex(MustFuzzySearch $model) Update the search index for a model instance
 * @method static void removeModelFromIndex(MustFuzzySearch $model) Remove a model instance from the search index
 * @method static void reindexAll() Reindex all searchable models
 * @method static void reindexModel(string $modelClass) Reindex all instances of a specific model
 * @method static array getStats() Get search index statistics
 * @method static float calculateSimilarity(string $str1, string $str2) Calculate similarity score between two strings
 * @method static string normalize(string $str) Normalize a string for search operations
 * @method static array splitIntoWords(string $str) Split a string into individual words
 *
 * @see \Fuzzy\Services\FuzzySearchService
 */
class FuzzySearch extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-fuzzy.search';
    }
}
