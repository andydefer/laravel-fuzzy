<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\SearchContext;
use Fuzzy\Services\Algorithms\WordSimilarityComparator;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Stages\RelevanceScoringStage;
use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\SearchQuery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionMethod;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class RelevanceScoringStageTest extends TestCase
{
    private RelevanceScoringStage $stage;
    private WordSimilarityComparator $comparator;
    private StringNormalizer $normalizer;

    /**
     * Set up test dependencies.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new StringNormalizer();
        $this->comparator = new WordSimilarityComparator(
            normalizer: $this->normalizer
        );

        $this->stage = new RelevanceScoringStage($this->comparator);
    }

    /**
     * Test handling empty results array.
     */
    public function test_handle_with_empty_results(): void
    {
        // Arrange: Context with empty results
        $context = $this->createSearchContext('test query', []);

        // Act: Process empty results
        $result = $this->stage->handle($context);

        // Assert: Should return empty array
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test adding relevance scores to search results.
     */
    public function test_handle_adds_relevance_scores_to_results(): void
    {
        // Arrange: Create search results with matched values
        $results = [
            $this->createSearchResult('John Doe', 'John Doe'),
            $this->createSearchResult('Jane Smith', 'Jane Smith'),
        ];

        $context = $this->createSearchContext('John Doe', $results);
        $next = $this->createNextCallback($context);

        // Act: Process results through scoring stage
        $processedResults = $this->stage->handle($context, $next);

        // Assert: All results should have calculated relevance scores
        $this->assertIsArray($processedResults);
        $this->assertCount(2, $processedResults);

        foreach ($processedResults as $resultItem) {
            $this->assertObjectHasProperty('relevance', $resultItem);
            $this->assertIsFloat($resultItem->relevance);
            $this->assertGreaterThanOrEqual(0.0, $resultItem->relevance);
        }
    }

    /**
     * Test sorting results by ascending relevance score.
     */
    public function test_handle_sorts_by_relevance_ascending(): void
    {
        // Arrange: Results with varying similarity to query
        $results = [
            $this->createSearchResult('John Doe', 'John Doe'),
            $this->createSearchResult('Jane Smith', 'Jane Smith'),
            $this->createSearchResult('John Smith', 'John Smith'),
        ];

        $context = $this->createSearchContext('John Doe', $results);
        $next = $this->createNextCallback($context);

        // Act: Process and sort results by relevance
        $processedResults = $this->stage->handle($context, $next);

        // Assert: Results sorted by relevance (lower score = more relevant)
        $this->assertIsArray($processedResults);
        $this->assertCount(3, $processedResults);

        // First result should be exact match with zero relevance
        $this->assertEquals('John Doe', $processedResults[0]->matchedValue);
        $this->assertEqualsWithDelta(0.0, $processedResults[0]->relevance, 0.01);

        // Second result should be partial match with low relevance
        $this->assertEquals('John Smith', $processedResults[1]->matchedValue);
        $this->assertGreaterThan(0.0, $processedResults[1]->relevance);

        // Third result should be different with higher relevance
        $this->assertEquals('Jane Smith', $processedResults[2]->matchedValue);
        $this->assertGreaterThan($processedResults[1]->relevance, $processedResults[2]->relevance);
    }

    /**
     * Test applying maximum results limit.
     */
    public function test_handle_applies_max_results_limit(): void
    {
        // Arrange: More results than specified limit
        $results = [
            $this->createSearchResult('Result 1', 'Result 1'),
            $this->createSearchResult('Result 2', 'Result 2'),
            $this->createSearchResult('Result 3', 'Result 3'),
            $this->createSearchResult('Result 4', 'Result 4'),
            $this->createSearchResult('Result 5', 'Result 5'),
        ];

        $context = $this->createSearchContext('test', $results, maxResults: 3);
        $next = $this->createNextCallback($context);

        // Act: Process with results limit
        $processedResults = $this->stage->handle($context, $next);

        // Assert: Results limited to specified maximum
        $this->assertIsArray($processedResults);
        $this->assertCount(3, $processedResults);
    }

    /**
     * Test using default maximum results when not explicitly set.
     */
    public function test_handle_uses_default_max_results_when_not_set(): void
    {
        // Arrange: Many results without explicit maxResults
        $results = [];
        for ($i = 0; $i < 30; $i++) {
            $results[] = $this->createSearchResult('Test ' . $i, 'Test ' . $i);
        }

        $context = $this->createSearchContext('test', $results);
        $context->options = new SearchOptionsData(); // Uses default maxResults
        $next = $this->createNextCallback($context);

        // Act: Process without explicit limit
        $processedResults = $this->stage->handle($context, $next);

        // Assert: Should use default limit of 20 results
        $this->assertIsArray($processedResults);
        $this->assertCount(20, $processedResults);
    }

    /**
     * Test calculating relevance for result with missing data.
     */
    public function test_calculate_relevance_for_result_with_missing_data(): void
    {
        // Arrange: Result with missing matchedValue
        $result = $this->createSearchResult(null, null);
        $context = $this->createSearchContext('test', [$result]);

        // Act: Calculate relevance using private method
        $relevance = $this->invokePrivateMethod(
            $this->stage,
            'calculateRelevanceForResult',
            [$result, $context]
        );

        // Assert: Should return high penalty for missing data
        $this->assertEquals(10.0, $relevance);
    }

    /**
     * Test calculating relevance for result with empty query.
     */
    public function test_calculate_relevance_for_result_with_empty_query(): void
    {
        // Arrange: Empty query context
        $result = $this->createSearchResult('John Doe', 'John Doe');
        $context = $this->createSearchContext('', [$result]);

        // Act: Calculate relevance using private method
        $relevance = $this->invokePrivateMethod(
            $this->stage,
            'calculateRelevanceForResult',
            [$result, $context]
        );

        // Assert: Should return high penalty for empty query
        $this->assertEquals(10.0, $relevance);
    }

    /**
     * Test that relevance scores accurately reflect similarity levels.
     */
    public function test_relevance_scores_reflect_similarity(): void
    {
        // Arrange: Results with varying similarity levels
        $results = [
            $this->createSearchResult('exact match', 'exact match'),
            $this->createSearchResult('exct match', 'exct match'),
            $this->createSearchResult('different text', 'different text'),
        ];

        $context = $this->createSearchContext('exact match', $results);
        $next = $this->createNextCallback($context);

        // Act: Calculate relevance scores
        $processedResults = $this->stage->handle($context, $next);

        // Assert: Scores reflect similarity hierarchy
        $this->assertIsArray($processedResults);
        $this->assertCount(3, $processedResults);

        // Exact match should have zero relevance
        $this->assertEquals('exact match', $processedResults[0]->matchedValue);
        $this->assertEqualsWithDelta(0.0, $processedResults[0]->relevance, 0.01);

        // Typo should have low relevance
        $this->assertEquals('exct match', $processedResults[1]->matchedValue);
        $this->assertGreaterThan(0.0, $processedResults[1]->relevance);
        $this->assertLessThan(1.5, $processedResults[1]->relevance);

        // Different text should have higher relevance
        $this->assertEquals('different text', $processedResults[2]->matchedValue);
        $this->assertGreaterThan(1.0, $processedResults[2]->relevance);
    }

    /**
     * Test that original result properties are preserved.
     */
    public function test_handle_preserves_original_result_properties(): void
    {
        // Arrange: Result with comprehensive properties
        $originalItem = (object) [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = SearchResultData::create(
            item: $originalItem,
            score: 85.5,
            modelType: 'User',
            matchedField: 'name',
            matchedValue: 'John Doe'
        );

        $context = $this->createSearchContext('John Doe', [$result]);
        $next = $this->createNextCallback($context);

        // Act: Process result through scoring stage
        $processedResults = $this->stage->handle($context, $next);

        // Assert: Original properties preserved, relevance added
        $this->assertIsArray($processedResults);
        $this->assertCount(1, $processedResults);

        $resultItem = $processedResults[0];
        $this->assertEquals(85.5, $resultItem->score);
        $this->assertEquals('User', $resultItem->modelType);
        $this->assertEquals('name', $resultItem->matchedField);
        $this->assertEquals('John Doe', $resultItem->matchedValue);
        $this->assertObjectHasProperty('relevance', $resultItem);
        $this->assertSame($originalItem, $resultItem->item);
    }

    /**
     * Test handling duplicate matched values.
     */
    public function test_handle_with_duplicate_matches(): void
    {
        // Arrange: Multiple results with identical matched values
        $results = [
            $this->createSearchResult('John Doe', 'John Doe'),
            $this->createSearchResult('John Doe', 'John Doe'),
            $this->createSearchResult('Jane Smith', 'Jane Smith'),
        ];

        $context = $this->createSearchContext('John Doe', $results);
        $next = $this->createNextCallback($context);

        // Act: Process duplicate matches
        $processedResults = $this->stage->handle($context, $next);

        // Assert: All results processed, duplicates maintain same relevance
        $this->assertIsArray($processedResults);
        $this->assertCount(3, $processedResults);

        // Duplicate matches should have identical relevance
        $this->assertEquals('John Doe', $processedResults[0]->matchedValue);
        $this->assertEquals('John Doe', $processedResults[1]->matchedValue);
        $this->assertEqualsWithDelta(0.0, $processedResults[0]->relevance, 0.01);
        $this->assertEqualsWithDelta(0.0, $processedResults[1]->relevance, 0.01);

        // Different match should have non-zero relevance
        $this->assertEquals('Jane Smith', $processedResults[2]->matchedValue);
        $this->assertGreaterThan(0.0, $processedResults[2]->relevance);
    }

    /**
     * Test case-insensitive matching behavior.
     */
    public function test_handle_with_case_variations(): void
    {
        // Arrange: Results with case variations
        $results = [
            $this->createSearchResult('john doe', 'john doe'),
            $this->createSearchResult('JOHN DOE', 'JOHN DOE'),
            $this->createSearchResult('John Doe', 'John Doe'),
        ];

        $context = $this->createSearchContext('JOHN DOE', $results);
        $next = $this->createNextCallback($context);

        // Act: Process case variations
        $processedResults = $this->stage->handle($context, $next);

        // Assert: All case variations should match exactly
        $this->assertIsArray($processedResults);
        $this->assertCount(3, $processedResults);

        foreach ($processedResults as $resultItem) {
            $this->assertEqualsWithDelta(0.0, $resultItem->relevance, 0.01);
        }
    }

    /**
     * Test handling special characters in search queries.
     */
    public function test_handle_with_special_characters(): void
    {
        // Arrange: Query and matches with special characters
        $results = [
            $this->createSearchResult('Jean-Pierre', 'Jean-Pierre'),
            $this->createSearchResult('Jean Pierre', 'Jean Pierre'),
        ];

        $context = $this->createSearchContext('Jean-Pierre', $results);
        $next = $this->createNextCallback($context);

        // Act: Process special characters
        $processedResults = $this->stage->handle($context, $next);

        // Assert: Exact match prioritized over hyphenated variations
        $this->assertIsArray($processedResults);
        $this->assertCount(2, $processedResults);

        $this->assertEquals('Jean-Pierre', $processedResults[0]->matchedValue);
        $this->assertEqualsWithDelta(0.0, $processedResults[0]->relevance, 0.01);

        $this->assertEquals('Jean Pierre', $processedResults[1]->matchedValue);
        $this->assertGreaterThan(0.0, $processedResults[1]->relevance);
        $this->assertLessThan(1, $processedResults[1]->relevance);
    }

    /**
     * Test relevance score normalization method.
     */
    public function test_normalize_relevance_method(): void
    {
        // Arrange: Test cases for relevance normalization
        $testCases = [
            [0.0, 100.0],
            [0.5, 95.0],
            [1.0, 90.0],
            [5.0, 50.0],
            [10.0, 0.0],
            [15.0, 0.0],
            [-1.0, 100.0],
        ];

        foreach ($testCases as [$input, $expected]) {
            // Act: Normalize relevance score
            $result = $this->invokePrivateMethod(
                $this->stage,
                'normalizeRelevance',
                [$input]
            );

            // Assert: Normalization produces expected percentage
            $this->assertEqualsWithDelta(
                $expected,
                $result,
                0.01,
                "Failed for input: $input. Got: $result, Expected: $expected"
            );
        }
    }

    /**
     * Test combining original scores with relevance scores.
     */
    public function test_combine_scores_method(): void
    {
        // Arrange: Results with original scores and relevance
        $results = collect([
            (object) ['score' => 80.0, 'relevance' => 0.5],
            (object) ['score' => 90.0, 'relevance' => 2.0],
            (object) ['score' => 70.0, 'relevance' => 0.1],
        ]);

        // Act: Combine scores using private method
        $combinedResults = $this->invokePrivateMethod(
            $this->stage,
            'combineScores',
            [$results]
        );

        // Assert: Results have combined scores and are sorted
        $this->assertCount(3, $combinedResults);

        foreach ($combinedResults as $result) {
            $this->assertObjectHasProperty('combinedScore', $result);
            $this->assertObjectHasProperty('originalScore', $result);
            $this->assertObjectHasProperty('relevanceScore', $result);
            $this->assertGreaterThanOrEqual(0.0, $result->combinedScore);
            $this->assertLessThanOrEqual(100.0, $result->combinedScore);
        }

        // Verify descending order by combined score
        $sorted = $combinedResults->values()->all();
        for ($i = 0; $i < count($sorted) - 1; $i++) {
            $this->assertGreaterThanOrEqual($sorted[$i + 1]->combinedScore, $sorted[$i]->combinedScore);
        }
    }

    /**
     * Create a search context for testing.
     */
    private function createSearchContext(
        string $queryString,
        array $results,
        int $maxResults = 20
    ): SearchContext {
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create($queryString, $normalizer);
        $options = new SearchOptionsData(maxResults: $maxResults);

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
     * Create a search result for testing.
     */
    private function createSearchResult(
        ?string $matchedField,
        ?string $matchedValue
    ): SearchResultData {
        $item = (object) [
            'id' => rand(1, 1000),
            'name' => $matchedValue ?? 'Test Item',
        ];

        return SearchResultData::create(
            item: $item,
            score: rand(50, 100),
            modelType: 'TestModel',
            matchedField: $matchedField,
            matchedValue: $matchedValue
        );
    }

    /**
     * Create a next callback for pipeline testing.
     */
    private function createNextCallback(SearchContext $context): callable
    {
        return function ($passedContext) use ($context): array {
            $this->assertSame($context, $passedContext);
            return $context->results;
        };
    }

    /**
     * Invoke a private method on an object.
     */
    private function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($object, $methodName);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * Set a private property value on an object.
     */
    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
