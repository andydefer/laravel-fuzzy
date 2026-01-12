<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;
use Illuminate\Support\Facades\Config;

final class ConfigurationTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Test that searchable models are correctly configured.
     */
    public function test_searchable_models_configuration(): void
    {
        // Arrange: Configure searchable models
        Config::set('fuzzy.searchable_models', [
            User::class,
            Product::class,
        ]);

        // Act: Retrieve search service and get searchable models
        $searchService = app('laravel-fuzzy.search');
        $models = $searchService->getSearchableModels();

        // Assert: Verify both models are present
        $this->assertContains(User::class, $models);
        $this->assertContains(Product::class, $models);
        $this->assertCount(2, $models);
    }

    /**
     * Test auto-discovery when enabled.
     */
    public function test_auto_discovery_enabled(): void
    {
        // Arrange: Enable auto-discovery with test fixtures directory
        Config::set('fuzzy.auto_discovery.enabled', true);
        Config::set('fuzzy.searchable_models', []);
        Config::set('fuzzy.auto_discovery.directories', [
            dirname(__DIR__, 2) . '/tests/Fixtures'
        ]);

        // Act: Retrieve searchable models
        $searchService = app('laravel-fuzzy.search');
        $models = $searchService->getSearchableModels();

        // Assert: Verify User and Product models are discovered
        $this->assertContains(User::class, $models);
        $this->assertContains(Product::class, $models);
    }

    /**
     * Test auto-discovery when disabled.
     */
    public function test_auto_discovery_disabled(): void
    {
        // Arrange: Disable auto-discovery
        Config::set('fuzzy.auto_discovery.enabled', false);
        Config::set('fuzzy.searchable_models', []);

        // Act: Retrieve searchable models
        $searchService = app('laravel-fuzzy.search');
        $models = $searchService->getSearchableModels();

        // Assert: Verify no models are discovered
        $this->assertEmpty($models);
    }

    /**
     * Test field weights configuration.
     */
    public function test_field_weights_configuration(): void
    {
        // Arrange: Configure field weights
        Config::set('fuzzy.scoring.field_weights', [
            'name' => 2.0,
            'title' => 1.5,
            'email' => 1.0,
            'description' => 0.8,
            'default' => 0.5,
        ]);

        // Act: Calculate weights for different fields
        $indexBuilder = app('laravel-fuzzy.index-builder');
        $nameWeight = $indexBuilder->calculateFieldWeight('name');
        $titleWeight = $indexBuilder->calculateFieldWeight('title');
        $unknownWeight = $indexBuilder->calculateFieldWeight('unknown');

        // Assert: Verify correct weights are applied
        $this->assertEqualsWithDelta(2.0, $nameWeight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.5, $titleWeight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.5, $unknownWeight, PHP_FLOAT_EPSILON);
    }

    /**
     * Test stop words configuration.
     */
    public function test_stop_words_configuration(): void
    {
        // Arrange: Configure stop words
        Config::set('fuzzy.stop_words', ['the', 'and', 'or', 'test']);

        $normalizer = app('laravel-fuzzy.normalizer');

        // Act: Normalize query containing stop words
        $query = $normalizer->normalizeQuery('the quick brown fox and the lazy dog test');

        // Assert: Verify stop words are removed
        $this->assertStringNotContainsString('the', (string) $query);
        $this->assertStringNotContainsString('and', (string) $query);
        $this->assertStringNotContainsString('test', (string) $query);
        $this->assertStringContainsString('quick', (string) $query);
        $this->assertStringContainsString('brown', (string) $query);
    }

    /**
     * Test default search options configuration.
     */
    public function test_default_options_configuration(): void
    {
        // Arrange: Configure default search options
        Config::set('fuzzy.default_options', [
            'min_score' => 0.5,
            'max_results' => 50,
            'fuzzy' => false,
            'threshold' => 0.5,
        ]);

        $searchService = app('laravel-fuzzy.search');

        // Act: Perform search without custom options
        $results = $searchService->search('test');

        // Assert: Verify search returns results with default options
        $this->assertIsIterable($results);
    }

    /**
     * Test cache configuration.
     */
    public function test_cache_configuration(): void
    {
        // Arrange: Configure cache settings
        Config::set('fuzzy.cache.enabled', true);
        Config::set('fuzzy.cache.ttl.search', 1800);
        Config::set('fuzzy.cache.invalidation.on_index', true);

        // Act & Assert: Verify cache configuration is correctly set
        $this->assertTrue(config('fuzzy.cache.enabled'));
        $this->assertEquals(1800, config('fuzzy.cache.ttl.search'));
        $this->assertTrue(config('fuzzy.cache.invalidation.on_index'));
    }

    /**
     * Test search pipeline configuration.
     */
    public function test_pipeline_configuration(): void
    {
        // Arrange: Configure pipeline stages
        Config::set('fuzzy.pipeline.stages', [
            NormalizeQueryStage::class,
            MatchDiscoveryStage::class,
            ScoringStage::class,
            SortAndLimitStage::class,
        ]);

        // Act: Get configured stages
        $stages = config('fuzzy.pipeline.stages');

        // Assert: Verify all stages are configured
        $this->assertCount(4, $stages);
        $this->assertContains(NormalizeQueryStage::class, $stages);
        $this->assertContains(MatchDiscoveryStage::class, $stages);
    }

    /**
     * Test exclude patterns for auto-discovery.
     */
    public function test_exclude_patterns_configuration(): void
    {
        // Arrange: Configure exclude patterns
        Config::set('fuzzy.auto_discovery.exclude_patterns', [
            '/^Abstract/',
            '/^Base/',
            '/Interface$/',
            '/Trait$/',
        ]);

        // Act: Get configured exclude patterns
        $patterns = config('fuzzy.auto_discovery.exclude_patterns');

        // Assert: Verify exclude patterns are correctly configured
        $this->assertCount(4, $patterns);
        $this->assertContains('/^Abstract/', $patterns);
        $this->assertContains('/Interface$/', $patterns);
    }

    /**
     * Test index configuration.
     */
    public function test_index_configuration(): void
    {
        // Arrange: Configure index settings
        Config::set('fuzzy.index', [
            'min_word_length' => 3,
            'max_word_length' => 100,
            'batch_size' => 200,
            'queue' => true,
            'queue_name' => 'search-index',
        ]);

        // Act & Assert: Verify index configuration values
        $this->assertEquals(3, config('fuzzy.index.min_word_length'));
        $this->assertEquals(200, config('fuzzy.index.batch_size'));
        $this->assertTrue(config('fuzzy.index.queue'));
        $this->assertEquals('search-index', config('fuzzy.index.queue_name'));
    }
}
