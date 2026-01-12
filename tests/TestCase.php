<?php

declare(strict_types=1);

namespace Fuzzy\Tests;

use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Illuminate\Foundation\Application;
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

    /**
     * Get package service providers.
     *
     * @param Application $app
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
     * @param Application $app
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
            User::class,
            Product::class,
        ]);
    }
}
