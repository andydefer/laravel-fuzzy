<?php

declare(strict_types=1);

namespace Fuzzy\Tests;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase as Orchestra;
use Fuzzy\FuzzySearchServiceProvider;

/**
 * Base test case for Fuzzy Search package tests.
 *
 * Provides common setup and configuration for testing the fuzzy search functionality.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Set up the test environment.
     *
     * Loads migrations and configures in-memory caching for isolated testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageMigrations();
        $this->loadTestMigrations();
        $this->configureMemoryCache();

        config(['fuzzy.cache.enabled' => false]);
    }

    /**
     * Load package migrations.
     *
     * @return void
     */
    private function loadPackageMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load test-specific migrations.
     *
     * @return void
     */
    private function loadTestMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Configure in-memory cache for tests.
     *
     * @return void
     */
    private function configureMemoryCache(): void
    {
        Config::set('cache.default', 'array');
    }

    /**
     * Get package service providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            FuzzySearchServiceProvider::class,
        ];
    }

    /**
     * Configure test environment.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Ajoutez cette configuration
        $app['config']->set('fuzzy.scoring.field_weights', [
            'name' => 1.0,
            'title' => 0.9,
            'email' => 0.8,
            'description' => 0.7,
            'default' => 0.5,
        ]);
        $app['config']->set('fuzzy.eager_load', []);

        $app['config']->set('fuzzy.searchable_models', [
            \Fuzzy\Tests\Fixtures\User::class,
            \Fuzzy\Tests\Fixtures\Product::class,
        ]);
    }
}
