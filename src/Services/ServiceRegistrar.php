<?php

declare(strict_types=1);

namespace Fuzzy\Services;

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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

/**
 * Centralized service registrar for the Fuzzy Search package.
 *
 * This class is responsible for registering all services, bindings, and configurations
 * required by the Fuzzy Search package. It handles dependency injection, singleton
 * registrations, command registration, and asset publishing.
 */
class ServiceRegistrar
{
    use ServiceProviderHelper;

    private PipelineStageManager $stageManager;

    /**
     * Create a new ServiceRegistrar instance.
     *
     * @param Application $app The Laravel application container
     * @param ServiceProvider $provider The service provider instance for publishing
     */
    public function __construct(private Application $app, ServiceProvider $provider)
    {
        $this->provider = $provider;
        $this->stageManager = new PipelineStageManager($app);
    }

    /**
     * Register all services and configurations.
     *
     * This is the main entry point that orchestrates all registration methods.
     */
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

    /**
     * Register and load helper functions.
     *
     * @throws \RuntimeException If helpers.php file is not found
     */
    private function registerHelpers(): void
    {
        $helpersPath = __DIR__ . '/../helpers.php';
        if (!file_exists($helpersPath)) {
            throw new \RuntimeException("helpers.php not found at: {$helpersPath}");
        }
        require_once $helpersPath;
    }

    /**
     * Register configuration files and config singletons.
     *
     * Merges package config with application config and registers
     * configuration classes as singletons for dependency injection.
     */
    private function registerConfig(): void
    {
        // Merge the package configuration file
        $this->mergeConfigFrom(__DIR__ . '/../../config/fuzzy.php', 'fuzzy');

        // Register configuration classes as singletons
        $this->app->singleton(SimilarityCalculatorConfig::class, fn() => SimilarityCalculatorConfig::createDefault());
        $this->app->singleton(AdvancedScoringConfig::class, fn() => AdvancedScoringConfig::fromConfig());

        // Register algorithm-specific configs with fromConfig() method
        $this->app->singleton(LongestCommonSubstringConfig::class, fn() => LongestCommonSubstringConfig::fromConfig());
        $this->app->singleton(LevenshteinAlgorithmConfig::class, fn() => LevenshteinAlgorithmConfig::fromConfig());
        $this->app->singleton(PrefixAlgorithmConfig::class, fn() => PrefixAlgorithmConfig::fromConfig());

        // Register WordSimilarityComparator config
        $this->app->singleton(WordSimilarityComparatorConfig::class, fn() => WordSimilarityComparatorConfig::fromConfig());
    }

