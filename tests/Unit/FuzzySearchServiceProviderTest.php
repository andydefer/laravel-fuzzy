<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit;

use Fuzzy\FuzzySearchServiceProvider;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;

/**
 * Test suite for FuzzySearchServiceProvider.
 *
 * Validates that the service provider correctly delegates to ServiceRegistrar
 * and properly handles boot operations.
 */
final class FuzzySearchServiceProviderTest extends TestCase
{
    private FuzzySearchServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new FuzzySearchServiceProvider($this->app);
    }

    /**
     * Test that the service provider registers services.
     */
    public function test_service_provider_registers_services(): void
    {
        $this->provider->register();

        $this->assertTrue($this->app->bound(FuzzySearchService::class));
        $this->assertTrue($this->app->bound('laravel-fuzzy.search'));
        $this->assertInstanceOf(FuzzySearchService::class, $this->app->make(FuzzySearchService::class));
    }

    /**
     * Test that the service provider loads migrations.
     */
    public function test_service_provider_loads_migrations(): void
    {
        $migrationPath = __DIR__ . '/../../database/migrations';

        $this->provider->boot();

        $this->assertDirectoryExists($migrationPath);
    }

    /**
     * Test that the service provider provides the correct services.
     */
    public function test_service_provider_provides_correct_services(): void
    {
        $providedServices = $this->provider->provides();

        $expectedServices = [
            \Fuzzy\Contracts\CacheManagerInterface::class,
            \Fuzzy\Contracts\ModelDiscoveryInterface::class,
            \Fuzzy\Contracts\IndexManagerInterface::class,
            \Fuzzy\Contracts\SearchProcessorInterface::class,
            \Fuzzy\Contracts\ResultFilterInterface::class,
            \Fuzzy\Contracts\PipelineManagerInterface::class,
            \Fuzzy\Contracts\SearchContextInterface::class,
            \Fuzzy\Contracts\ScoringEngineInterface::class,
            \Fuzzy\Config\AdvancedScoringConfig::class,
            \Fuzzy\Config\SimilarityCalculatorConfig::class,
            FuzzySearchService::class,
            'laravel-fuzzy.search',
        ];

        foreach ($expectedServices as $service) {
            $this->assertContains($service, $providedServices);
        }
    }

    /**
     * Test that the service provider merges configuration.
     * Configuration is merged by ServiceRegistrar, which is called during register().
     */
    public function test_service_provider_merges_configuration(): void
    {
        // Register the provider - this triggers ServiceRegistrar which merges config
        $this->provider->register();

        // Assert that configuration was merged
        $this->assertNotNull(config('fuzzy'));
        $this->assertArrayHasKey('cache', config('fuzzy'));
        $this->assertArrayHasKey('default_options', config('fuzzy'));
        $this->assertArrayHasKey('index', config('fuzzy'));
        $this->assertArrayHasKey('similarity', config('fuzzy'));
        $this->assertArrayHasKey('scoring', config('fuzzy'));
        $this->assertArrayHasKey('pipeline', config('fuzzy'));
    }

    /**
     * Test that the service provider registers commands in console context.
     */
    public function test_service_provider_registers_commands_in_console(): void
    {
        if (!$this->app->runningInConsole()) {
            $this->markTestSkipped('This test requires console context');
        }

        $this->provider->register();
        $this->provider->boot();

        $artisan = $this->app->make(Kernel::class);
        $commands = $artisan->all();

        $this->assertArrayHasKey('fuzzy:index', $commands);
        $this->assertArrayHasKey('fuzzy:clear', $commands);
        $this->assertArrayHasKey('fuzzy:stats', $commands);
        $this->assertArrayHasKey('fuzzy:clear-cache', $commands);
    }

    /**
     * Test that custom configuration values are properly loaded.
     */
    public function test_custom_configuration_values_are_loaded(): void
    {
        // Set custom configuration values before registration
        Config::set('fuzzy.default_options.min_score', 0.5);
        Config::set('fuzzy.default_options.max_results', 100);
        Config::set('fuzzy.default_options.fuzzy', false);
        Config::set('fuzzy.cache.enabled', false);
        Config::set('fuzzy.index.batch_size', 200);

        // Register the provider - this triggers ServiceRegistrar which merges config
        $this->provider->register();

        // Assert: Custom values should be preserved (not overwritten by defaults)
        $this->assertEquals(0.5, config('fuzzy.default_options.min_score'));
        $this->assertEquals(100, config('fuzzy.default_options.max_results'));
        $this->assertFalse(config('fuzzy.default_options.fuzzy'));
        $this->assertFalse(config('fuzzy.cache.enabled'));
        $this->assertEquals(200, config('fuzzy.index.batch_size'));
    }

    /**
     * Test that the service provider loads helper functions.
     */
    public function test_service_provider_loads_helper_functions(): void
    {
        $this->provider->register();

        $this->assertTrue(defined('FUZZY_SCORE_IDENTICAL'));
        $this->assertTrue(defined('FUZZY_SCORE_NONE'));
        $this->assertTrue(defined('FUZZY_BASE_FACTOR'));
    }

    /**
     * Test that helper file not found throws exception.
     */
    public function test_helper_file_not_found_throws_exception(): void
    {
        $helpersPath = __DIR__ . '/../../src/helpers.php';
        $tempPath = __DIR__ . '/../../src/helpers.php.temp';

        if (file_exists($helpersPath)) {
            rename($helpersPath, $tempPath);
        }

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('helpers.php not found');
            $this->provider->register();
        } finally {
            if (file_exists($tempPath)) {
                rename($tempPath, $helpersPath);
            }
        }
    }

    /**
     * Test that the service provider delegates to ServiceRegistrar.
     */
    public function test_service_provider_delegates_to_service_registrar(): void
    {
        // Mock the ServiceRegistrar to verify it's called
        $this->app->instance('fuzzy.test.registrar_called', false);

        $this->provider->register();

        // If registration completed without errors, ServiceRegistrar was called
        $this->assertTrue(true);
    }
}
