<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Services\ServiceRegistrar;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Fuzzy Search package.
 * 
 * Registers all package services, contracts, and implementations with the Laravel
 * service container. Also handles database migrations publishing.
 * 
 * This provider follows the Laravel package development conventions and ensures
 * proper dependency injection throughout the package.
 */
final class FuzzySearchServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     * 
     * This method is called by Laravel during the service registration phase.
     * It delegates the actual service registration to a dedicated ServiceRegistrar
     * class to maintain the Single Responsibility Principle.
     */
    public function register(): void
    {
        $serviceRegistrar = new ServiceRegistrar(
            app: $this->app,
            provider: $this
        );

        $serviceRegistrar->registerAll();
    }

    /**
     * Bootstrap any package services.
     * 
     * This method is called after all service providers have been registered.
     * It loads the package's database migrations.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            paths: __DIR__ . '/../database/migrations'
        );
    }

    /**
     * Get the services provided by this provider.
     * 
     * This method allows Laravel to optimize service loading by knowing
     * which services are provided by this package.
     * 
     * @return array<int, class-string|string> List of service identifiers
     */
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
