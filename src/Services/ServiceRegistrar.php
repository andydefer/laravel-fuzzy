<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Cache\LaravelCacheStore;
use Fuzzy\Commands\ClearCacheCommand;
use Fuzzy\Commands\ClearIndexCommand;
use Fuzzy\Commands\IndexSearchCommand;
use Fuzzy\Commands\StatsIndexCommand;
use Fuzzy\Config\AdvancedScoringConfig;
use Fuzzy\Config\LevenshteinAlgorithmConfig;
use Fuzzy\Config\LongestCommonSubstringConfig;
use Fuzzy\Config\PrefixAlgorithmConfig;
use Fuzzy\Config\SimilarityCalculatorConfig;
use Fuzzy\Config\WordSimilarityComparatorConfig;
use Fuzzy\Contracts\CacheManagerInterface;
use Fuzzy\Contracts\CacheStoreInterface;
use Fuzzy\Contracts\ContextualNormalizerInterface;
use Fuzzy\Contracts\IndexManagerInterface;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\PipelineManagerInterface;
use Fuzzy\Contracts\ResultFilterInterface;
use Fuzzy\Contracts\ScoringEngineInterface;
use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\SearchProcessorInterface;
use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Repositories\IndexRepository;
use Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm;
use Fuzzy\Services\Algorithms\LevenshteinSimilarityAlgorithm;
use Fuzzy\Services\Algorithms\PrefixSimilarityAlgorithm;
use Fuzzy\Services\Algorithms\WordSimilarityComparator;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\Scoring\ScoringStrategies\ExactMatchStrategy;
use Fuzzy\Services\Scoring\ScoringStrategies\FuzzyMatchStrategy;
use Fuzzy\Services\Scoring\ScoringStrategies\MultiWordStrategy;
use Fuzzy\Services\Scoring\ScoringStrategies\WordMatchStrategy;
use Fuzzy\SearchContext;
use Fuzzy\Traits\ServiceProviderHelper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;

/**
 * Centralized service registrar for the Fuzzy Search package.
 */
class ServiceRegistrar
{
    use ServiceProviderHelper;

    private PipelineStageManager $stageManager;

    public function __construct(private Application $app, ServiceProvider $provider)
    {
        $this->provider = $provider;
        $this->stageManager = new PipelineStageManager($app);
    }

    public function registerAll(): void
    {
        $this->registerHelpers();
        $this->registerConfig();
        $this->registerContracts();
        $this->registerCoreServices();
        $this->registerAlgorithms();
        $this->registerScoring();
        $this->registerCommands();
        $this->registerPublishing();
    }

    private function registerHelpers(): void
    {
        $helpersPath = __DIR__ . '/../helpers.php';
        if (!file_exists($helpersPath)) {
            throw new \RuntimeException("helpers.php not found at: {$helpersPath}");
        }
        require_once $helpersPath;
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/fuzzy.php', 'fuzzy');

        $this->app->singleton(SimilarityCalculatorConfig::class, fn() => SimilarityCalculatorConfig::createDefault());
        $this->app->singleton(AdvancedScoringConfig::class, fn() => AdvancedScoringConfig::fromConfig());

        $this->app->singleton(LongestCommonSubstringConfig::class, fn() => LongestCommonSubstringConfig::fromConfig());
        $this->app->singleton(LevenshteinAlgorithmConfig::class, fn() => LevenshteinAlgorithmConfig::fromConfig());
        $this->app->singleton(PrefixAlgorithmConfig::class, fn() => PrefixAlgorithmConfig::fromConfig());

        $this->app->singleton(WordSimilarityComparatorConfig::class, fn() => WordSimilarityComparatorConfig::fromConfig());
    }

