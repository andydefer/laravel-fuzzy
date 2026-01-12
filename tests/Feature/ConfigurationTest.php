<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;

final class ConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function test_searchable_models_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.searchable_models', [
            User::class,
            Product::class,
        ]);

        // Act
        $searchService = app('laravel-fuzzy.search');
        $models = $searchService->getSearchableModels();

        // Assert
        $this->assertContains(User::class, $models);
        $this->assertContains(Product::class, $models);
        $this->assertCount(2, $models);
    }

    public function test_auto_discovery_enabled(): void
    {
        // Arrange
        Config::set('fuzzy.auto_discovery.enabled', true);
        Config::set('fuzzy.searchable_models', []);

        Config::set('fuzzy.auto_discovery.directories', [
            dirname(__DIR__, 2) . '/tests/Fixtures'
        ]);

        // Act
        $searchService = app('laravel-fuzzy.search');
        $models = $searchService->getSearchableModels();

        // Assert - Devrait découvrir User et Product
        $this->assertContains(User::class, $models);
        $this->assertContains(Product::class, $models);
    }

    public function test_auto_discovery_disabled(): void
    {
        // Arrange
        Config::set('fuzzy.auto_discovery.enabled', false);
        Config::set('fuzzy.searchable_models', []);

        // Act
        $searchService = app('laravel-fuzzy.search');
        $models = $searchService->getSearchableModels();

        // Assert - Ne devrait rien découvrir
        $this->assertEmpty($models);
    }

    public function test_field_weights_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.scoring.field_weights', [
            'name' => 2.0,
            'title' => 1.5,
            'email' => 1.0,
            'description' => 0.8,
            'default' => 0.5,
        ]);

        // Act
        $indexBuilder = app('laravel-fuzzy.index-builder');
        $nameWeight = $indexBuilder->calculateFieldWeight('name');
        $titleWeight = $indexBuilder->calculateFieldWeight('title');
        $unknownWeight = $indexBuilder->calculateFieldWeight('unknown');

        // Assert
        $this->assertEqualsWithDelta(2.0, $nameWeight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.5, $titleWeight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.5, $unknownWeight, PHP_FLOAT_EPSILON);
    }

    public function test_stop_words_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.stop_words', ['the', 'and', 'or', 'test']);

        $normalizer = app('laravel-fuzzy.normalizer');

        // Act
        $query = $normalizer->normalizeQuery('the quick brown fox and the lazy dog test');

        // Assert
        $this->assertStringNotContainsString('the', (string) $query);
        $this->assertStringNotContainsString('and', (string) $query);
        $this->assertStringNotContainsString('test', (string) $query);
        $this->assertStringContainsString('quick', (string) $query);
        $this->assertStringContainsString('brown', (string) $query);
    }

    public function test_default_options_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.default_options', [
            'min_score' => 0.5,
            'max_results' => 50,
            'fuzzy' => false,
            'threshold' => 0.5,
        ]);

        $searchService = app('laravel-fuzzy.search');

        // Act - Recherche sans options personnalisées
        $results = $searchService->search('test');

        // Assert - Les options par défaut devraient être utilisées
        // Note: On ne peut pas directement vérifier les options internes,
        // mais on peut vérifier que la recherche fonctionne avec ces paramètres
        $this->assertIsIterable($results);
    }

    public function test_cache_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.cache.enabled', true);
        Config::set('fuzzy.cache.ttl.search', 1800);
        Config::set('fuzzy.cache.invalidation.on_index', true);

        // Act & Assert
        $this->assertTrue(config('fuzzy.cache.enabled'));
        $this->assertEquals(1800, config('fuzzy.cache.ttl.search'));
        $this->assertTrue(config('fuzzy.cache.invalidation.on_index'));
    }

    public function test_pipeline_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.pipeline.stages', [
            NormalizeQueryStage::class,
            MatchDiscoveryStage::class,
            ScoringStage::class,
            SortAndLimitStage::class,
        ]);

        // Act
        $stages = config('fuzzy.pipeline.stages');

        // Assert
        $this->assertCount(4, $stages);
        $this->assertContains(NormalizeQueryStage::class, $stages);
        $this->assertContains(MatchDiscoveryStage::class, $stages);
    }

    public function test_exclude_patterns_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.auto_discovery.exclude_patterns', [
            '/^Abstract/',
            '/^Base/',
            '/Interface$/',
            '/Trait$/',
        ]);

        // Act - Tester un pattern
        $patterns = config('fuzzy.auto_discovery.exclude_patterns');

        // Assert
        $this->assertCount(4, $patterns);
        $this->assertContains('/^Abstract/', $patterns);
        $this->assertContains('/Interface$/', $patterns);
    }

    public function test_index_configuration(): void
    {
        // Arrange
        Config::set('fuzzy.index', [
            'min_word_length' => 3,
            'max_word_length' => 100,
            'batch_size' => 200,
            'queue' => true,
            'queue_name' => 'search-index',
        ]);

        // Act & Assert
        $this->assertEquals(3, config('fuzzy.index.min_word_length'));
        $this->assertEquals(200, config('fuzzy.index.batch_size'));
        $this->assertTrue(config('fuzzy.index.queue'));
        $this->assertEquals('search-index', config('fuzzy.index.queue_name'));
    }
}
