<?php

declare(strict_types=1);

namespace LaravelFuzzy;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection search(string $query, array $options = [])
 * @method static \Illuminate\Support\Collection searchInModel(string $modelClass, string $query, array $options = [])
 * @method static \Illuminate\Support\Collection searchInModels(array $modelClasses, string $query, array $options = [])
 * @method static void indexModel(\LaravelFuzzy\Contracts\MustFuzzySearch $model)
 * @method static void updateModelIndex(\LaravelFuzzy\Contracts\MustFuzzySearch $model)
 * @method static void removeModelFromIndex(\LaravelFuzzy\Contracts\MustFuzzySearch $model)
 * @method static void reindexAll()
 * @method static void reindexModel(string $modelClass)
 * @method static array getStats()
 * @method static float calculateSimilarity(string $str1, string $str2)
 * @method static string normalize(string $str)
 * @method static array splitIntoWords(string $str)
 *
 * @see \LaravelFuzzy\Services\FuzzySearchService
 */
class FuzzySearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-fuzzy.search';
    }
}
