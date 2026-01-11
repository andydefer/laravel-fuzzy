<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Commands\IndexSearchCommand;
use Fuzzy\Commands\ClearIndexCommand;
use Fuzzy\Commands\StatsIndexCommand;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Repositories\IndexRepository;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Fuzzy Search package.
 */
class FuzzySearchServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/fuzzy.php',
            'fuzzy'
        );

        $this->registerCoreServices();
        $this->registerRepository();
        $this->registerSearchService();
    }

    /**
     * Register core services.
     */
    private function registerCoreServices(): void
    {
        $this->app->singleton('laravel-fuzzy.normalizer', function ($app): StringNormalizer {
            return new StringNormalizer();
        });

        $this->app->singleton('laravel-fuzzy.similarity', function ($app): SimilarityCalculator {
            return new SimilarityCalculator();
        });

        $this->app->singleton('laravel-fuzzy.index-builder', function ($app): IndexBuilder {
            return new IndexBuilder(
                normalizer: $app->make('laravel-fuzzy.normalizer')
            );
        });
    }

    /**
     * Register repository.
     */
    private function registerRepository(): void
    {
        $this->app->singleton(IndexRepositoryInterface::class, function ($app): IndexRepository {
            return new IndexRepository();
        });

        $this->app->alias(IndexRepositoryInterface::class, 'laravel-fuzzy.repository');
    }

    /**
     * Register search service.
     */
    private function registerSearchService(): void
    {
        $this->app->singleton('laravel-fuzzy.search', function ($app): FuzzySearchService {
            return new FuzzySearchService(
                pipeline: $app->make(Pipeline::class),
                normalizer: $app->make('laravel-fuzzy.normalizer'),
                similarityCalculator: $app->make('laravel-fuzzy.similarity'),
                indexBuilder: $app->make('laravel-fuzzy.index-builder'),
                indexRepository: $app->make(IndexRepositoryInterface::class)
            );
        });

        $this->app->alias('laravel-fuzzy.search', FuzzySearchService::class);
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->registerCommands();
        }
    }

    /**
     * Publish package resources.
     */
    private function publishResources(): void
    {
        $this->publishes([
            __DIR__ . '/../config/fuzzy.php' => config_path('fuzzy.php'),
        ], 'fuzzy-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'fuzzy-migrations');
    }

    /**
     * Register console commands.
     */
    private function registerCommands(): void
    {
        $this->commands([
            IndexSearchCommand::class,
            ClearIndexCommand::class,
            StatsIndexCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'laravel-fuzzy.search',
            'laravel-fuzzy.normalizer',
            'laravel-fuzzy.similarity',
            'laravel-fuzzy.index-builder',
            IndexRepositoryInterface::class,
        ];
    }
}
