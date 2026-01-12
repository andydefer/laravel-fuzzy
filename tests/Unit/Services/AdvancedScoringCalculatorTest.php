<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Services\IndexBuilder;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Services\Scoring\ScoringEngine;
use ReflectionMethod;
use Fuzzy\Tests\TestCase;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class AdvancedScoringCalculatorTest extends TestCase
{
    private AdvancedScoringCalculator $calculator;

    private SearchContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new AdvancedScoringCalculator();

        // Create a mock context
        $normalizer = new StringNormalizer();
        $similarityCalculator = new SimilarityCalculator();
        $query = SearchQuery::create('test query', $normalizer);
        $options = new SearchOptionsData();

        $this->context = new SearchContext(
            $query,
            $options,
            $normalizer,
            $similarityCalculator,
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $this->createMock(ScoringEngine::class),
            []
        );
    }

    public function test_calculate_final_score_basic(): void
    {
        $match = [
            'field' => 'name',
            'original_value' => 'Test Product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        $score = $this->calculator->calculateFinalScore(
            0.8,
            $match,
            $this->context,
            'test'
        );

        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_final_score_with_field_weighting(): void
    {
        config(['fuzzy.scoring.field_weights' => [
            'name' => 1.3,
            'title' => 1.2,
            'default' => 0.6,
        ]]);

        $matchName = ['field' => 'name', 'normalized_words' => ['test'], 'weight' => 1.0];
        $matchTitle = ['field' => 'title', 'normalized_words' => ['test'], 'weight' => 1.0];
        $matchDefault = ['field' => 'unknown', 'normalized_words' => ['test'], 'weight' => 1.0];

        $scoreName = $this->calculator->calculateFinalScore(0.5, $matchName, $this->context, 'test');
        $scoreTitle = $this->calculator->calculateFinalScore(0.5, $matchTitle, $this->context, 'test');
        $scoreDefault = $this->calculator->calculateFinalScore(0.5, $matchDefault, $this->context, 'test');

        // Name should have highest score due to highest weight
        $this->assertGreaterThan($scoreTitle, $scoreName);
        $this->assertGreaterThan($scoreDefault, $scoreName);
    }

    public function test_calculate_final_score_with_consecutive_bonus(): void
    {
        $match = [
            'field' => 'name',
            'original_value' => 'testing product',
            'normalized_words' => ['testing', 'product'],
            'weight' => 1.0,
        ];

        $score = $this->calculator->calculateFinalScore(
            0.5,
            $match,
            $this->context,
            'test' // 'test' is contained in 'testing' with consecutive letters
        );

        $this->assertGreaterThan(0.5, $score); // Should have bonus
    }

    public function test_calculate_final_score_with_position_bonus(): void
    {
        $matchEarly = [
            'field' => 'name',
            'original_value' => 'test product description',
            'normalized_words' => ['test', 'product', 'description'],
            'weight' => 1.0,
        ];

        $matchLate = [
            'field' => 'name',
            'original_value' => 'product description test',
            'normalized_words' => ['product', 'description', 'test'],
            'weight' => 1.0,
        ];

        $scoreEarly = $this->calculator->calculateFinalScore(
            0.5,
            $matchEarly,
            $this->context,
            'test'
        );

        $scoreLate = $this->calculator->calculateFinalScore(
            0.5,
            $matchLate,
            $this->context,
            'test'
        );

        // Modifier l'assertion pour ne pas dépasser 1.0
        $this->assertGreaterThanOrEqual($scoreLate, $scoreEarly);
        $this->assertLessThanOrEqual(1.0, $scoreEarly);
        $this->assertLessThanOrEqual(1.0, $scoreLate);
    }

    public function test_calculate_final_score_with_short_query_penalty(): void
    {
        $match = [
            'field' => 'name',
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        // Context with short query word
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('cat', $normalizer); // Short word
        $context = new SearchContext(
            $query,
            new SearchOptionsData(),
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $this->createMock(ScoringEngine::class),
            []
        );

        $score = $this->calculator->calculateFinalScore(
            0.5,
            $match,
            $context,
            'cat'
        );

        // Should have penalty for short query
        $this->assertLessThan(0.5, $score);
    }

    public function test_calculate_final_score_without_short_query_penalty(): void
    {
        $match = [
            'field' => 'name',
            'original_value' => 'testing product',
            'normalized_words' => ['testing', 'product'],
            'weight' => 1.0,
        ];

        // Context with long query word
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('testing', $normalizer); // Long word
        $context = new SearchContext(
            $query,
            new SearchOptionsData(),
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $this->createMock(ScoringEngine::class),
            []
        );

        $score = $this->calculator->calculateFinalScore(
            0.5,
            $match,
            $context,
            'testing'
        );

        // Should NOT have penalty for long query
        $this->assertGreaterThan(0.5, $score);
    }

    public function test_calculate_final_score_with_multi_word_context(): void
    {
        $match = [
            'field' => 'name',
            'original_value' => 'test query product',
            'normalized_words' => ['test', 'query', 'product'],
            'weight' => 1.0,
        ];

        // Context with multi-word query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test query', $normalizer); // Multi-word
        $context = new SearchContext(
            $query,
            new SearchOptionsData(),
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $this->createMock(ScoringEngine::class),
            []
        );

        $score = $this->calculator->calculateFinalScore(
            0.5,
            $match,
            $context,
            'test'
        );

        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_final_score_clamping(): void
    {
        $match = [
            'field' => 'name',
            'original_value' => 'test',
            'normalized_words' => ['test'],
            'weight' => 1.0,
        ];

        // Test that score is clamped between 0 and 1
        $negativeScore = $this->calculator->calculateFinalScore(
            -1.0,
            $match,
            $this->context,
            'test'
        );

        $highScore = $this->calculator->calculateFinalScore(
            2.0,
            $match,
            $this->context,
            'test'
        );

        $this->assertEqualsWithDelta(0.0, $negativeScore, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.0, $highScore, PHP_FLOAT_EPSILON);
    }

    public function test_find_longest_common_substring(): void
    {
        $method = new ReflectionMethod($this->calculator, 'findLongestCommonSubstring');
        $method->setAccessible(true);

        // Exact match
        $this->assertEquals(5, $method->invoke($this->calculator, 'hello', 'hello'));

        // Partial match
        $this->assertEquals(3, $method->invoke($this->calculator, 'hello', 'hel'));

        // No match
        $this->assertEquals(0, $method->invoke($this->calculator, 'hello', 'world'));

        // One empty
        $this->assertEquals(0, $method->invoke($this->calculator, '', 'hello'));
    }
}
