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
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\UnifiedScoringOrchestrator;
use Fuzzy\Services\Scoring\ExactMatchStrategy;
use Fuzzy\Services\Scoring\WordMatchStrategy;
use Fuzzy\Services\Scoring\FuzzyMatchStrategy;
use Fuzzy\Services\Scoring\MultiWordStrategy;
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
            fn($app): StringNormalizer =>
            new StringNormalizer()
        );

        $this->app->singleton(
            'laravel-fuzzy.similarity',
            fn($app): SimilarityCalculator =>
            new SimilarityCalculator()
        );

        $this->app->singleton(
            'laravel-fuzzy.index-builder',
            fn($app): IndexBuilder =>
            new IndexBuilder($app->make('laravel-fuzzy.normalizer'))
        );

        $this->app->singleton(
            'laravel-fuzzy.advanced-scoring',
            fn($app): AdvancedScoringCalculator =>
            new AdvancedScoringCalculator()
        );
    }

    private function registerRepository(): void
    {
        $this->app->singleton(
            IndexRepositoryInterface::class,
            fn($app): IndexRepository =>
            new IndexRepository()
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
                indexRepository: $app->make(IndexRepositoryInterface::class)
            );
        });

        $this->app->alias('laravel-fuzzy.search', FuzzySearchService::class);
    }

    private function registerScoringSystem(): void
    {
        // AdvancedScoringCalculator (cœur des calculs)
        $this->app->singleton(AdvancedScoringCalculator::class, function ($app) {
            return new AdvancedScoringCalculator();
        });

        // UnifiedScoringOrchestrator (orchestration intelligente)
        $this->app->singleton(UnifiedScoringOrchestrator::class, function ($app) {
            return new UnifiedScoringOrchestrator(
                $app->make(AdvancedScoringCalculator::class)
            );
        });
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
        ]);
    }

    public function provides(): array
    {
        return [
            'laravel-fuzzy.search',
            'laravel-fuzzy.normalizer',
            'laravel-fuzzy.similarity',
            'laravel-fuzzy.index-builder',
            IndexRepositoryInterface::class,
            UnifiedScoringOrchestrator::class,
            AdvancedScoringCalculator::class,
        ];
    }
}
