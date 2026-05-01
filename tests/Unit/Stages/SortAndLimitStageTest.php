<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Contracts\SearchContextInterface;
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

#[AllowMockObjectsWithoutExpectations]
final class SortAndLimitStageTest extends TestCase
{
    private SortAndLimitStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new SortAndLimitStage();
    }

    /**
     * Create a next callback for pipeline testing.
     * The callback should return the modified context results.
     */
    private function createNextCallback(): callable
    {
        return function ($passedContext) {
            return $passedContext->results;
        };
    }

    /**
     * Create a search context for testing.
     */
    private function createSearchContext(
        string $queryString,
        array $results,
        float $minScore = 0.0,
        int $maxResults = 20
    ): SearchContext {
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create($queryString, $normalizer);
        $options = new SearchOptionsData(
            minScore: $minScore,
            maxResults: $maxResults
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

        $context->results = $results;

        return $context;
    }

    /**
     * Create a mock search result with a specific score.
     */
    private function createMockResult(float $score): SearchResultData
    {
        $result = $this->createMock(SearchResultData::class);
        $result->score = $score;
        return $result;
    }

    public function test_handle_with_no_results(): void
    {
        $context = $this->createSearchContext('test', []);
        $next = $this->createNextCallback();

        $result = $this->stage->handle($context, $next);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertEmpty($context->results);
    }

    public function test_handle_sorts_by_score_descending(): void
    {
        $results = [
            $this->createMockResult(0.3),
            $this->createMockResult(0.8),
            $this->createMockResult(0.5),
        ];

        $context = $this->createSearchContext('test', $results, 0.1);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(3, $processedResults);

        $actualScores = array_map(fn($result): float => $result->score, $processedResults);
        $this->assertSame([0.8, 0.5, 0.3], $actualScores);
    }

    public function test_handle_filters_by_min_score(): void
    {
        $results = [
            $this->createMockResult(0.3),
            $this->createMockResult(0.8),
            $this->createMockResult(0.4),
        ];

        $context = $this->createSearchContext('test', $results, 0.5);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(1, $processedResults);
        $this->assertEqualsWithDelta(0.8, $processedResults[0]->score, PHP_FLOAT_EPSILON);
    }

    public function test_handle_filters_by_min_score_with_exact_threshold(): void
    {
        $results = [
            $this->createMockResult(0.3),
            $this->createMockResult(0.5),
            $this->createMockResult(0.7),
        ];

        $context = $this->createSearchContext('test', $results, 0.5);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(2, $processedResults);
        $actualScores = array_map(fn($result): float => $result->score, $processedResults);
        $this->assertContains(0.5, $actualScores);
        $this->assertContains(0.7, $actualScores);
    }

    public function test_handle_limits_results(): void
    {
        $results = [];
        for ($i = 1; $i <= 5; ++$i) {
            $results[] = $this->createMockResult($i * 0.2);
        }

        $context = $this->createSearchContext('test', $results, 0.0, 2);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(2, $processedResults);

        $actualScores = array_map(fn($result): float => $result->score, $processedResults);
        $this->assertSame([1.0, 0.8], $actualScores);
    }

    public function test_handle_combines_filtering_sorting_and_limiting(): void
    {
        $scores = [0.2, 0.9, 0.3, 0.7, 0.5, 0.8, 0.4, 0.6];
        $results = [];

        foreach ($scores as $score) {
            $results[] = $this->createMockResult($score);
        }

        $context = $this->createSearchContext('test', $results, 0.4, 3);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(3, $processedResults);

        $actualScores = array_map(fn($result): float => $result->score, $processedResults);
        $this->assertSame([0.9, 0.8, 0.7], $actualScores);
    }

    public function test_handle_with_null_results(): void
    {
        $validResult1 = $this->createMockResult(0.8);
        $validResult2 = $this->createMockResult(0.6);

        $results = [
            $validResult1,
            null,
            $validResult2,
            null,
        ];

        $context = $this->createSearchContext('test', $results);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(2, $processedResults);
        $this->assertNotContains(null, $processedResults);
    }

    public function test_handle_filters_null_before_sorting(): void
    {
        $results = [
            null,
            $this->createMockResult(0.9),
            null,
            $this->createMockResult(0.5),
            $this->createMockResult(0.7),
        ];

        $context = $this->createSearchContext('test', $results);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(3, $processedResults);
        $actualScores = array_map(fn($result): float => $result->score, $processedResults);
        $this->assertSame([0.9, 0.7, 0.5], $actualScores);
    }

    public function test_handle_returns_results(): void
    {
        $result = $this->createMockResult(0.8);
        $results = [$result];

        $context = $this->createSearchContext('test', $results);
        $next = $this->createNextCallback();

        $returnedResults = $this->stage->handle($context, $next);

        $this->assertIsArray($returnedResults);
        $this->assertCount(1, $returnedResults);
        $this->assertSame($context->results, $returnedResults);
    }

    public function test_handle_values_reset_indexes(): void
    {
        $results = [];
        for ($i = 5; $i >= 1; --$i) {
            $results['key' . $i] = $this->createMockResult($i * 0.2);
        }

        $context = $this->createSearchContext('test', $results, 0.0, 2);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertArrayHasKey(0, $processedResults);
        $this->assertArrayHasKey(1, $processedResults);
        $this->assertArrayNotHasKey(2, $processedResults);
        $this->assertArrayNotHasKey('key5', $processedResults);
    }

    public function test_handle_maintains_scores_in_original_objects(): void
    {
        $result1 = $this->createMock(SearchResultData::class);
        $result1->score = 0.7;
        $result1->matchedField = 'name';
        $result1->matchedValue = 'John Doe';

        $result2 = $this->createMock(SearchResultData::class);
        $result2->score = 0.9;
        $result2->matchedField = 'email';
        $result2->matchedValue = 'john@example.com';

        $results = [$result1, $result2];

        $context = $this->createSearchContext('test', $results);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(2, $processedResults);

        // Should be sorted by score (highest first)
        $this->assertEquals('email', $processedResults[0]->matchedField);
        $this->assertEquals('john@example.com', $processedResults[0]->matchedValue);
        $this->assertEquals('name', $processedResults[1]->matchedField);
        $this->assertEquals('John Doe', $processedResults[1]->matchedValue);
    }

    public function test_handle_with_default_max_results(): void
    {
        $results = [];
        for ($i = 1; $i <= 30; ++$i) {
            $results[] = $this->createMockResult($i);
        }

        $context = $this->createSearchContext('test', $results, 0.0, 20);
        $next = $this->createNextCallback();

        $this->stage->handle($context, $next);

        $processedResults = $context->results;
        $this->assertCount(20, $processedResults);
    }
}
