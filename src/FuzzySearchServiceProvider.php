<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Services\Scoring\ScoringStrategies\ExactMatchStrategy;
use Fuzzy\Services\Scoring\ScoringStrategies\WordMatchStrategy;
use Fuzzy\Services\Scoring\ScoringStrategies\FuzzyMatchStrategy;
use Fuzzy\Services\Scoring\ScoringStrategies\MultiWordStrategy;
use Fuzzy\Commands\IndexSearchCommand;
use Fuzzy\Commands\ClearIndexCommand;
use Fuzzy\Commands\StatsIndexCommand;
use Fuzzy\Commands\ClearCacheCommand;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Repositories\IndexRepository;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

class FuzzySearchServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     *
     * Sets up all core services, repositories, and scoring systems
     * with their appropriate bindings and aliases
     */
    public function register(): void
    {
        $this->mergeDefaultConfig();
        $this->registerCoreServices();
        $this->registerRepository();
        $this->registerSearchService();
        $this->registerScoringSystem();
    }

    /**
     * Merge default configuration with user configuration
     *
     * Loads protected defaults and merges them intelligently
     * with user-defined configuration while preserving critical values
     */
    private function mergeDefaultConfig(): void
    {
        $defaultConfig = require __DIR__ . '/../config/fuzzy-defaults.php';
        $userConfig = $this->app['config']->get('fuzzy', []);
        $mergedConfig = $this->mergeConfigurations($defaultConfig, $userConfig);

        $this->app['config']->set('fuzzy', $mergedConfig);
        $this->mergeConfigFrom(__DIR__ . '/../config/fuzzy.php', 'fuzzy');
    }

    /**
     * Intelligently merge default and user configurations
     *
     * Applies specific merging rules for different configuration sections
     * to protect critical values while allowing customization
     *
     * @param array<string, mixed> $defaults Protected default configuration
     * @param array<string, mixed> $userConfig User-defined configuration
     * @return array<string, mixed> Merged configuration
     */
    private function mergeConfigurations(array $defaults, array $userConfig): array
    {
        $merged = [];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($userConfig[$key])) {
                $merged[$key] = $defaultValue;
                continue;
            }

            $userValue = $userConfig[$key];

            $merged[$key] = match ($key) {
                'pipeline' => $defaultValue, // Non-modifiable pipeline
                'stop_words' => $this->mergeStopWords($defaultValue, $userValue),
                'scoring' => $this->mergeScoringConfig($defaultValue, $userValue),
                default => $this->mergeGenericConfig($defaultValue, $userValue),
            };
        }

        return array_merge($merged, array_diff_key($userConfig, $defaults));
    }

    /**
     * Merge stop words arrays while preserving defaults
     *
     * @param array<int, string> $defaultStopWords Default stop words
     * @param mixed $userStopWords User-defined stop words
     * @return array<int, string> Merged stop words
     */
    private function mergeStopWords(array $defaultStopWords, mixed $userStopWords): array
    {
        if (!is_array($userStopWords)) {
            return $defaultStopWords;
        }

        return array_unique(array_merge($defaultStopWords, $userStopWords));
    }

    /**
     * Merge scoring configuration with protected sections
     *
     * @param array<string, mixed> $defaults Default scoring configuration
     * @param array<string, mixed> $userConfig User scoring configuration
     * @return array<string, mixed> Merged scoring configuration
     */
    private function mergeScoringConfig(array $defaults, array $userConfig): array
    {
        $merged = [];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($userConfig[$key])) {
                $merged[$key] = $defaultValue;
                continue;
            }

            $userValue = $userConfig[$key];

            $merged[$key] = match ($key) {
                'consecutive_bonus' => $defaultValue, // Protected critical values
                default => $this->mergeGenericConfig($defaultValue, $userValue),
            };
        }

        return $merged;
    }

    /**
     * Generic configuration merging for standard sections
     *
     * @param mixed $defaultValue Default configuration value
     * @param mixed $userValue User configuration value
     * @return mixed Merged value
     */
    private function mergeGenericConfig(mixed $defaultValue, mixed $userValue): mixed
    {
        if (is_array($defaultValue) && is_array($userValue)) {
            return array_merge($defaultValue, $userValue);
        }

        return $userValue;
    }

    /**
     * Register core utility services
     */
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
            fn($app): IndexBuilder => new IndexBuilder(
                normalizer: $app->make('laravel-fuzzy.normalizer')
            )
        );

        $this->app->singleton(
            AdvancedScoringCalculator::class,
            fn($app): AdvancedScoringCalculator => new AdvancedScoringCalculator()
        );

        $this->app->alias(AdvancedScoringCalculator::class, 'laravel-fuzzy.advanced-scoring');
    }

    /**
     * Register the index repository with its interface
     */
    private function registerRepository(): void
    {
        $this->app->singleton(
            IndexRepositoryInterface::class,
            fn($app): IndexRepository => new IndexRepository()
        );

        $this->app->alias(IndexRepositoryInterface::class, 'laravel-fuzzy.repository');
    }

    /**
     * Register the main fuzzy search service
     */
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

    /**
     * Register the scoring engine with its strategies
     */
    private function registerScoringSystem(): void
    {
        $this->app->singleton(ScoringEngine::class, function ($app): ScoringEngine {
            $advancedCalculator = $app->make(AdvancedScoringCalculator::class);

            return new ScoringEngine(
                exactMatchStrategy: new ExactMatchStrategy($advancedCalculator),
                wordMatchStrategy: new WordMatchStrategy($advancedCalculator),
                fuzzyMatchStrategy: new FuzzyMatchStrategy($advancedCalculator),
                multiWordStrategy: new MultiWordStrategy($advancedCalculator)
            );
        });

        $this->app->alias(ScoringEngine::class, 'laravel-fuzzy.scoring');
    }

    /**
     * Bootstrap package services and resources
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
     * Publish package resources for customization
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
     * Register console commands
     */
    private function registerCommands(): void
    {
        $this->commands([
            IndexSearchCommand::class,
            ClearIndexCommand::class,
            StatsIndexCommand::class,
            ClearCacheCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<int, string> List of service identifiers
     */
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
