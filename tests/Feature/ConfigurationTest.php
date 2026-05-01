<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\TestCase;
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

        // Act: Retrieve search service and get searchable models via ModelDiscovery
        $modelDiscovery = app(ModelDiscoveryInterface::class);
        $models = $modelDiscovery->getSearchableModels();

        // Assert: Verify both models are present
        $this->assertContains(User::class, $models);
        $this->assertContains(Product::class, $models);
        $this->assertCount(2, $models);
    }

    /**
     * Test auto-discovery is always enabled.
     */
    public function test_auto_discovery_always_enabled(): void
    {
        // Arrange: Clear configured models to force auto-discovery
        Config::set('fuzzy.searchable_models', []);

        // Act: Retrieve searchable models via ModelDiscovery
        $modelDiscovery = app(ModelDiscoveryInterface::class);
        $models = $modelDiscovery->getSearchableModels();

        // Assert: Models should be discovered automatically
        $this->assertIsArray($models);
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
        $indexBuilder = app(IndexBuilder::class);

        // Test via IndexBuilder methods
        $nameWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'name');
        $titleWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'title');
        $unknownWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'unknown');

        // Assert: Verify correct weights are applied
        $this->assertEqualsWithDelta(2.0, $nameWeight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.5, $titleWeight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.5, $unknownWeight, PHP_FLOAT_EPSILON);
    }

    /**
     * Helper to get field weight from IndexBuilder.
     */
    private function getFieldWeightFromIndexBuilder(IndexBuilder $indexBuilder, string $field): float
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($indexBuilder);
        $method = $reflection->getMethod('calculateFieldWeight');
        $method->setAccessible(true);
        return $method->invoke($indexBuilder, $field);
    }

    /**
     * Test stop words are loaded from internal files (not configurable by user).
     * 
     * Note: Stop words are now internal to the package and cannot be configured
     * by users. This test verifies that common stop words are properly removed.
     */
    public function test_stop_words_configuration(): void
    {
        $normalizer = app(StringNormalizer::class);

        // Act: Normalize query containing common stop words
        $query = $normalizer->normalizeQuery('the quick brown fox and the lazy dog');

        // Assert: Verify common stop words are removed (from internal stop words list)
        $this->assertStringNotContainsString('the', (string) $query);
        $this->assertStringNotContainsString('and', (string) $query);

        // Assert: Verify content words are preserved
        $this->assertStringContainsString('quick', (string) $query);
        $this->assertStringContainsString('brown', (string) $query);
        $this->assertStringContainsString('fox', (string) $query);
        $this->assertStringContainsString('lazy', (string) $query);
        $this->assertStringContainsString('dog', (string) $query);
    }

    /**
     * Test that stop words are preserved for protected fields (names, emails).
     */
    public function test_stop_words_preserved_for_protected_fields(): void
    {
        $normalizer = app(StringNormalizer::class);

        // Set protected fields (like name field)
        $normalizer->setProtectedFields(['name']);
        $normalizer->setCurrentField('name');

        // Act: Normalize a name containing stop words
        $query = $normalizer->normalizeQuery('Jean de La Fontaine');

        // Assert: Stop words "de" and "la" should be preserved
        $this->assertEquals('jean de la fontaine', $query);

        // Reset
        $normalizer->setCurrentField(null);
        $normalizer->setProtectedFields([]);
    }

    /**
     * Test that stop words are removed for non-protected fields.
     */
    public function test_stop_words_removed_for_non_protected_fields(): void
    {
        $normalizer = app(StringNormalizer::class);

        // Set current field as description (not protected)
        $normalizer->setCurrentField('description');

        // Act: Normalize a description containing stop words
        $query = $normalizer->normalizeQuery('the quick brown fox and the lazy dog');

        // Assert: Stop words should be removed
        $this->assertStringNotContainsString('the', (string) $query);
        $this->assertStringNotContainsString('and', (string) $query);

        // Reset
        $normalizer->setCurrentField(null);
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

        $searchService = app(SearchServiceInterface::class);

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

    /**
     * Test similarity configuration.
     */
    public function test_similarity_configuration(): void
    {
        // Arrange: Configure similarity settings
        Config::set('fuzzy.similarity', [
            'min_query_length' => 3,
            'min_similarity_threshold' => 0.2,
            'algorithm_weights' => [
                'longest_common_substring' => 0.5,
                'levenshtein' => 0.3,
                'prefix' => 0.2,
            ],
        ]);

        // Act: Get configured values
        $minQueryLength = config('fuzzy.similarity.min_query_length');
        $minThreshold = config('fuzzy.similarity.min_similarity_threshold');
        $weights = config('fuzzy.similarity.algorithm_weights');

        // Assert: Verify configuration values
        $this->assertEquals(3, $minQueryLength);
        $this->assertEqualsWithDelta(0.2, $minThreshold, PHP_FLOAT_EPSILON);
        $this->assertArrayHasKey('longest_common_substring', $weights);
        $this->assertArrayHasKey('levenshtein', $weights);
        $this->assertArrayHasKey('prefix', $weights);
    }

    /**
     * Test custom pipeline configuration.
     */
    public function test_custom_pipeline_configuration(): void
    {
        // Arrange: Configure custom pipeline stages
        Config::set('fuzzy.pipeline', [
            \Fuzzy\Tests\Fixtures\CustomStage::class,
        ]);

        // Act: Get pipeline config
        $pipelineConfig = config('fuzzy.pipeline', []);

        // Assert: Verify custom stages are configured
        $this->assertIsArray($pipelineConfig);
    }

    /**
     * Test that stop words are language-aware (French).
     */
    public function test_french_stop_words_are_removed(): void
    {
        // Temporarily set locale to French
        $originalLocale = app()->getLocale();
        app()->setLocale('fr');

        $normalizer = app(StringNormalizer::class);

        // Act: Normalize French query with stop words
        $query = $normalizer->normalizeQuery('le chat et le chien sont dans la maison');

        // Assert: French stop words should be removed
        $this->assertStringNotContainsString('le', (string) $query);
        $this->assertStringNotContainsString('et', (string) $query);
        $this->assertStringNotContainsString('sont', (string) $query);
        $this->assertStringNotContainsString('dans', (string) $query);
        $this->assertStringNotContainsString('la', (string) $query);

        // Content words should remain
        $this->assertStringContainsString('chat', (string) $query);
        $this->assertStringContainsString('chien', (string) $query);
        $this->assertStringContainsString('maison', (string) $query);

        // Restore original locale
        app()->setLocale($originalLocale);
    }

    /**
     * Test that stop words are language-aware (English fallback).
     */
    public function test_english_stop_words_are_removed(): void
    {
        // Temporarily set locale to English
        $originalLocale = app()->getLocale();
        app()->setLocale('en');

        $normalizer = app(StringNormalizer::class);

        // Act: Normalize English query with stop words
        $query = $normalizer->normalizeQuery('the cat and the dog are in the house');

        // Assert: English stop words should be removed
        $this->assertStringNotContainsString('the', (string) $query);
        $this->assertStringNotContainsString('and', (string) $query);
        $this->assertStringNotContainsString('are', (string) $query);
        $this->assertStringNotContainsString('in', (string) $query);

        // Content words should remain
        $this->assertStringContainsString('cat', (string) $query);
        $this->assertStringContainsString('dog', (string) $query);
        $this->assertStringContainsString('house', (string) $query);

        // Restore original locale
        app()->setLocale($originalLocale);
    }
}