    private function registerContracts(): void
    {
        // Register CacheStoreInterface with Laravel implementation
        $this->app->bind(CacheStoreInterface::class, LaravelCacheStore::class);

        $bindings = [
            CacheManagerInterface::class => CacheManagerService::class,
            ModelDiscoveryInterface::class => ModelDiscoveryService::class,
            IndexManagerInterface::class => IndexManagerService::class,
            SearchProcessorInterface::class => SearchProcessorService::class,
            ResultFilterInterface::class => ResultFilterService::class,
            PipelineManagerInterface::class => PipelineManagerService::class,
            IndexRepositoryInterface::class => IndexRepository::class,
            SearchContextInterface::class => SearchContext::class,
            ScoringEngineInterface::class => ScoringEngine::class,
            SearchServiceInterface::class => FuzzySearchService::class,
            ContextualNormalizerInterface::class => StringNormalizer::class,
        ];

        foreach ($bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    private function registerCoreServices(): void
    {
        $this->app->singleton(ContextualNormalizerInterface::class, fn() => new StringNormalizer());
        $this->app->singleton(StringNormalizer::class, fn() => new StringNormalizer());

        $this->app->singleton(ResultFilterService::class, fn() => new ResultFilterService());

        // CacheManagerService with CacheStoreInterface injection
        $this->app->singleton(CacheManagerService::class, function ($app) {
            return new CacheManagerService(
                cache: $app->make(CacheStoreInterface::class)
            );
        });

        $this->app->singleton(ModelDiscoveryService::class, fn() => new ModelDiscoveryService());

        $this->app->singleton(PipelineStageManager::class, fn($app) => new PipelineStageManager($app));

        $this->app->singleton(IndexBuilder::class, fn($app) => new IndexBuilder(
            $app->make(ContextualNormalizerInterface::class)
        ));

        $this->app->singleton(IndexManagerService::class, fn($app) => new IndexManagerService(
            indexBuilder: $app->make(IndexBuilder::class),
            indexRepository: $app->make(IndexRepositoryInterface::class),
            modelDiscovery: $app->make(ModelDiscoveryInterface::class)
        ));

        $this->app->singleton(SimilarityCalculator::class, function ($app) {
            $calculator = new SimilarityCalculator($app->make(SimilarityCalculatorConfig::class));

            $calculator->addAlgorithm(new LongestCommonSubstringAlgorithm($app->make(LongestCommonSubstringConfig::class)));
            $calculator->addAlgorithm(new LevenshteinSimilarityAlgorithm($app->make(LevenshteinAlgorithmConfig::class)));
            $calculator->addAlgorithm(new PrefixSimilarityAlgorithm($app->make(PrefixAlgorithmConfig::class)));

            return $calculator;
        });

        $this->app->singleton(AdvancedScoringCalculator::class, fn($app) => new AdvancedScoringCalculator(
            $app->make(AdvancedScoringConfig::class)
        ));

        $this->app->singleton(PipelineManagerService::class, function ($app) {
            $stageClasses = $this->stageManager->getMergedStages();
            $stages = $this->stageManager->createStageInstances($stageClasses);

            return new PipelineManagerService(
                pipeline: $app->make(Pipeline::class),
                stages: $stages
            );
        });

        $this->app->singleton(SearchProcessorService::class, fn($app) => new SearchProcessorService(
            pipeline: $app->make(Pipeline::class),
            normalizer: $app->make(StringNormalizer::class),
            similarityCalculator: $app->make(SimilarityCalculator::class),
            indexRepository: $app->make(IndexRepositoryInterface::class),
            scoringEngine: $app->make(ScoringEngineInterface::class),
            modelDiscovery: $app->make(ModelDiscoveryInterface::class),
            resultFilter: $app->make(ResultFilterInterface::class)
        ));

        $this->app->singleton(SearchServiceInterface::class, fn($app) => new FuzzySearchService(
            cacheManager: $app->make(CacheManagerInterface::class),
            modelDiscovery: $app->make(ModelDiscoveryInterface::class),
            indexManager: $app->make(IndexManagerInterface::class),
            searchProcessor: $app->make(SearchProcessorInterface::class)
        ));

        $this->app->alias(SearchServiceInterface::class, 'laravel-fuzzy.search');
        $this->app->alias(SearchServiceInterface::class, FuzzySearchService::class);
    }

    private function registerAlgorithms(): void
    {
        $this->app->singleton(WordSimilarityComparator::class, fn($app) => new WordSimilarityComparator(
            normalizer: $app->make(StringNormalizer::class),
            config: $app->make(WordSimilarityComparatorConfig::class)
        ));
    }

    private function registerScoring(): void
    {
        $this->app->singleton(ScoringEngineInterface::class, function ($app) {
            $calculator = $app->make(AdvancedScoringCalculator::class);

            return new ScoringEngine(
                exactMatchStrategy: new ExactMatchStrategy($calculator),
                wordMatchStrategy: new WordMatchStrategy($calculator),
                fuzzyMatchStrategy: new FuzzyMatchStrategy($calculator),
                multiWordStrategy: new MultiWordStrategy()
            );
        });
    }

    private function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            IndexSearchCommand::class,
            ClearIndexCommand::class,
            StatsIndexCommand::class,
            ClearCacheCommand::class,
        ]);
    }

    private function registerPublishing(): void
    {
        // Publication de la configuration
        $this->publishes([
            __DIR__ . '/../../config/fuzzy.php' => config_path('fuzzy.php'),
        ], 'fuzzy-config');

        // Publication des migrations
        $this->registerMigrationsForPublishing();
    }

    /**
     * Enregistre les fichiers de migration pour publication.
     */
    private function registerMigrationsForPublishing(): void
    {
        $sourceMigrationsPath = __DIR__ . '/../../database/migrations';

        if (!is_dir($sourceMigrationsPath)) {
            return;
        }

        $migrationFiles = glob($sourceMigrationsPath . '/*.php');

        if (empty($migrationFiles)) {
            return;
        }

        $filesToPublish = [];
        foreach ($migrationFiles as $sourceFile) {
            $fileName = basename($sourceFile);
            $targetFile = database_path('migrations/' . $fileName);
            $filesToPublish[$sourceFile] = $targetFile;
        }

        $this->publishes($filesToPublish, 'fuzzy-migrations');
    }
}
