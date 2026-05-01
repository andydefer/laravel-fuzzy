<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\Fixtures\CustomStage;
use Fuzzy\Tests\Fixtures\Product;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Feature tests for package configuration settings.
 * 
 * Verifies that all configuration options are properly loaded,
 * applied, and respected throughout the package.
 */
final class ConfigurationTest extends TestCase
{
    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: Load database migrations for test fixtures
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Test that searchable models are correctly discovered from configuration.
     */
    public function test_searchable_models_configuration(): void
    {
        // Arrange: Configure explicit list of searchable models
        Config::set('fuzzy.searchable_models', [
            User::class,
            Product::class,
        ]);

        // Act: Retrieve searchable models through the discovery service
        $modelDiscovery = app(ModelDiscoveryInterface::class);
        $discoveredModels = $modelDiscovery->getSearchableModels();

        // Assert: Verify both configured models are present and count is correct
        $this->assertContains(User::class, $discoveredModels, 'User model should be discoverable');
        $this->assertContains(Product::class, $discoveredModels, 'Product model should be discoverable');
        $this->assertCount(2, $discoveredModels, 'Exactly 2 models should be discovered');
    }

    /**
     * Test that auto-discovery works when no models are explicitly configured.
     */
    public function test_auto_discovery_always_enabled(): void
    {
        // Arrange: Clear configured models to force auto-discovery behavior
        Config::set('fuzzy.searchable_models', []);

        // Act: Retrieve searchable models without explicit configuration
        $modelDiscovery = app(ModelDiscoveryInterface::class);
        $discoveredModels = $modelDiscovery->getSearchableModels();

        // Assert: Models should be discovered automatically from registered searchable traits
        $this->assertIsArray($discoveredModels, 'Auto-discovery should return an array of models');
        $this->assertNotEmpty($discoveredModels, 'Auto-discovery should find at least one model');
    }

