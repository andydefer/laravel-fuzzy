<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class NormalizeQueryStageTest extends TestCase
{
    private NormalizeQueryStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new NormalizeQueryStage();
    }

    public function test_handle_with_empty_query(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('', $normalizer);
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

        // Act
        $result = $this->stage->handle($context, fn() => 'next');

        // Assert: Should return empty collection
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_handle_with_valid_query(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test query', $normalizer);
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

        $nextCalled = false;
        $next = function ($ctx) use (&$nextCalled) {
            $nextCalled = true;
            return 'next-result';
        };

        // Act
        $result = $this->stage->handle($context, $next);

        // Assert: Should call next stage
        $this->assertTrue($nextCalled);
        $this->assertEquals('next-result', $result);
    }

    public function test_handle_preserves_context(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.5, maxResults: 10);

        $originalContext = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $receivedContext = null;
        $next = function ($ctx) use (&$receivedContext) {
            $receivedContext = $ctx;
            return 'result';
        };

        // Act
        $this->stage->handle($originalContext, $next);

        // Assert: Context should be passed unchanged
        $this->assertSame($originalContext, $receivedContext);
    }

    public function test_handle_with_query_only_special_characters(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('!!! @#$', $normalizer); // Normalizes to empty
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

        // Act
        $result = $this->stage->handle($context, fn() => 'next');

        // Assert: Should return empty collection
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
