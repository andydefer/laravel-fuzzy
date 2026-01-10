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
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Fuzzy Search package.
 *
 * Registers services and configurations required for fuzzy search functionality
 * in Laravel applications, including console commands and migrations.
 */
class FuzzySearchServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/fuzzy.php',
            'fuzzy'
        );

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

        $this->app->singleton('laravel-fuzzy.search', function ($app): FuzzySearchService {
            return new FuzzySearchService(
                pipeline: $app->make(Pipeline::class),
                normalizer: $app->make('laravel-fuzzy.normalizer'),
                similarityCalculator: $app->make('laravel-fuzzy.similarity'),
                indexBuilder: $app->make('laravel-fuzzy.index-builder')
            );
        });

        $this->app->alias('laravel-fuzzy.search', FuzzySearchService::class);
    }

    /**
     * Bootstrap package services.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'laravel-fuzzy.search',
            'laravel-fuzzy.normalizer',
            'laravel-fuzzy.similarity',
            'laravel-fuzzy.index-builder',
        ];
    }
}
