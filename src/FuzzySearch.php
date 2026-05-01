<?php

declare(strict_types=1);

namespace Fuzzy;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Collection;
use Fuzzy\Contracts\MustFuzzySearch;

/**
 * Facade for the fuzzy search service
 *
 * Provides static access to fuzzy search functionality throughout the application.
 * This facade acts as the primary interface for search operations.
 *
 * For cache management, index management, model discovery, and search processor
 * operations, resolve the underlying service directly:
 * @see \Fuzzy\Services\FuzzySearchService
 *
 * @method static Collection search(string $query, array $options = []) Search across all searchable models
 * @method static Collection searchInModel(string $modelClass, string $query, array $options = []) Search within a specific model
 * @method static Collection searchInModels(array $modelClasses, string $query, array $options = []) Search across multiple specific models
 *
 * @see \Fuzzy\Services\FuzzySearchService
 */
class FuzzySearch extends Facade
{
    /**
     * Get the registered name of the component
     *
     * @return string The service container binding key
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-fuzzy.search';
    }
}
