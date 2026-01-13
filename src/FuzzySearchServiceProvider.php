<?php

declare(strict_types=1);

namespace Fuzzy;

use Fuzzy\Services\Algorithms\WordSimilarityComparator;
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

/**
 * Service provider for the Fuzzy Search package.
 *
 * Registers all core services, repositories, algorithms, and scoring systems
 * with appropriate bindings and configurations for dependency injection.
 */
class FuzzySearchServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfiguration();
        $this->registerCoreServices();
        $this->registerRepository();
        $this->registerSearchService();
        $this->registerScoringSystem();
        $this->registerAlgorithms();
    }

    /**
     * Merge default configuration with user configuration.
     *
     * @return void
     */
    private function mergeConfiguration(): void
    {
        $defaultConfig = require __DIR__ . '/../config/fuzzy-defaults.php';
        $userConfig = $this->app['config']->get('fuzzy', []);
        $mergedConfig = $this->mergeConfigurations($defaultConfig, $userConfig);

        $this->app['config']->set('fuzzy', $mergedConfig);
        $this->mergeConfigFrom(__DIR__ . '/../config/fuzzy.php', 'fuzzy');
    }

    /**
     * Intelligently merge default and user configurations.
     *
     * @param array<string, mixed> $defaults Default configuration
     * @param array<string, mixed> $userConfig User configuration
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
                'pipeline' => $defaultValue,
                'stop_words' => $this->mergeStopWords($defaultValue, $userValue),
                'scoring' => $this->mergeScoringConfig($defaultValue, $userValue),
                'algorithms' => $this->mergeAlgorithmsConfig($defaultValue, $userValue),
                default => $this->mergeGenericConfig($defaultValue, $userValue),
            };
        }

        return array_merge($merged, array_diff_key($userConfig, $defaults));
    }

    /**
     * Merge algorithms configuration.
     *
     * @param array<string, mixed> $defaults Default algorithms configuration
     * @param array<string, mixed> $userConfig User algorithms configuration
     * @return array<string, mixed> Merged algorithms configuration
     */
    private function mergeAlgorithmsConfig(array $defaults, array $userConfig): array
    {
        $merged = [];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($userConfig[$key])) {
                $merged[$key] = $defaultValue;
                continue;
            }

            $userValue = $userConfig[$key];

            $merged[$key] = match ($key) {
                'word_similarity' => $this->mergeWordSimilarityConfig($defaultValue, $userValue),
                default => $this->mergeGenericConfig($defaultValue, $userValue),
            };
        }

        return $merged;
    }

    /**
     * Merge WordSimilarityComparator configuration.
     *
     * @param array<string, mixed> $defaults Default configuration
     * @param array<string, mixed> $userConfig User configuration
     * @return array<string, mixed> Merged configuration
     */
    private function mergeWordSimilarityConfig(array $defaults, array $userConfig): array
    {
        return array_merge($defaults, $userConfig);
    }

    /**
     * Merge stop words arrays.
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
     * Merge scoring configuration.
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
                'consecutive_bonus' => $defaultValue,
                default => $this->mergeGenericConfig($defaultValue, $userValue),
            };
        }

        return $merged;
    }

    /**
     * Merge generic configuration values.
     *
     * @param mixed $defaultValue Default value
     * @param mixed $userValue User value
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
     * Register core utility services.
     *
     * @return void
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

        $this->app->singleton(AdvancedScoringCalculator::class, function ($app): AdvancedScoringCalculator {
            return new AdvancedScoringCalculator();
        });

        $this->app->alias(AdvancedScoringCalculator::class, 'laravel-fuzzy.advanced-scoring');
    }

    /**
     * Register algorithm services.
     *
     * @return void
     */
    private function registerAlgorithms(): void
    {
        $this->app->singleton(WordSimilarityComparator::class, function ($app): WordSimilarityComparator {
            $config = $app['config']->get('fuzzy.algorithms.word_similarity', []);

            return new WordSimilarityComparator(
                normalizer: $app->make('laravel-fuzzy.normalizer'),
                unmatchedLetterPenalty: $config['unmatched_letter_penalty'] ?? 0.2,
                maxScoreCap: $config['max_score_cap'] ?? 10.0,
                wordPenaltyPerChar: $config['word_penalty_per_char'] ?? 0.05,
                lengthPenaltyMultiplier: $config['length_penalty_multiplier'] ?? 0.05,
                minimalPenalty: $config['minimal_penalty'] ?? 0.1,
                matchFuzzinessPenalty: $config['match_fuzziness_penalty'] ?? 0.05,
                minWordMatchRatio: $config['min_word_match_ratio'] ?? 0.7
            );
        });

        $this->app->alias(WordSimilarityComparator::class, 'laravel-fuzzy.word-similarity');
        $this->app->bind('laravel-fuzzy.algorithms.word-similarity', WordSimilarityComparator::class);
    }

    /**
     * Register the index repository.
     *
     * @return void
     */
    private function registerRepository(): void
    {
        $this->app->singleton(IndexRepositoryInterface::class, function ($app): IndexRepository {
            return new IndexRepository();
        });

        $this->app->alias(IndexRepositoryInterface::class, 'laravel-fuzzy.repository');
    }

    /**
     * Register the fuzzy search service.
     *
     * @return void
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
     * Register the scoring engine.
     *
     * @return void
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
     * Bootstrap package services and resources.
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
     * Publish package resources for customization.
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
            ClearCacheCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string> Service identifiers
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
            'laravel-fuzzy.word-similarity',
            'laravel-fuzzy.algorithms.word-similarity',
            IndexRepositoryInterface::class,
            ScoringEngine::class,
            AdvancedScoringCalculator::class,
            WordSimilarityComparator::class,
        ];
    }
}
