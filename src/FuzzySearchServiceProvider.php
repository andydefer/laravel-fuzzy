<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Services\ServiceRegistrar;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Fuzzy Search package.
 */
class FuzzySearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        (new ServiceRegistrar($this->app, $this))->registerAll();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function provides(): array
    {
        return [
            \Fuzzy\Contracts\CacheManagerInterface::class,
            \Fuzzy\Contracts\ModelDiscoveryInterface::class,
            \Fuzzy\Contracts\IndexManagerInterface::class,
            \Fuzzy\Contracts\SearchProcessorInterface::class,
            \Fuzzy\Contracts\ResultFilterInterface::class,
            \Fuzzy\Contracts\PipelineManagerInterface::class,
            \Fuzzy\Contracts\SearchContextInterface::class,
            \Fuzzy\Contracts\ScoringEngineInterface::class,
            \Fuzzy\Config\AdvancedScoringConfig::class,
            \Fuzzy\Config\SimilarityCalculatorConfig::class,
            \Fuzzy\Services\FuzzySearchService::class,
            'laravel-fuzzy.search',
        ];
    }
}
