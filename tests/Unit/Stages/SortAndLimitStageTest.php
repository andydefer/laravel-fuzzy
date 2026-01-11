<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\SortAndLimitStage;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class SortAndLimitStageTest extends TestCase
{
    private SortAndLimitStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new SortAndLimitStage();
    }

    public function test_handle_with_no_results(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $context->results = [];

        // Act
        $result = $this->stage->handle($context, fn() => 'next');

        // Assert: Should return empty array
        $this->assertSame([], $result);
        $this->assertSame([], $context->results);
    }

    public function test_handle_sorts_by_score_descending(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $result1 = $this->createMock(SearchResultData::class);
        $result1->score = 0.3;

        $result2 = $this->createMock(SearchResultData::class);
        $result2->score = 0.8;

        $result3 = $this->createMock(SearchResultData::class);
        $result3->score = 0.5;

        $context->results = [
            'item1' => $result1,
            'item2' => $result2,
            'item3' => $result3,
        ];

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should be sorted by score descending
        $results = $context->results;
        $this->assertCount(3, $results);

        $scores = array_map(fn($r) => $r->score, $results);
        $this->assertEquals([0.8, 0.5, 0.3], $scores);
    }

    public function test_handle_filters_by_min_score(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.5); // High min score

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $result1 = $this->createMock(SearchResultData::class);
        $result1->score = 0.3; // Below min

        $result2 = $this->createMock(SearchResultData::class);
        $result2->score = 0.8; // Above min

        $result3 = $this->createMock(SearchResultData::class);
        $result3->score = 0.4; // Below min

        $context->results = [
            'item1' => $result1,
            'item2' => $result2,
            'item3' => $result3,
        ];

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should only keep result with score >= 0.5
        $results = $context->results;
        $this->assertCount(1, $results);
        $this->assertEquals(0.8, $results[0]->score);
    }

    public function test_handle_limits_results(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(maxResults: 2); // Limit to 2 results

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        // Create 5 results
        $context->results = [];
        for ($i = 1; $i <= 5; $i++) {
            $result = $this->createMock(SearchResultData::class);
            $result->score = $i * 0.2; // Scores: 0.2, 0.4, 0.6, 0.8, 1.0
            $context->results["item$i"] = $result;
        }

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should limit to 2 highest-scoring results
        $results = $context->results;
        $this->assertCount(2, $results);

        $scores = array_map(fn($r) => $r->score, $results);
        $this->assertEquals([1.0, 0.8], $scores); // Highest scores
    }

    public function test_handle_combines_filtering_sorting_and_limiting(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(
            minScore: 0.4,
            maxResults: 3
        );

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        // Create results with various scores
        $scores = [0.2, 0.9, 0.3, 0.7, 0.5, 0.8, 0.4, 0.6];
        $context->results = [];

        foreach ($scores as $index => $score) {
            $result = $this->createMock(SearchResultData::class);
            $result->score = $score;
            $context->results["item$index"] = $result;
        }

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should:
        // 1. Filter out scores < 0.4 (removes 0.2, 0.3)
        // 2. Sort descending
        // 3. Limit to 3 results
        $results = $context->results;
        $this->assertCount(3, $results);

        $resultScores = array_map(fn($r) => $r->score, $results);
        $this->assertEquals([0.9, 0.8, 0.7], $resultScores);
    }

    public function test_handle_with_null_results(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $result1 = $this->createMock(SearchResultData::class);
        $result1->score = 0.8;

        $context->results = [
            'item1' => $result1,
            'item2' => null, // Null result
            'item3' => $result1,
        ];

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should filter out null results
        $results = $context->results;
        $this->assertCount(2, $results);
        $this->assertNotContains(null, $results);
    }

    public function test_handle_returns_results(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $result = $this->createMock(SearchResultData::class);
        $result->score = 0.8;
        $context->results = ['item1' => $result];

        // Act
        $result = $this->stage->handle($context, fn() => 'next');

        // Assert: Should return the sorted results
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($context->results, $result);
    }

    public function test_handle_values_reset_indexes(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(maxResults: 2);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        // Create results with string keys
        $context->results = [];
        for ($i = 5; $i >= 1; $i--) {
            $result = $this->createMock(SearchResultData::class);
            $result->score = $i * 0.2;
            $context->results["key$i"] = $result;
        }

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should have sequential numeric indexes after values()
        $results = $context->results;
        $this->assertArrayHasKey(0, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertArrayNotHasKey(2, $results); // Limited to 2
        $this->assertArrayNotHasKey('key5', $results); // Original keys gone
    }
}
