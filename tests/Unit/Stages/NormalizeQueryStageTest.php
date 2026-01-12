<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\SearchContext;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\SearchQuery;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class NormalizeQueryStageTest extends TestCase
{
    private NormalizeQueryStage $stage;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new NormalizeQueryStage();
    }

    /**
     * Test that empty query returns empty collection.
     */
    public function test_handle_with_empty_query(): void
    {
        // Arrange: Create context with empty query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('', $normalizer);
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

        // Act: Process the empty query through the stage
        $result = $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should return empty collection
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test that valid query proceeds to next stage.
     */
    public function test_handle_with_valid_query(): void
    {
        // Arrange: Create context with valid search query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test query', $normalizer);
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

        $nextStageWasCalled = false;
        $nextStage = function ($context) use (&$nextStageWasCalled): string {
            $nextStageWasCalled = true;
            return 'next-result';
        };

        // Act: Process query through pipeline
        $result = $this->stage->handle($context, $nextStage);

        // Assert: Should call next stage and return its result
        $this->assertTrue($nextStageWasCalled);
        $this->assertEquals('next-result', $result);
    }

    /**
     * Test that context is passed unchanged to next stage.
     */
    public function test_handle_preserves_context(): void
    {
        // Arrange: Create context with specific options
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.5, maxResults: 10);

        $originalContext = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $receivedContext = null;
        $nextStage = function ($context) use (&$receivedContext): string {
            $receivedContext = $context;
            return 'result';
        };

        // Act: Process query through stage
        $this->stage->handle($originalContext, $nextStage);

        // Assert: Same context instance should be passed to next stage
        $this->assertSame($originalContext, $receivedContext);
    }

    /**
     * Test that query with only special characters returns empty collection.
     */
    public function test_handle_with_query_only_special_characters(): void
    {
        // Arrange: Create query that normalizes to empty string
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('!!! @#$', $normalizer);
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

        // Act: Process special character query
        $result = $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should return empty collection after normalization
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
