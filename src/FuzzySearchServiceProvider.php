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
    public function register(): void
    {
        $this->mergeDefaultConfig();
        $this->registerCoreServices();
        $this->registerRepository();
        $this->registerSearchService();
        $this->registerScoringSystem();
    }

    private function mergeDefaultConfig(): void
    {
        // Charger la configuration par défaut (protégée)
        $defaultConfig = require __DIR__ . '/../config/fuzzy-defaults.php';

        // Charger la configuration utilisateur si elle existe
        $userConfig = $this->app['config']->get('fuzzy', []);

        // Fusionner intelligemment les configurations
        $mergedConfig = $this->mergeConfigurations($defaultConfig, $userConfig);

        // Définir la configuration fusionnée
        $this->app['config']->set('fuzzy', $mergedConfig);

        // Garder aussi l'ancienne méthode pour compatibilité
        $this->mergeConfigFrom(__DIR__ . '/../config/fuzzy.php', 'fuzzy');
    }

    private function mergeConfigurations(array $defaults, array $userConfig): array
    {
        $merged = [];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($userConfig[$key])) {
                $merged[$key] = $defaultValue;
                continue;
            }

            $userValue = $userConfig[$key];

            // Logique de fusion spécifique pour chaque section
            switch ($key) {
                case 'pipeline':
                    // Pipeline non modifiable - toujours utiliser la valeur par défaut
                    $merged[$key] = $defaultValue;
                    break;

                case 'stop_words':
                    // Fusionner les tableaux - ajouter mais pas supprimer
                    if (is_array($defaultValue) && is_array($userValue)) {
                        $merged[$key] = array_unique(array_merge($defaultValue, $userValue));
                    } else {
                        $merged[$key] = $defaultValue;
                    }
                    break;

                case 'scoring':
                    // Fusionner récursivement mais protéger certains sous-éléments
                    $merged[$key] = $this->mergeScoringConfig($defaultValue, $userValue);
                    break;

                default:
                    // Fusion standard pour les autres sections
                    if (is_array($defaultValue) && is_array($userValue)) {
                        $merged[$key] = array_merge($defaultValue, $userValue);
                    } else {
                        $merged[$key] = $userValue;
                    }
            }
        }

        // Ajouter les clés qui n'existent que dans la config utilisateur
        foreach ($userConfig as $key => $value) {
            if (!isset($defaults[$key])) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private function mergeScoringConfig(array $defaults, array $userConfig): array
    {
        $merged = [];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($userConfig[$key])) {
                $merged[$key] = $defaultValue;
                continue;
            }

            $userValue = $userConfig[$key];

            switch ($key) {
                case 'consecutive_bonus':
                    // Les bonus consécutifs sont critiques - toujours utiliser les valeurs par défaut
                    $merged[$key] = $defaultValue;
                    break;

                default:
                    if (is_array($defaultValue) && is_array($userValue)) {
                        $merged[$key] = array_merge($defaultValue, $userValue);
                    } else {
                        $merged[$key] = $userValue;
                    }
            }
        }

        return $merged;
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
        $this->app->singleton(ScoringEngine::class, function ($app): ScoringEngine {
            $advancedCalculator = $app->make(AdvancedScoringCalculator::class);

            return new ScoringEngine(
                new ExactMatchStrategy($advancedCalculator),
                new WordMatchStrategy($advancedCalculator),
                new FuzzyMatchStrategy($advancedCalculator),
                new MultiWordStrategy($advancedCalculator)
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
            ClearCacheCommand::class,
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
