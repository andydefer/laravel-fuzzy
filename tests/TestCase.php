<?php

declare(strict_types=1);

namespace Fuzzy\Tests;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase as Orchestra;
use Fuzzy\FuzzySearchServiceProvider;

abstract class TestCase extends Orchestra
{

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageMigrations();
        $this->loadTestMigrations();
        $this->configureMemoryCache();
    }


    /**
     * Load package migrations.
     */
    private function loadPackageMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load test-specific migrations.
     */
    private function loadTestMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Configure in-memory cache for tests.
     */
    private function configureMemoryCache(): void
    {
        Config::set('cache.default', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            FuzzySearchServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('fuzzy.searchable_models', [
            \Fuzzy\Tests\Fixtures\User::class,
            \Fuzzy\Tests\Fixtures\Product::class,
        ]);
    }
}
