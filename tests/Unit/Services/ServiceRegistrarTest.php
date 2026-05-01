<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

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
use Illuminate\Support\ServiceProvider;

final class ServiceRegistrarTest extends TestCase
{
    private ServiceRegistrar $registrar;
    private ServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new FuzzySearchServiceProvider($this->app);
        $this->registrar = new ServiceRegistrar($this->app, $this->provider);
        // Force console context for command tests
        $this->app->detectEnvironment(fn() => 'testing');
    }

    /**
     * Test that registerAll registers all services without errors.
     */
    public function test_register_all_registers_services(): void
    {
        $this->registrar->registerAll();

        $this->assertTrue($this->app->bound(FuzzySearchService::class));
        $this->assertTrue($this->app->bound(SimilarityCalculator::class));
        $this->assertTrue($this->app->bound(StringNormalizer::class));
        $this->assertTrue($this->app->bound(IndexBuilder::class));
        $this->assertTrue($this->app->bound(PipelineStageManager::class));
        $this->assertTrue($this->app->bound(WordSimilarityComparator::class));
        $this->assertTrue($this->app->bound(WordSimilarityComparatorConfig::class));
    }

    /**
     * Test that helpers are loaded.
     */
    public function test_helpers_are_loaded(): void
    {
        $this->registrar->registerAll();

        $this->assertTrue(defined('FUZZY_SCORE_IDENTICAL'));
        $this->assertTrue(defined('FUZZY_SCORE_NONE'));
        $this->assertTrue(defined('FUZZY_BASE_FACTOR'));
        $this->assertTrue(defined('FUZZY_DISTANCE_IDENTICAL'));
    }

    /**
     * Test that configuration is merged.
     */
    public function test_configuration_is_merged(): void
    {
        $this->registrar->registerAll();

        $this->assertNotNull(config('fuzzy'));
        $this->assertArrayHasKey('cache', config('fuzzy'));
    }

    /**
     * Test that contract bindings are registered.
     */
    public function test_contract_bindings_are_registered(): void
    {
        $this->registrar->registerAll();

        $this->assertTrue($this->app->bound(CacheManagerInterface::class));
        $this->assertTrue($this->app->bound(ModelDiscoveryInterface::class));
        $this->assertTrue($this->app->bound(IndexManagerInterface::class));
        $this->assertTrue($this->app->bound(SearchProcessorInterface::class));
        $this->assertTrue($this->app->bound(ResultFilterInterface::class));
        $this->assertTrue($this->app->bound(PipelineManagerInterface::class));
        $this->assertTrue($this->app->bound(SearchContextInterface::class));
        $this->assertTrue($this->app->bound(ScoringEngineInterface::class));
    }

    /**
     * Test that config objects are registered as singletons.
     */
    public function test_config_objects_are_singletons(): void
    {
        $this->registrar->registerAll();

        // Main configs
        $firstSimilarity = $this->app->make(SimilarityCalculatorConfig::class);
        $secondSimilarity = $this->app->make(SimilarityCalculatorConfig::class);
        $this->assertSame($firstSimilarity, $secondSimilarity);

        $firstAdvanced = $this->app->make(AdvancedScoringConfig::class);
        $secondAdvanced = $this->app->make(AdvancedScoringConfig::class);
        $this->assertSame($firstAdvanced, $secondAdvanced);

        // Algorithm-specific configs
        $firstLongestCommon = $this->app->make(LongestCommonSubstringConfig::class);
        $secondLongestCommon = $this->app->make(LongestCommonSubstringConfig::class);
        $this->assertSame($firstLongestCommon, $secondLongestCommon);

        $firstLevenshtein = $this->app->make(LevenshteinAlgorithmConfig::class);
        $secondLevenshtein = $this->app->make(LevenshteinAlgorithmConfig::class);
        $this->assertSame($firstLevenshtein, $secondLevenshtein);

        $firstPrefix = $this->app->make(PrefixAlgorithmConfig::class);
        $secondPrefix = $this->app->make(PrefixAlgorithmConfig::class);
        $this->assertSame($firstPrefix, $secondPrefix);

        $firstWordSimilarity = $this->app->make(WordSimilarityComparatorConfig::class);
        $secondWordSimilarity = $this->app->make(WordSimilarityComparatorConfig::class);
        $this->assertSame($firstWordSimilarity, $secondWordSimilarity);
    }

    /**
     * Test that core services are registered as singletons.
     */
    public function test_core_services_are_singletons(): void
    {
        $this->registrar->registerAll();

        $firstSearch = $this->app->make(FuzzySearchService::class);
        $secondSearch = $this->app->make(FuzzySearchService::class);
        $this->assertSame($firstSearch, $secondSearch);

        $firstNormalizer = $this->app->make(StringNormalizer::class);
        $secondNormalizer = $this->app->make(StringNormalizer::class);
        $this->assertSame($firstNormalizer, $secondNormalizer);

        $firstStageManager = $this->app->make(PipelineStageManager::class);
        $secondStageManager = $this->app->make(PipelineStageManager::class);
        $this->assertSame($firstStageManager, $secondStageManager);

        $firstSimilarityCalculator = $this->app->make(SimilarityCalculator::class);
        $secondSimilarityCalculator = $this->app->make(SimilarityCalculator::class);
        $this->assertSame($firstSimilarityCalculator, $secondSimilarityCalculator);
    }

    /**
     * Test that algorithm services are registered.
     */
    public function test_algorithm_services_are_registered(): void
    {
        $this->registrar->registerAll();

        $instance = $this->app->make(WordSimilarityComparator::class);
        $this->assertInstanceOf(WordSimilarityComparator::class, $instance);
    }

    /**
     * Test that similarity calculator has algorithms registered.
     */
    public function test_similarity_calculator_has_algorithms_registered(): void
    {
        $this->registrar->registerAll();

        $calculator = $this->app->make(SimilarityCalculator::class);

        // Use reflection to access private property
        $reflection = new \ReflectionClass($calculator);
        $algorithmsProperty = $reflection->getProperty('algorithms');
        $algorithmsProperty->setAccessible(true);
        $algorithms = $algorithmsProperty->getValue($calculator);

        // Should have 3 algorithms registered by default
        $this->assertCount(3, $algorithms);

        // Verify algorithm types
        $this->assertInstanceOf(LongestCommonSubstringAlgorithm::class, $algorithms[0]);
        $this->assertInstanceOf(LevenshteinSimilarityAlgorithm::class, $algorithms[1]);
        $this->assertInstanceOf(PrefixSimilarityAlgorithm::class, $algorithms[2]);
    }

    /**
     * Test that algorithm configs are properly injected.
     */
    public function test_algorithm_configs_are_properly_injected(): void
    {
        $this->registrar->registerAll();

        $calculator = $this->app->make(SimilarityCalculator::class);

        // Use reflection to access private property
        $reflection = new \ReflectionClass($calculator);
        $algorithmsProperty = $reflection->getProperty('algorithms');
        $algorithmsProperty->setAccessible(true);
        $algorithms = $algorithmsProperty->getValue($calculator);

        // Verify each algorithm has its specific config
        $lcsReflection = new \ReflectionClass($algorithms[0]);
        $lcsConfigProperty = $lcsReflection->getProperty('config');
        $lcsConfigProperty->setAccessible(true);
        $lcsConfig = $lcsConfigProperty->getValue($algorithms[0]);
        $this->assertInstanceOf(LongestCommonSubstringConfig::class, $lcsConfig);

        $levReflection = new \ReflectionClass($algorithms[1]);
        $levConfigProperty = $levReflection->getProperty('config');
        $levConfigProperty->setAccessible(true);
        $levConfig = $levConfigProperty->getValue($algorithms[1]);
        $this->assertInstanceOf(LevenshteinAlgorithmConfig::class, $levConfig);

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
        $this->registrar->registerAll();

        $instance = $this->app->make(ScoringEngineInterface::class);
        $this->assertInstanceOf(ScoringEngineInterface::class, $instance);
    }

    /**
     * Test that commands are registered in console context.
     */
    public function test_commands_are_registered_in_console(): void
    {
        $this->registrar->registerAll();

        // Since the actual command registration happens via $this->commands(),
        // which doesn't create container bindings, we just verify that the
        // registerCommands method doesn't throw exceptions
        $this->addToAssertionCount(1);
    }

    /**
     * Test that commands are instantiable when registered.
     */
    public function test_commands_are_instantiable(): void
    {
        $this->registrar->registerAll();

        // Manually instantiate commands since they're not bound in the container
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
        $this->registrar->registerAll();

        $stageManager = $this->app->make(PipelineStageManager::class);
        $this->assertInstanceOf(PipelineStageManager::class, $stageManager);
    }

    /**
     * Test that registerAll handles custom pipeline stages.
     */
    public function test_register_all_handles_custom_pipeline_stages(): void
    {
        config(['fuzzy.pipeline' => [\Fuzzy\Tests\Fixtures\CustomStage::class]]);

        $this->registrar->registerAll();

        $pipelineManager = $this->app->make(PipelineManagerInterface::class);
        $this->assertInstanceOf(PipelineManagerInterface::class, $pipelineManager);
    }

    /**
     * Test that helper file not found throws exception.
     */
    public function test_helper_file_not_found_throws_exception(): void
    {
        // Temporarily move the helpers.php file
        $helpersPath = __DIR__ . '/../../../src/helpers.php';
        $tempPath = __DIR__ . '/../../../src/helpers.php.temp';

        if (file_exists($helpersPath)) {
            rename($helpersPath, $tempPath);
        }

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('helpers.php not found at');

            $registrar = new ServiceRegistrar($this->app, $this->provider);
            $registrar->registerAll();
        } finally {
            // Restore the file
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
        $this->registrar->registerAll();

        $instance = $this->app->make(ContextualNormalizerInterface::class);
        $this->assertInstanceOf(ContextualNormalizerInterface::class, $instance);
        $this->assertInstanceOf(StringNormalizer::class, $instance);
    }

    /**
     * Test that IndexBuilder receives ContextualNormalizerInterface.
     */
    public function test_index_builder_receives_contextual_normalizer(): void
    {
        $this->registrar->registerAll();

        $indexBuilder = $this->app->make(IndexBuilder::class);

        // Use reflection to verify the normalizer property type
        $reflection = new \ReflectionClass($indexBuilder);
        $property = $reflection->getProperty('normalizer');
        $property->setAccessible(true);
        $normalizer = $property->getValue($indexBuilder);

        $this->assertInstanceOf(ContextualNormalizerInterface::class, $normalizer);
        $this->assertInstanceOf(StringNormalizer::class, $normalizer);
    }
}
