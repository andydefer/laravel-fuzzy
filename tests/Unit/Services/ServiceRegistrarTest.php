<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

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
use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\PipelineManagerInterface;
use Fuzzy\Contracts\ResultFilterInterface;
use Fuzzy\Contracts\ScoringEngineInterface;
use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\SearchProcessorInterface;
use Fuzzy\FuzzySearchServiceProvider;
use Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm;
use Fuzzy\Services\Algorithms\LevenshteinSimilarityAlgorithm;
use Fuzzy\Services\Algorithms\PrefixSimilarityAlgorithm;
use Fuzzy\Services\Algorithms\WordSimilarityComparator;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\PipelineStageManager;
use Fuzzy\Services\ServiceRegistrar;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Unit tests for the ServiceRegistrar class.
 * 
 * Verifies that all services, contracts, configurations, and commands
 * are properly registered with the Laravel service container.
 */
final class ServiceRegistrarTest extends TestCase
{
    private ServiceRegistrar $registrar;
    private ServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Create service provider and registrar instances
        $this->provider = new FuzzySearchServiceProvider($this->app);
        $this->registrar = new ServiceRegistrar(
            app: $this->app,
            provider: $this->provider
        );

        $this->app->detectEnvironment(fn() => 'testing');
    }

    protected function tearDown(): void
    {
        // Clean up test migrations
        $this->cleanupTestMigrations();
        parent::tearDown();
    }

    /**
     * Clean up migration files created during tests.
     */
    private function cleanupTestMigrations(): void
    {
        $migrationsPath = database_path('migrations');

        if (is_dir($migrationsPath)) {
            // Remove test migration files
            $testMigrationFiles = glob($migrationsPath . '/test_migration_*.php');
            foreach ($testMigrationFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // Remove dummy migration files created by tests
            $dummyFiles = glob($migrationsPath . '/2025_*.php');
            foreach ($dummyFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Test that registerAll registers all services without errors.
     */
    public function test_register_all_registers_services(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify all core services are bound in container
        $this->assertTrue($this->app->bound(FuzzySearchService::class));
        $this->assertTrue($this->app->bound(SimilarityCalculator::class));
        $this->assertTrue($this->app->bound(StringNormalizer::class));
        $this->assertTrue($this->app->bound(IndexBuilder::class));
        $this->assertTrue($this->app->bound(PipelineStageManager::class));
        $this->assertTrue($this->app->bound(WordSimilarityComparator::class));
        $this->assertTrue($this->app->bound(WordSimilarityComparatorConfig::class));
    }

    /**
     * Test that CacheStoreInterface is bound to LaravelCacheStore.
     */
    public function test_cache_store_interface_is_bound_to_laravel_cache_store(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify CacheStoreInterface resolves to LaravelCacheStore
        $instance = $this->app->make(CacheStoreInterface::class);
        $this->assertInstanceOf(LaravelCacheStore::class, $instance);
    }

    /**
     * Test that helper functions are loaded correctly.
     */
    public function test_helpers_are_loaded(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify helper constants are defined
        $this->assertTrue(defined('FUZZY_SCORE_IDENTICAL'));
        $this->assertTrue(defined('FUZZY_SCORE_NONE'));
        $this->assertTrue(defined('FUZZY_BASE_FACTOR'));
        $this->assertTrue(defined('FUZZY_DISTANCE_IDENTICAL'));
    }

    /**
     * Test that configuration is properly merged.
     */
    public function test_configuration_is_merged(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify configuration array structure
        $this->assertNotNull(config('fuzzy'));
        $this->assertArrayHasKey('cache', config('fuzzy'));
    }

    /**
     * Test that all contract bindings are registered.
     */
    public function test_contract_bindings_are_registered(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify all contract interfaces are bound
        $this->assertTrue($this->app->bound(CacheManagerInterface::class));
        $this->assertTrue($this->app->bound(CacheStoreInterface::class));
        $this->assertTrue($this->app->bound(ModelDiscoveryInterface::class));
        $this->assertTrue($this->app->bound(IndexManagerInterface::class));
        $this->assertTrue($this->app->bound(SearchProcessorInterface::class));
        $this->assertTrue($this->app->bound(ResultFilterInterface::class));
        $this->assertTrue($this->app->bound(PipelineManagerInterface::class));
        $this->assertTrue($this->app->bound(SearchContextInterface::class));
        $this->assertTrue($this->app->bound(ScoringEngineInterface::class));
    }

    /**
     * Test that configuration objects are registered as singletons.
     */
    public function test_config_objects_are_singletons(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify SimilarityCalculatorConfig is singleton
        $firstSimilarity = $this->app->make(SimilarityCalculatorConfig::class);
        $secondSimilarity = $this->app->make(SimilarityCalculatorConfig::class);
        $this->assertSame($firstSimilarity, $secondSimilarity);

        // Assert: Verify AdvancedScoringConfig is singleton
        $firstAdvanced = $this->app->make(AdvancedScoringConfig::class);
        $secondAdvanced = $this->app->make(AdvancedScoringConfig::class);
        $this->assertSame($firstAdvanced, $secondAdvanced);

        // Assert: Verify LongestCommonSubstringConfig is singleton
        $firstLongestCommon = $this->app->make(LongestCommonSubstringConfig::class);
        $secondLongestCommon = $this->app->make(LongestCommonSubstringConfig::class);
        $this->assertSame($firstLongestCommon, $secondLongestCommon);

        // Assert: Verify LevenshteinAlgorithmConfig is singleton
        $firstLevenshtein = $this->app->make(LevenshteinAlgorithmConfig::class);
        $secondLevenshtein = $this->app->make(LevenshteinAlgorithmConfig::class);
        $this->assertSame($firstLevenshtein, $secondLevenshtein);

        // Assert: Verify PrefixAlgorithmConfig is singleton
        $firstPrefix = $this->app->make(PrefixAlgorithmConfig::class);
        $secondPrefix = $this->app->make(PrefixAlgorithmConfig::class);
        $this->assertSame($firstPrefix, $secondPrefix);

        // Assert: Verify WordSimilarityComparatorConfig is singleton
        $firstWordSimilarity = $this->app->make(WordSimilarityComparatorConfig::class);
        $secondWordSimilarity = $this->app->make(WordSimilarityComparatorConfig::class);
        $this->assertSame($firstWordSimilarity, $secondWordSimilarity);
    }

    /**
     * Test that core services are registered as singletons.
     */
    public function test_core_services_are_singletons(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify FuzzySearchService is singleton
        $firstSearch = $this->app->make(FuzzySearchService::class);
        $secondSearch = $this->app->make(FuzzySearchService::class);
        $this->assertSame($firstSearch, $secondSearch);

        // Assert: Verify StringNormalizer is singleton
        $firstNormalizer = $this->app->make(StringNormalizer::class);
        $secondNormalizer = $this->app->make(StringNormalizer::class);
        $this->assertSame($firstNormalizer, $secondNormalizer);

        // Assert: Verify PipelineStageManager is singleton
        $firstStageManager = $this->app->make(PipelineStageManager::class);
        $secondStageManager = $this->app->make(PipelineStageManager::class);
        $this->assertSame($firstStageManager, $secondStageManager);

        // Assert: Verify SimilarityCalculator is singleton
        $firstSimilarityCalculator = $this->app->make(SimilarityCalculator::class);
        $secondSimilarityCalculator = $this->app->make(SimilarityCalculator::class);
        $this->assertSame($firstSimilarityCalculator, $secondSimilarityCalculator);
    }

    /**
     * Test that algorithm services are registered.
     */
    public function test_algorithm_services_are_registered(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify WordSimilarityComparator is instantiable
        $instance = $this->app->make(WordSimilarityComparator::class);
        $this->assertInstanceOf(WordSimilarityComparator::class, $instance);
    }

    /**
     * Test that similarity calculator has all algorithms registered.
     */
    public function test_similarity_calculator_has_algorithms_registered(): void
    {
        // Act: Register all services and get calculator instance
        $this->registrar->registerAll();
        $calculator = $this->app->make(SimilarityCalculator::class);

        // Assert: Verify algorithms are registered using reflection
        $reflection = new \ReflectionClass($calculator);
        $algorithmsProperty = $reflection->getProperty('algorithms');
        $algorithmsProperty->setAccessible(true);
        $algorithms = $algorithmsProperty->getValue($calculator);

        $this->assertCount(3, $algorithms);
        $this->assertInstanceOf(LongestCommonSubstringAlgorithm::class, $algorithms[0]);
        $this->assertInstanceOf(LevenshteinSimilarityAlgorithm::class, $algorithms[1]);
        $this->assertInstanceOf(PrefixSimilarityAlgorithm::class, $algorithms[2]);
    }

    /**
     * Test that algorithm configs are properly injected.
     */
    public function test_algorithm_configs_are_properly_injected(): void
    {
        // Act: Register all services and get calculator instance
        $this->registrar->registerAll();
        $calculator = $this->app->make(SimilarityCalculator::class);

        // Assert: Verify LCS algorithm config is correctly injected
        $reflection = new \ReflectionClass($calculator);
        $algorithmsProperty = $reflection->getProperty('algorithms');
        $algorithmsProperty->setAccessible(true);
        $algorithms = $algorithmsProperty->getValue($calculator);

        $lcsReflection = new \ReflectionClass($algorithms[0]);
        $lcsConfigProperty = $lcsReflection->getProperty('config');
        $lcsConfigProperty->setAccessible(true);
        $lcsConfig = $lcsConfigProperty->getValue($algorithms[0]);
        $this->assertInstanceOf(LongestCommonSubstringConfig::class, $lcsConfig);

        // Assert: Verify Levenshtein algorithm config is correctly injected
        $levReflection = new \ReflectionClass($algorithms[1]);
        $levConfigProperty = $levReflection->getProperty('config');
        $levConfigProperty->setAccessible(true);
        $levConfig = $levConfigProperty->getValue($algorithms[1]);
        $this->assertInstanceOf(LevenshteinAlgorithmConfig::class, $levConfig);

        // Assert: Verify Prefix algorithm config is correctly injected
        $prefixReflection = new \ReflectionClass($algorithms[2]);
        $prefixConfigProperty = $prefixReflection->getProperty('config');
        $prefixConfigProperty->setAccessible(true);
        $prefixConfig = $prefixConfigProperty->getValue($algorithms[2]);
        $this->assertInstanceOf(PrefixAlgorithmConfig::class, $prefixConfig);
    }

    /**
     * Test that scoring engine is registered.
     */
    public function test_scoring_engine_is_registered(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify ScoringEngineInterface is bound to concrete instance
        $instance = $this->app->make(ScoringEngineInterface::class);
        $this->assertInstanceOf(ScoringEngineInterface::class, $instance);
    }

    /**
     * Test that commands are instantiable when registered.
     */
    public function test_commands_are_instantiable(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify all commands can be instantiated
        $indexCommand = new IndexSearchCommand();
        $this->assertInstanceOf(IndexSearchCommand::class, $indexCommand);

        $clearCommand = new ClearIndexCommand();
        $this->assertInstanceOf(ClearIndexCommand::class, $clearCommand);

        $statsCommand = new StatsIndexCommand();
        $this->assertInstanceOf(StatsIndexCommand::class, $statsCommand);

        $cacheCommand = new ClearCacheCommand();
        $this->assertInstanceOf(ClearCacheCommand::class, $cacheCommand);
    }

    /**
     * Test that PipelineStageManager is properly initialized.
     */
    public function test_pipeline_stage_manager_is_initialized(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify PipelineStageManager is instantiable
        $stageManager = $this->app->make(PipelineStageManager::class);
        $this->assertInstanceOf(PipelineStageManager::class, $stageManager);
    }

    /**
     * Test that registerAll handles custom pipeline stages configuration.
     */
    public function test_register_all_handles_custom_pipeline_stages(): void
    {
        // Arrange: Set custom pipeline stages configuration
        config(['fuzzy.pipeline' => [\Fuzzy\Tests\Fixtures\CustomStage::class]]);

        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify pipeline manager is still instantiable with custom stages
        $pipelineManager = $this->app->make(PipelineManagerInterface::class);
        $this->assertInstanceOf(PipelineManagerInterface::class, $pipelineManager);
    }

    /**
     * Test that helper file not found throws appropriate exception.
     */
    public function test_helper_file_not_found_throws_exception(): void
    {
        // Arrange: Temporarily rename helpers.php file
        $helpersPath = __DIR__ . '/../../../src/helpers.php';
        $tempPath = __DIR__ . '/../../../src/helpers.php.temp';

        if (file_exists($helpersPath)) {
            rename($helpersPath, $tempPath);
        }

        try {
            // Act & Assert: Expect exception when helpers file is missing
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('helpers.php not found at');

            $registrar = new ServiceRegistrar(
                app: $this->app,
                provider: $this->provider
            );

            $registrar->registerAll();
        } finally {
            // Restore the helpers file
            if (file_exists($tempPath)) {
                rename($tempPath, $helpersPath);
            }
        }
    }

    /**
     * Test that ContextualNormalizerInterface is properly bound.
     */
    public function test_contextual_normalizer_interface_is_bound(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();

        // Assert: Verify ContextualNormalizerInterface resolves to StringNormalizer
        $instance = $this->app->make(ContextualNormalizerInterface::class);
        $this->assertInstanceOf(ContextualNormalizerInterface::class, $instance);
        $this->assertInstanceOf(StringNormalizer::class, $instance);
    }

    /**
     * Test that IndexBuilder receives ContextualNormalizerInterface dependency.
     */
    public function test_index_builder_receives_contextual_normalizer(): void
    {
        // Act: Register all services
        $this->registrar->registerAll();
        $indexBuilder = $this->app->make(IndexBuilder::class);

        // Assert: Verify normalizer dependency is properly injected
        $reflection = new \ReflectionClass($indexBuilder);
        $property = $reflection->getProperty('normalizer');
        $property->setAccessible(true);
        $normalizer = $property->getValue($indexBuilder);

        $this->assertInstanceOf(ContextualNormalizerInterface::class, $normalizer);
        $this->assertInstanceOf(StringNormalizer::class, $normalizer);
    }

    /**
     * Test that migration publication does not cause errors.
     */
    public function test_migrations_publication_does_not_cause_errors(): void
    {
        // Act & Assert: Registration should complete without exceptions
        $this->registrar->registerAll();
        $this->assertTrue(true);
    }

    /**
     * Test that multiple calls to registerAll are safe.
     */
    public function test_multiple_register_calls_are_safe(): void
    {
        // Act: Call registerAll multiple times
        for ($i = 0; $i < 3; $i++) {
            $this->registrar->registerAll();
        }

        // Assert: No exceptions were thrown
        $this->assertTrue(true);
    }
}
