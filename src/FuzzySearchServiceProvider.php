<?php

declare(strict_types=1);

namespace LaravelFuzzy;

use Illuminate\Support\ServiceProvider;
use LaravelFuzzy\Commands\IndexSearchCommand;
use LaravelFuzzy\Commands\ClearIndexCommand;
use LaravelFuzzy\Commands\StatsIndexCommand;

class FuzzySearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/fuzzy.php',
            'fuzzy'
        );

        $this->app->singleton('laravel-fuzzy.normalizer', function ($app) {
            return new \LaravelFuzzy\Services\StringNormalizer();
        });

        $this->app->singleton('laravel-fuzzy.similarity', function ($app) {
            return new \LaravelFuzzy\Services\SimilarityCalculator();
        });

        $this->app->singleton('laravel-fuzzy.index-builder', function ($app) {
            return new \LaravelFuzzy\Services\IndexBuilder(
                $app->make('laravel-fuzzy.normalizer')
            );
        });

        $this->app->singleton('laravel-fuzzy.search', function ($app) {
            return new \LaravelFuzzy\Services\FuzzySearchService(
                $app->make(\Illuminate\Pipeline\Pipeline::class),
                $app->make('laravel-fuzzy.normalizer'),
                $app->make('laravel-fuzzy.similarity'),
                $app->make('laravel-fuzzy.index-builder')
            );
        });

        $this->app->alias('laravel-fuzzy.search', \LaravelFuzzy\Services\FuzzySearchService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/fuzzy.php' => config_path('fuzzy.php'),
            ], 'fuzzy-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'fuzzy-migrations');

            $this->commands([
                IndexSearchCommand::class,
                ClearIndexCommand::class,
                StatsIndexCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

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
