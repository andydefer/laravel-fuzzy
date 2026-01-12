<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\SortAndLimitStage;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class SortAndLimitStageTest extends TestCase
{
    private SortAndLimitStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new SortAndLimitStage();
    }

    public function test_handle_with_no_results(): void
    {
        // Arrange: Create a search context with empty results
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $context->results = [];

        // Act: Execute the stage with empty results
        $result = $this->stage->handle(context: $context);

        // Assert: Should return empty array without modifying context
        $this->assertSame([], $result);
        $this->assertSame([], $context->results);
    }

    public function test_handle_sorts_by_score_descending(): void
    {
        // Arrange: Create results with different scores in random order
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $lowScoreResult = $this->createMock(SearchResultData::class);
        $lowScoreResult->score = 0.3;

        $highScoreResult = $this->createMock(SearchResultData::class);
        $highScoreResult->score = 0.8;

        $mediumScoreResult = $this->createMock(SearchResultData::class);
        $mediumScoreResult->score = 0.5;

        $context->results = [
            'item1' => $lowScoreResult,
            'item2' => $highScoreResult,
            'item3' => $mediumScoreResult,
        ];

        // Act: Execute the sorting stage
        $this->stage->handle(context: $context);

        // Assert: Results should be sorted by score in descending order
        $results = $context->results;
        $this->assertCount(3, $results);

        $actualScores = array_map(
            fn(SearchResultData&MockObject $result): float => $result->score,
            $results
        );
        $this->assertSame([0.8, 0.5, 0.3], $actualScores);
    }

    public function test_handle_filters_by_min_score(): void
    {
        // Arrange: Create results with scores below and above minimum threshold
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.5);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $belowMinResult1 = $this->createMock(SearchResultData::class);
        $belowMinResult1->score = 0.3;

        $aboveMinResult = $this->createMock(SearchResultData::class);
        $aboveMinResult->score = 0.8;

        $belowMinResult2 = $this->createMock(SearchResultData::class);
        $belowMinResult2->score = 0.4;

        $context->results = [
            'item1' => $belowMinResult1,
            'item2' => $aboveMinResult,
            'item3' => $belowMinResult2,
        ];

        // Act: Execute the filtering stage
        $this->stage->handle(context: $context);

        // Assert: Should only keep results with score equal or above minimum threshold
        $results = $context->results;
        $this->assertCount(1, $results);
        $this->assertEqualsWithDelta(0.8, $results[0]->score, PHP_FLOAT_EPSILON);
    }

    public function test_handle_limits_results(): void
    {
        // Arrange: Create more results than the maximum allowed
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(maxResults: 2);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $context->results = [];
        for ($i = 1; $i <= 5; ++$i) {
            $result = $this->createMock(SearchResultData::class);
            $result->score = $i * 0.2;
            $context->results['item' . $i] = $result;
        }

        // Act: Execute the limiting stage
        $this->stage->handle(context: $context);

        // Assert: Should keep only the highest scoring results up to the limit
        $results = $context->results;
        $this->assertCount(2, $results);

        $actualScores = array_map(fn($result): float => $result->score, $results);
        $this->assertSame([1.0, 0.8], $actualScores);
    }

    public function test_handle_combines_filtering_sorting_and_limiting(): void
    {
        // Arrange: Create results that will be filtered, sorted and limited
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(
            minScore: 0.4,
            maxResults: 3
        );

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $scores = [0.2, 0.9, 0.3, 0.7, 0.5, 0.8, 0.4, 0.6];
        $context->results = [];

        foreach ($scores as $index => $score) {
            $result = $this->createMock(SearchResultData::class);
            $result->score = $score;
            $context->results['item' . $index] = $result;
        }

        // Act: Execute the complete processing stage
        $this->stage->handle(context: $context);

        // Assert: Should filter low scores, sort descending, and apply limit
        $results = $context->results;
        $this->assertCount(3, $results);

        $actualScores = array_map(fn($result): float => $result->score, $results);
        $this->assertSame([0.9, 0.8, 0.7], $actualScores);
    }

    public function test_handle_with_null_results(): void
    {
        // Arrange: Create results including null values
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $validResult = $this->createMock(SearchResultData::class);
        $validResult->score = 0.8;

        $context->results = [
            'item1' => $validResult,
            'item2' => null,
            'item3' => $validResult,
        ];

        // Act: Execute the stage with null results
        $this->stage->handle(context: $context);

        // Assert: Should filter out null results while keeping valid ones
        $results = $context->results;
        $this->assertCount(2, $results);
        $this->assertNotContains(null, $results);
    }

    public function test_handle_returns_results(): void
    {
        // Arrange: Create context with single result
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $result = $this->createMock(SearchResultData::class);
        $result->score = 0.8;

        $context->results = ['item1' => $result];

        // Act: Execute the stage and capture return value
        $returnedResults = $this->stage->handle(context: $context);

        // Assert: Should return the processed results array
        $this->assertIsArray($returnedResults);
        $this->assertCount(1, $returnedResults);
        $this->assertSame($context->results, $returnedResults);
    }

    public function test_handle_values_reset_indexes(): void
    {
        // Arrange: Create results with string keys
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(maxResults: 2);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $context->results = [];
        for ($i = 5; $i >= 1; --$i) {
            $result = $this->createMock(SearchResultData::class);
            $result->score = $i * 0.2;
            $context->results['key' . $i] = $result;
        }

        // Act: Execute the stage which should reset array keys
        $this->stage->handle(context: $context);

        // Assert: Should have sequential numeric indexes after processing
        $results = $context->results;
        $this->assertArrayHasKey(0, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertArrayNotHasKey(2, $results);
        $this->assertArrayNotHasKey('key5', $results);
    }
}
