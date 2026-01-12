<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Fuzzy\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionMethod;

/**
 * Test suite for AdvancedScoringCalculator.
 *
 * Verifies that advanced scoring calculations work correctly,
 * including field weighting, bonuses, penalties and boundary conditions.
 */
#[AllowMockObjectsWithoutExpectations]
final class AdvancedScoringCalculatorTest extends TestCase
{
    private AdvancedScoringCalculator $calculator;
    private SearchContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new AdvancedScoringCalculator();

        // Arrange: Create a search context for testing
        $normalizer = new StringNormalizer();
        $similarityCalculator = new SimilarityCalculator();
        $query = SearchQuery::create(query: 'test query', normalizer: $normalizer);
        $options = new SearchOptionsData();

        $this->context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: $similarityCalculator,
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );
    }

    public function test_calculate_final_score_basic(): void
    {
        // Arrange: Basic match data
        $match = [
            'field' => 'name',
            'original_value' => 'Test Product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        // Act: Calculate final score
        $score = $this->calculator->calculateFinalScore(
            baseScore: 0.8,
            match: $match,
            context: $this->context,
            queryWord: 'test'
        );

        // Assert: Score should be within valid range
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_final_score_with_field_weighting(): void
    {
        // Arrange: Configure field weights
        config(['fuzzy.scoring.field_weights' => [
            'name' => 1.3,
            'title' => 1.2,
            'default' => 0.6,
        ]]);

        $matchName = ['field' => 'name', 'normalized_words' => ['test'], 'weight' => 1.0];
        $matchTitle = ['field' => 'title', 'normalized_words' => ['test'], 'weight' => 1.0];
        $matchDefault = ['field' => 'unknown', 'normalized_words' => ['test'], 'weight' => 1.0];

        // Act: Calculate scores for different fields
        $scoreName = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $matchName,
            context: $this->context,
            queryWord: 'test'
        );

        $scoreTitle = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $matchTitle,
            context: $this->context,
            queryWord: 'test'
        );

        $scoreDefault = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $matchDefault,
            context: $this->context,
            queryWord: 'test'
        );

        // Assert: Name should have highest score due to highest weight
        $this->assertGreaterThan($scoreTitle, $scoreName);
        $this->assertGreaterThan($scoreDefault, $scoreName);
    }

    public function test_calculate_final_score_with_consecutive_bonus(): void
    {
        // Arrange: Match with word containing consecutive letters
        $match = [
            'field' => 'name',
            'original_value' => 'testing product',
            'normalized_words' => ['testing', 'product'],
            'weight' => 1.0,
        ];

        // Act: Calculate score for match with consecutive bonus
        $score = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $match,
            context: $this->context,
            queryWord: 'test' // 'test' is contained in 'testing' with consecutive letters
        );

        // Assert: Should have bonus for consecutive letters
        $this->assertGreaterThan(0.5, $score);
    }

    public function test_calculate_final_score_with_position_bonus(): void
    {
        // Arrange: Matches with word in different positions
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

        // Act: Calculate scores for different positions
        $scoreEarly = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $matchEarly,
            context: $this->context,
            queryWord: 'test'
        );

        $scoreLate = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $matchLate,
            context: $this->context,
            queryWord: 'test'
        );

        // Assert: Early position should have higher or equal score
        $this->assertGreaterThanOrEqual($scoreLate, $scoreEarly);
        $this->assertLessThanOrEqual(1.0, $scoreEarly);
        $this->assertLessThanOrEqual(1.0, $scoreLate);
    }

    public function test_calculate_final_score_with_short_query_penalty(): void
    {
        // Arrange: Create context with short query word
        $match = [
            'field' => 'name',
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'cat', normalizer: $normalizer); // Short word
        $context = new SearchContext(
            query: $query,
            options: new SearchOptionsData(),
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        // Act: Calculate score with short query word
        $score = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $match,
            context: $context,
            queryWord: 'cat'
        );

        // Assert: Should have penalty for short query
        $this->assertLessThan(0.5, $score);
    }

    public function test_calculate_final_score_without_short_query_penalty(): void
    {
        // Arrange: Create context with long query word
        $match = [
            'field' => 'name',
            'original_value' => 'testing product',
            'normalized_words' => ['testing', 'product'],
            'weight' => 1.0,
        ];

        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'testing', normalizer: $normalizer); // Long word
        $context = new SearchContext(
            query: $query,
            options: new SearchOptionsData(),
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        // Act: Calculate score with long query word
        $score = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $match,
            context: $context,
            queryWord: 'testing'
        );

        // Assert: Should NOT have penalty for long query
        $this->assertGreaterThan(0.5, $score);
    }

    public function test_calculate_final_score_with_multi_word_context(): void
    {
        // Arrange: Create context with multi-word query
        $match = [
            'field' => 'name',
            'original_value' => 'test query product',
            'normalized_words' => ['test', 'query', 'product'],
            'weight' => 1.0,
        ];

        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test query', normalizer: $normalizer); // Multi-word
        $context = new SearchContext(
            query: $query,
            options: new SearchOptionsData(),
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        // Act: Calculate score in multi-word context
        $score = $this->calculator->calculateFinalScore(
            baseScore: 0.5,
            match: $match,
            context: $context,
            queryWord: 'test'
        );

        // Assert: Score should be within valid range
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_final_score_clamping(): void
    {
        // Arrange: Match data for boundary testing
        $match = [
            'field' => 'name',
            'original_value' => 'test',
            'normalized_words' => ['test'],
            'weight' => 1.0,
        ];

        // Act: Test score clamping with extreme values
        $negativeScore = $this->calculator->calculateFinalScore(
            baseScore: -1.0,
            match: $match,
            context: $this->context,
            queryWord: 'test'
        );

        $highScore = $this->calculator->calculateFinalScore(
            baseScore: 2.0,
            match: $match,
            context: $this->context,
            queryWord: 'test'
        );

        // Assert: Scores should be clamped between 0 and 1
        $this->assertEqualsWithDelta(0.0, $negativeScore, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.0, $highScore, PHP_FLOAT_EPSILON);
    }

    public function test_find_longest_common_substring(): void
    {
        // Arrange: Make private method accessible
        $method = new ReflectionMethod($this->calculator, 'findLongestCommonSubstring');
        $method->setAccessible(true);

        // Act & Assert: Test various substring scenarios
        $this->assertEquals(5, $method->invoke($this->calculator, 'hello', 'hello'));
        $this->assertEquals(3, $method->invoke($this->calculator, 'hello', 'hel'));
        $this->assertEquals(0, $method->invoke($this->calculator, 'hello', 'world'));
        $this->assertEquals(0, $method->invoke($this->calculator, '', 'hello'));
    }
}