    /**
     * Register interface-to-implementation bindings.
     *
     * Maps all package contracts to their concrete implementations
     * for dependency injection resolution.
     */
    private function registerContracts(): void
    {
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

    /**
     * Register core services as singletons.
     *
     * This method registers all the essential services including:
     * - Normalizers
     * - Calculators
     * - Managers
     * - Processors
     * - The main search service
     */
    private function registerCoreServices(): void
    {
        // Register normalizers as singletons
        $this->app->singleton(ContextualNormalizerInterface::class, fn() => new StringNormalizer());
        $this->app->singleton(StringNormalizer::class, fn() => new StringNormalizer());

        // Register service managers
        $this->app->singleton(ResultFilterService::class, fn() => new ResultFilterService());
        $this->app->singleton(CacheManagerService::class, fn() => new CacheManagerService());
        $this->app->singleton(ModelDiscoveryService::class, fn() => new ModelDiscoveryService());

        // PipelineStageManager as singleton
        $this->app->singleton(PipelineStageManager::class, fn($app) => new PipelineStageManager($app));

        // Index Builder - uses ContextualNormalizerInterface
        $this->app->singleton(IndexBuilder::class, fn($app) => new IndexBuilder(
            $app->make(ContextualNormalizerInterface::class)
        ));

        // Index Manager
        $this->app->singleton(IndexManagerService::class, fn($app) => new IndexManagerService(
            indexBuilder: $app->make(IndexBuilder::class),
            indexRepository: $app->make(IndexRepositoryInterface::class),
            modelDiscovery: $app->make(ModelDiscoveryInterface::class)
        ));

        // Similarity Calculator with algorithm registration
        $this->app->singleton(SimilarityCalculator::class, function ($app) {
            $calculator = new SimilarityCalculator($app->make(SimilarityCalculatorConfig::class));

            // Register all similarity algorithms with their specific configurations
            $calculator->addAlgorithm(new LongestCommonSubstringAlgorithm($app->make(LongestCommonSubstringConfig::class)));
            $calculator->addAlgorithm(new LevenshteinSimilarityAlgorithm($app->make(LevenshteinAlgorithmConfig::class)));
            $calculator->addAlgorithm(new PrefixSimilarityAlgorithm($app->make(PrefixAlgorithmConfig::class)));

            return $calculator;
        });

        // Advanced Scoring Calculator
        $this->app->singleton(AdvancedScoringCalculator::class, fn($app) => new AdvancedScoringCalculator(
            $app->make(AdvancedScoringConfig::class)
        ));

        // Pipeline Manager with configured stages
        $this->app->singleton(PipelineManagerService::class, function ($app) {
            $stageClasses = $this->stageManager->getMergedStages();
            $stages = $this->stageManager->createStageInstances($stageClasses);

            return new PipelineManagerService(
                pipeline: $app->make(Pipeline::class),
                stages: $stages
            );
        });

        // Search Processor - Core search orchestration
        $this->app->singleton(SearchProcessorService::class, fn($app) => new SearchProcessorService(
            pipeline: $app->make(Pipeline::class),
            normalizer: $app->make(StringNormalizer::class),
            similarityCalculator: $app->make(SimilarityCalculator::class),
            indexRepository: $app->make(IndexRepositoryInterface::class),
            scoringEngine: $app->make(ScoringEngineInterface::class),
            modelDiscovery: $app->make(ModelDiscoveryInterface::class),
            resultFilter: $app->make(ResultFilterInterface::class)
        ));

        // Main Search Service - bound to interface for easy resolution
        $this->app->singleton(SearchServiceInterface::class, fn($app) => new FuzzySearchService(
            cacheManager: $app->make(CacheManagerInterface::class),
            modelDiscovery: $app->make(ModelDiscoveryInterface::class),
            indexManager: $app->make(IndexManagerInterface::class),
            searchProcessor: $app->make(SearchProcessorInterface::class)
        ));

        // Register aliases for backward compatibility and convenience
        $this->app->alias(SearchServiceInterface::class, 'laravel-fuzzy.search');
        $this->app->alias(SearchServiceInterface::class, FuzzySearchService::class);
    }

    /**
     * Register algorithm comparator services.
     */
    private function registerAlgorithms(): void
    {
        $this->app->singleton(WordSimilarityComparator::class, fn($app) => new WordSimilarityComparator(
            normalizer: $app->make(StringNormalizer::class),
            config: $app->make(WordSimilarityComparatorConfig::class)
        ));
    }

    /**
     * Register scoring strategies and scoring engine.
     */
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

    /**
     * Register Artisan commands for console environments.
     *
     * Commands are only registered when running in console mode
     * to avoid loading them in web requests.
     */
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

    /**
     * Register assets for publishing (config and migrations).
     *
     * This method allows users to publish the package configuration
     * and migration files to their application.
     */
    private function registerPublishing(): void
    {
        // Publish configuration file using the helper trait
        $this->publishes([
            __DIR__ . '/../../config/fuzzy.php' => config_path('fuzzy.php'),
        ], 'fuzzy-config');

        // Publish migrations only if they don't already exist
        $this->publishMigrationsIfNotExists();
    }

    /**
     * Publish migration files only if they don't already exist.
     *
     * This method prevents overwriting existing migration files that may have been
     * customized by the user. Each migration file is checked individually before
     * being added to the publishing list.
     *
     * Benefits:
     * - Preserves user customizations to migrations
     * - Prevents accidental data loss from schema changes
     * - Allows safe package updates without migration conflicts
     *
     * Usage:
     * php artisan vendor:publish --tag=fuzzy-migrations
     *
     * @return void
     */
    private function publishMigrationsIfNotExists(): void
    {
        $sourceMigrationsPath = __DIR__ . '/../../database/migrations';
        $targetMigrationsPath = database_path('migrations');

        // If source migrations directory doesn't exist, nothing to publish
        if (!is_dir($sourceMigrationsPath)) {
            return;
        }

        // Create target directory if it doesn't exist
        if (!is_dir($targetMigrationsPath)) {
            mkdir($targetMigrationsPath, 0755, true);
        }

        // Get all migration files from source
        $migrationFiles = glob($sourceMigrationsPath . '/*.php');
        $filesToPublish = [];
        $skippedFiles = [];

        foreach ($migrationFiles as $sourceFile) {
            $fileName = basename($sourceFile);
            $targetFile = $targetMigrationsPath . '/' . $fileName;

            // Only include files that don't already exist in target
            if (!file_exists($targetFile)) {
                $filesToPublish[$sourceFile] = $targetFile;
            } else {
                $skippedFiles[] = $fileName;
            }
        }

        // Display skipped files message if any migrations were skipped
        if (!empty($skippedFiles) && $this->app->runningInConsole()) {
            $this->outputSkippedMigrationsMessage($skippedFiles);
        }

        // Publish only the migrations that don't exist
        if (!empty($filesToPublish)) {
            $this->publishes($filesToPublish, 'fuzzy-migrations');
        } elseif ($this->app->runningInConsole()) {
            $this->outputAllMigrationsSkippedMessage();
        }
    }

    /**
     * Display a message showing which migration files were skipped.
     *
     * @param array<int, string> $skippedFiles List of skipped migration file names
     * @return void
     */
    private function outputSkippedMigrationsMessage(array $skippedFiles): void
    {
        $count = count($skippedFiles);

        try {
            // Try to use Artisan facade for output (works during vendor:publish)
            if (Artisan::getFacadeApplication() && method_exists(Artisan::getFacadeApplication(), 'make')) {
                $output = Artisan::getFacadeApplication()->make('Illuminate\Contracts\Console\Kernel')->getOutput();
                if ($output) {
                    $this->writeOutputMessages($output, $skippedFiles, $count);
                    return;
                }
            }
        } catch (\Exception $e) {
            // Fallback to simple output if Artisan doesn't respond
        }

        // Fallback: Simple console output using error_log or info
        $message = sprintf(
            '[Fuzzy] %d migration file(s) already exist and were preserved: %s',
            $count,
            implode(', ', $skippedFiles)
        );

        if (function_exists('info')) {
            info($message);
            info('[Fuzzy] Use --force to overwrite existing migrations.');
        } else {
            error_log($message);
            error_log('[Fuzzy] Use --force to overwrite existing migrations.');
        }
    }

    /**
     * Write output messages using the console output interface.
     *
     * @param object $output Console output instance
     * @param array<int, string> $skippedFiles List of skipped files
     * @param int $count Number of skipped files
     * @return void
     */
    private function writeOutputMessages(object $output, array $skippedFiles, int $count): void
    {
        $message = sprintf(
            "  <fg=yellow;options=bold>📁 %d %s already exist%s:</>",
            $count,
            $count === 1 ? 'migration file' : 'migration files',
            $count === 1 ? 's' : ''
        );

        $output->writeln($message);

        foreach ($skippedFiles as $file) {
            $output->writeln(sprintf("     <fg=gray>→ %s</fg=gray>", $file));
        }

        $output->writeln(
            "     <fg=yellow;options=bold>💡 Skipped to preserve existing custom migrations. Use --force to overwrite.</>"
        );
        $output->writeln('');
    }

    /**
     * Display a message when all migrations were skipped.
     *
     * @return void
     */
    private function outputAllMigrationsSkippedMessage(): void
    {
        try {
            // Try to use Artisan facade for output (works during vendor:publish)
            if (Artisan::getFacadeApplication() && method_exists(Artisan::getFacadeApplication(), 'make')) {
                $output = Artisan::getFacadeApplication()->make('Illuminate\Contracts\Console\Kernel')->getOutput();
                if ($output) {
                    $output->writeln('');
                    $output->writeln('  <fg=yellow;options=bold>📁 All migration files already exist and were preserved.</>');
                    $output->writeln('  <fg=yellow;options=bold>💡 Use --force to overwrite existing migrations.</>');
                    $output->writeln('');
                    return;
                }
            }
        } catch (\Exception $e) {
            // Fallback to simple output
        }

        // Fallback: Simple console output
        $message = '[Fuzzy] All migration files already exist and were preserved.';

        if (function_exists('info')) {
            info($message);
            info('[Fuzzy] Use --force to overwrite existing migrations.');
        } else {
            error_log($message);
            error_log('[Fuzzy] Use --force to overwrite existing migrations.');
        }
    }
}
