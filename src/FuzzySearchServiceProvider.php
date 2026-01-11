<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Commands\IndexSearchCommand;
use Fuzzy\Commands\ClearIndexCommand;
use Fuzzy\Commands\StatsIndexCommand;
use Fuzzy\Commands\ClearCacheCommand; // AJOUTÉ
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\Scoring\ScoringStrategies;
use Fuzzy\Repositories\IndexRepository;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

class FuzzySearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fuzzy.php', 'fuzzy');
        $this->registerCoreServices();
        $this->registerRepository();
        $this->registerSearchService();
        $this->registerScoringSystem();
    }

    private function registerCoreServices(): void
    {
        $this->app->singleton(
            'laravel-fuzzy.normalizer',
            fn($app): StringNormalizer => new StringNormalizer()
        );

        $this->app->singleton(
            'laravel-fuzzy.similarity',
            fn($app): SimilarityCalculator => new SimilarityCalculator()
        );

        $this->app->singleton(
            'laravel-fuzzy.index-builder',
            fn($app): IndexBuilder => new IndexBuilder($app->make('laravel-fuzzy.normalizer'))
        );

        $this->app->singleton(
            AdvancedScoringCalculator::class,
            fn($app): AdvancedScoringCalculator => new AdvancedScoringCalculator()
        );

        $this->app->alias(AdvancedScoringCalculator::class, 'laravel-fuzzy.advanced-scoring');
    }

    private function registerRepository(): void
    {
        $this->app->singleton(
            IndexRepositoryInterface::class,
            fn($app): IndexRepository => new IndexRepository()
        );

        $this->app->alias(IndexRepositoryInterface::class, 'laravel-fuzzy.repository');
    }

    private function registerSearchService(): void
    {
        $this->app->singleton('laravel-fuzzy.search', function ($app): FuzzySearchService {
            return new FuzzySearchService(
                pipeline: $app->make(Pipeline::class),
                normalizer: $app->make('laravel-fuzzy.normalizer'),
                similarityCalculator: $app->make('laravel-fuzzy.similarity'),
                indexBuilder: $app->make('laravel-fuzzy.index-builder'),
                indexRepository: $app->make(IndexRepositoryInterface::class),
                scoringEngine: $app->make(ScoringEngine::class)
            );
        });

        $this->app->alias('laravel-fuzzy.search', FuzzySearchService::class);
    }

    private function registerScoringSystem(): void
    {
        // Scoring Engine unifié
        $this->app->singleton(ScoringEngine::class, function ($app) {
            $advancedCalculator = $app->make(AdvancedScoringCalculator::class);

            return new ScoringEngine(
                new ScoringStrategies\ExactMatchStrategy($advancedCalculator),
                new ScoringStrategies\WordMatchStrategy($advancedCalculator),
                new ScoringStrategies\FuzzyMatchStrategy($advancedCalculator),
                new ScoringStrategies\MultiWordStrategy($advancedCalculator)
            );
        });

        $this->app->alias(ScoringEngine::class, 'laravel-fuzzy.scoring');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->registerCommands();
        }
    }

    private function publishResources(): void
    {
        $this->publishes([
            __DIR__ . '/../config/fuzzy.php' => config_path('fuzzy.php'),
        ], 'fuzzy-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'fuzzy-migrations');
    }

    private function registerCommands(): void
    {
        $this->commands([
            IndexSearchCommand::class,
            ClearIndexCommand::class,
            StatsIndexCommand::class,
            ClearCacheCommand::class, // AJOUTÉ
        ]);
    }

    public function provides(): array
    {
        return [
            'laravel-fuzzy.search',
            'laravel-fuzzy.normalizer',
            'laravel-fuzzy.similarity',
            'laravel-fuzzy.index-builder',
            'laravel-fuzzy.advanced-scoring',
            'laravel-fuzzy.scoring',
            IndexRepositoryInterface::class,
            ScoringEngine::class,
            AdvancedScoringCalculator::class,
        ];
    }
}