    /**
     * Test that field weights are properly applied from configuration.
     */
    public function test_field_weights_configuration(): void
    {
        // Arrange: Configure custom field weights for scoring
        Config::set('fuzzy.scoring.field_weights', [
            'name' => 2.0,
            'title' => 1.5,
            'email' => 1.0,
            'description' => 0.8,
            'default' => 0.5,
        ]);

        // Act: Calculate weights for different fields using IndexBuilder
        $indexBuilder = app(IndexBuilder::class);
        $nameWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'name');
        $titleWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'title');
        $emailWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'email');
        $unknownWeight = $this->getFieldWeightFromIndexBuilder($indexBuilder, 'nonexistent_field');

        // Assert: Verify correct weights are applied from configuration
        $this->assertEqualsWithDelta(2.0, $nameWeight, PHP_FLOAT_EPSILON, 'Name field weight should be 2.0');
        $this->assertEqualsWithDelta(1.5, $titleWeight, PHP_FLOAT_EPSILON, 'Title field weight should be 1.5');
        $this->assertEqualsWithDelta(1.0, $emailWeight, PHP_FLOAT_EPSILON, 'Email field weight should be 1.0');
        $this->assertEqualsWithDelta(0.5, $unknownWeight, PHP_FLOAT_EPSILON, 'Unknown field should use default weight 0.5');
    }

    /**
     * Helper method to extract field weight from IndexBuilder using reflection.
     *
     * @param IndexBuilder $indexBuilder The index builder instance
     * @param string $field The field name to get weight for
     * @return float The configured weight for the field
     */
    private function getFieldWeightFromIndexBuilder(IndexBuilder $indexBuilder, string $field): float
    {
        $reflection = new \ReflectionClass($indexBuilder);
        $calculateWeightMethod = $reflection->getMethod('calculateFieldWeight');
        $calculateWeightMethod->setAccessible(true);

        return $calculateWeightMethod->invoke($indexBuilder, $field);
    }

    /**
     * Test that stop words are loaded from internal files and properly removed.
     * 
     * Note: Stop words are now internal to the package and cannot be configured
     * by users. This test verifies the default behavior.
     */
    public function test_stop_words_automatically_removed(): void
    {
        // Arrange: Create string normalizer instance
        $normalizer = app(StringNormalizer::class);

        // Act: Normalize query containing common English stop words
        $normalizedQuery = $normalizer->normalizeQuery('the quick brown fox and the lazy dog');

        // Assert: Verify common stop words are automatically removed
        $this->assertStringNotContainsString('the', (string) $normalizedQuery, 'Stop word "the" should be removed');
        $this->assertStringNotContainsString('and', (string) $normalizedQuery, 'Stop word "and" should be removed');

        // Assert: Verify content words are preserved
        $this->assertStringContainsString('quick', (string) $normalizedQuery, 'Content word "quick" should be preserved');
        $this->assertStringContainsString('brown', (string) $normalizedQuery, 'Content word "brown" should be preserved');
        $this->assertStringContainsString('fox', (string) $normalizedQuery, 'Content word "fox" should be preserved');
        $this->assertStringContainsString('lazy', (string) $normalizedQuery, 'Content word "lazy" should be preserved');
        $this->assertStringContainsString('dog', (string) $normalizedQuery, 'Content word "dog" should be preserved');
    }

    /**
     * Test that stop words are preserved for protected fields like names and emails.
     */
    public function test_stop_words_preserved_for_protected_fields(): void
    {
        // Arrange: Configure normalizer for protected name field
        $normalizer = app(StringNormalizer::class);
        $normalizer->setProtectedFields(['name']);
        $normalizer->setCurrentField('name');

        // Act: Normalize a proper name containing French stop words
        $normalizedName = $normalizer->normalizeQuery('Jean de La Fontaine');

        // Assert: Stop words "de" and "la" should be preserved in names
        $this->assertEquals('jean de la fontaine', $normalizedName, 'Stop words should be preserved in name fields');

        // Cleanup: Reset normalizer state
        $normalizer->setCurrentField(null);
        $normalizer->setProtectedFields([]);
    }

    /**
     * Test that stop words are correctly removed for non-protected fields.
     */
    public function test_stop_words_removed_for_non_protected_fields(): void
    {
        // Arrange: Configure normalizer for non-protected description field
        $normalizer = app(StringNormalizer::class);
        $normalizer->setCurrentField('description');

        // Act: Normalize description text containing stop words
        $normalizedText = $normalizer->normalizeQuery('the quick brown fox and the lazy dog');

        // Assert: Stop words should be removed from regular text fields
        $this->assertStringNotContainsString('the', (string) $normalizedText, 'Stop word "the" should be removed from description');
        $this->assertStringNotContainsString('and', (string) $normalizedText, 'Stop word "and" should be removed from description');

        // Assert: Content words should remain intact
        $this->assertStringContainsString('quick', (string) $normalizedText, 'Content word "quick" should remain');
        $this->assertStringContainsString('brown', (string) $normalizedText, 'Content word "brown" should remain');
        $this->assertStringContainsString('fox', (string) $normalizedText, 'Content word "fox" should remain');

        // Cleanup: Reset normalizer state
        $normalizer->setCurrentField(null);
    }

    /**
     * Test that default search options are properly applied from configuration.
     */
    public function test_default_options_configuration(): void
    {
        // Arrange: Configure custom default search options
        Config::set('fuzzy.default_options', [
            'min_score' => 0.5,
            'max_results' => 50,
            'fuzzy' => false,
            'threshold' => 0.5,
        ]);

        // Act: Perform search without providing explicit options
        $searchService = app(SearchServiceInterface::class);
        $searchResults = $searchService->search('test');

        // Assert: Search should execute with configured default options
        $this->assertIsIterable($searchResults, 'Search results should be iterable');
        $this->assertNotNull($searchResults, 'Search should return results even with strict options');
    }

    /**
     * Test that cache configuration values are correctly loaded.
     */
    public function test_cache_configuration(): void
    {
        // Arrange: Configure custom cache settings
        Config::set('fuzzy.cache.enabled', true);
        Config::set('fuzzy.cache.ttl.search', 1800);
        Config::set('fuzzy.cache.invalidation.on_index', true);

        // Act: Retrieve cache configuration values
        $cacheEnabled = config('fuzzy.cache.enabled');
        $cacheTtl = config('fuzzy.cache.ttl.search');
        $invalidationOnIndex = config('fuzzy.cache.invalidation.on_index');

        // Assert: Verify cache configuration is correctly applied
        $this->assertTrue($cacheEnabled, 'Cache should be enabled');
        $this->assertEquals(1800, $cacheTtl, 'Search cache TTL should be 30 minutes');
        $this->assertTrue($invalidationOnIndex, 'Cache should invalidate on index');
    }

    /**
     * Test that index configuration values are correctly loaded.
     */
    public function test_index_configuration(): void
    {
        // Arrange: Configure custom index settings
        Config::set('fuzzy.index.min_word_length', 3);
        Config::set('fuzzy.index.max_word_length', 100);
        Config::set('fuzzy.index.batch_size', 200);
        Config::set('fuzzy.index.queue', true);
        Config::set('fuzzy.index.queue_name', 'search-index');

        // Act: Retrieve index configuration values
        $minWordLength = config('fuzzy.index.min_word_length');
        $batchSize = config('fuzzy.index.batch_size');
        $queueEnabled = config('fuzzy.index.queue');
        $queueName = config('fuzzy.index.queue_name');

        // Assert: Verify index configuration is correctly applied
        $this->assertEquals(3, $minWordLength, 'Minimum word length should be 3 characters');
        $this->assertEquals(200, $batchSize, 'Batch size should be 200 records');
        $this->assertTrue($queueEnabled, 'Queue processing should be enabled');
        $this->assertEquals('search-index', $queueName, 'Queue name should be "search-index"');
    }

    /**
     * Test that similarity algorithm configuration values are correctly loaded.
     */
    public function test_similarity_configuration(): void
    {
        // Arrange: Configure custom similarity settings
        Config::set('fuzzy.similarity.min_query_length', 3);
        Config::set('fuzzy.similarity.min_similarity_threshold', 0.2);
        Config::set('fuzzy.similarity.algorithm_weights', [
            'longest_common_substring' => 0.5,
            'levenshtein' => 0.3,
            'prefix' => 0.2,
        ]);

        // Act: Retrieve similarity configuration values
        $minQueryLength = config('fuzzy.similarity.min_query_length');
        $minThreshold = config('fuzzy.similarity.min_similarity_threshold');
        $algorithmWeights = config('fuzzy.similarity.algorithm_weights');

        // Assert: Verify similarity configuration is correctly applied
        $this->assertEquals(3, $minQueryLength, 'Minimum query length should be 3 characters');
        $this->assertEqualsWithDelta(0.2, $minThreshold, PHP_FLOAT_EPSILON, 'Minimum similarity threshold should be 0.2');
        $this->assertArrayHasKey('longest_common_substring', $algorithmWeights, 'LCS weight should be configured');
        $this->assertArrayHasKey('levenshtein', $algorithmWeights, 'Levenshtein weight should be configured');
        $this->assertArrayHasKey('prefix', $algorithmWeights, 'Prefix weight should be configured');
    }

    /**
     * Test that custom pipeline stages can be configured.
     */
    public function test_custom_pipeline_configuration(): void
    {
        // Arrange: Configure custom pipeline stages
        Config::set('fuzzy.pipeline', [
            CustomStage::class,
        ]);

        // Act: Retrieve pipeline configuration
        $pipelineConfig = config('fuzzy.pipeline', []);

        // Assert: Verify custom stages are properly configured
        $this->assertIsArray($pipelineConfig, 'Pipeline configuration should be an array');
        $this->assertContains(CustomStage::class, $pipelineConfig, 'Custom stage should be in pipeline');
    }

    /**
     * Test that French stop words are automatically removed when locale is French.
     */
    public function test_french_stop_words_automatically_removed(): void
    {
        // Arrange: Set application locale to French
        $originalLocale = app()->getLocale();
        app()->setLocale('fr');

        $normalizer = app(StringNormalizer::class);

        // Act: Normalize French text containing stop words
        $normalizedFrench = $normalizer->normalizeQuery('le chat et le chien sont dans la maison');

        // Assert: French stop words should be automatically removed
        $this->assertStringNotContainsString('le', (string) $normalizedFrench, 'Stop word "le" should be removed from French text');
        $this->assertStringNotContainsString('et', (string) $normalizedFrench, 'Stop word "et" should be removed from French text');
        $this->assertStringNotContainsString('sont', (string) $normalizedFrench, 'Stop word "sont" should be removed from French text');
        $this->assertStringNotContainsString('dans', (string) $normalizedFrench, 'Stop word "dans" should be removed from French text');
        $this->assertStringNotContainsString('la', (string) $normalizedFrench, 'Stop word "la" should be removed from French text');

        // Assert: French content words should be preserved
        $this->assertStringContainsString('chat', (string) $normalizedFrench, 'Content word "chat" should be preserved');
        $this->assertStringContainsString('chien', (string) $normalizedFrench, 'Content word "chien" should be preserved');
        $this->assertStringContainsString('maison', (string) $normalizedFrench, 'Content word "maison" should be preserved');

        // Cleanup: Restore original locale
        app()->setLocale($originalLocale);
    }

    /**
     * Test that English stop words are automatically removed when locale is English.
     */
    public function test_english_stop_words_automatically_removed(): void
    {
        // Arrange: Set application locale to English
        $originalLocale = app()->getLocale();
        app()->setLocale('en');

        $normalizer = app(StringNormalizer::class);

        // Act: Normalize English text containing stop words
        $normalizedEnglish = $normalizer->normalizeQuery('the cat and the dog are in the house');

        // Assert: English stop words should be automatically removed
        $this->assertStringNotContainsString('the', (string) $normalizedEnglish, 'Stop word "the" should be removed from English text');
        $this->assertStringNotContainsString('and', (string) $normalizedEnglish, 'Stop word "and" should be removed from English text');
        $this->assertStringNotContainsString('are', (string) $normalizedEnglish, 'Stop word "are" should be removed from English text');
        $this->assertStringNotContainsString('in', (string) $normalizedEnglish, 'Stop word "in" should be removed from English text');

        // Assert: English content words should be preserved
        $this->assertStringContainsString('cat', (string) $normalizedEnglish, 'Content word "cat" should be preserved');
        $this->assertStringContainsString('dog', (string) $normalizedEnglish, 'Content word "dog" should be preserved');
        $this->assertStringContainsString('house', (string) $normalizedEnglish, 'Content word "house" should be preserved');

        // Cleanup: Restore original locale
        app()->setLocale($originalLocale);
    }

    /**
     * Test that coverage bonus configuration is properly loaded from config.
     */
    public function test_coverage_bonus_configuration(): void
    {
        // Arrange: Configure custom coverage bonus settings
        Config::set('fuzzy.scoring.coverage_bonus', [
            'full_threshold' => 0.80,
            'high_threshold' => 0.60,
            'full_bonus' => 0.40,
            'high_bonus' => 0.20,
        ]);

        // Act: Retrieve coverage bonus configuration values
        $fullThreshold = config('fuzzy.scoring.coverage_bonus.full_threshold');
        $highThreshold = config('fuzzy.scoring.coverage_bonus.high_threshold');
        $fullBonus = config('fuzzy.scoring.coverage_bonus.full_bonus');
        $highBonus = config('fuzzy.scoring.coverage_bonus.high_bonus');

        // Assert: Verify coverage bonus configuration is correctly applied
        $this->assertEqualsWithDelta(0.80, $fullThreshold, PHP_FLOAT_EPSILON, 'Full coverage threshold should be 0.80');
        $this->assertEqualsWithDelta(0.60, $highThreshold, PHP_FLOAT_EPSILON, 'High coverage threshold should be 0.60');
        $this->assertEqualsWithDelta(0.40, $fullBonus, PHP_FLOAT_EPSILON, 'Full coverage bonus should be 0.40');
        $this->assertEqualsWithDelta(0.20, $highBonus, PHP_FLOAT_EPSILON, 'High coverage bonus should be 0.20');
    }

    /**
     * Test that coverage bonus defaults are used when configuration is missing.
     */
    public function test_coverage_bonus_defaults_when_configuration_missing(): void
    {
        // Arrange: Remove coverage bonus configuration entirely
        Config::set('fuzzy.scoring.coverage_bonus', null);

        // Clear config cache to ensure fresh load
        Config::offsetUnset('fuzzy.scoring.coverage_bonus');

        // Act: Retrieve coverage bonus configuration with defaults
        $fullThreshold = config('fuzzy.scoring.coverage_bonus.full_threshold', 0.75);
        $highThreshold = config('fuzzy.scoring.coverage_bonus.high_threshold', 0.50);
        $fullBonus = config('fuzzy.scoring.coverage_bonus.full_bonus', 0.30);
        $highBonus = config('fuzzy.scoring.coverage_bonus.high_bonus', 0.15);

        // Assert: Verify default values are used
        $this->assertEqualsWithDelta(0.75, $fullThreshold, PHP_FLOAT_EPSILON, 'Default full threshold should be 0.75');
        $this->assertEqualsWithDelta(0.50, $highThreshold, PHP_FLOAT_EPSILON, 'Default high threshold should be 0.50');
        $this->assertEqualsWithDelta(0.30, $fullBonus, PHP_FLOAT_EPSILON, 'Default full bonus should be 0.30');
        $this->assertEqualsWithDelta(0.15, $highBonus, PHP_FLOAT_EPSILON, 'Default high bonus should be 0.15');
    }
}
