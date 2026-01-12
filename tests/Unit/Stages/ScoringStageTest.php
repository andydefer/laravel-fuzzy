<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\SearchContext;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\SearchQuery;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class ScoringStageTest extends TestCase
{
    private ScoringStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new ScoringStage();
    }

    public function test_handle_with_no_potential_matches(): void
    {
        // Arrange: Scoring stage with empty potential matches
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test', normalizer: $normalizer);
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

        $nextCalled = false;
        $next = function () use (&$nextCalled): string {
            $nextCalled = true;
            return 'next';
        };

        // Act: Process the pipeline stage
        $result = $this->stage->handle(context: $context, next: $next);

        // Assert: Should call next middleware without adding results
        $this->assertTrue($nextCalled);
        $this->assertEquals('next', $result);
        $this->assertEmpty($context->results);
    }

    public function test_handle_with_potential_matches(): void
    {
        // Arrange: Context with potential matches and preloaded model
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test', normalizer: $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $mockModel = new stdClass();
        $mockModel->id = 1;
        $mockModel->name = 'Test User';

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(0.8);
        $scoringEngine->method('calculateMultiWordScore')->willReturn(0.0);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $scoringEngine,
            indexDataArray: []
        );

        $this->setPrivateProperty($context, 'potentialMatches', [
            'User_1' => [
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'name',
                    'original_value' => 'Test User',
                ],
            ],
        ]);

        $this->setPrivateProperty($context, 'preloadedModels', ['User_1' => $mockModel]);

        // Act: Process matches through scoring stage
        $this->stage->handle(
            context: $context,
            next: fn(): string => 'next'
        );

        // Assert: Should add scored result to context
        $this->assertArrayHasKey('User_1', $context->results);

        $result = $context->results['User_1'];
        $this->assertInstanceOf(SearchResultData::class, $result);
        $this->assertSame($mockModel, $result->item);
        $this->assertEqualsWithDelta(0.8, $result->score, PHP_FLOAT_EPSILON);
        $this->assertSame('User', $result->modelType);
        $this->assertSame('name', $result->matchedField);
        $this->assertSame('Test User', $result->matchedValue);
    }

    public function test_handle_filters_by_min_score(): void
    {
        // Arrange: Context with score below minimum threshold
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test', normalizer: $normalizer);
        $options = new SearchOptionsData(minScore: 0.5);

        $mockModel = new stdClass();
        $mockModel->id = 1;

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(0.3);
        $scoringEngine->method('calculateMultiWordScore')->willReturn(0.0);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $scoringEngine,
            indexDataArray: []
        );

        $this->setPrivateProperty($context, 'potentialMatches', [
            'User_1' => [
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'name',
                ],
            ],
        ]);

        $this->setPrivateProperty($context, 'preloadedModels', ['User_1' => $mockModel]);

        // Act: Process matches with score below threshold
        $this->stage->handle(
            context: $context,
            next: fn(): string => 'next'
        );

        // Assert: Should not add result due to insufficient score
        $this->assertEmpty($context->results);
    }

    public function test_handle_skips_missing_model(): void
    {
        // Arrange: Context with potential matches but no corresponding model
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test', normalizer: $normalizer);
        $options = new SearchOptionsData();

        $scoringEngine = $this->createMock(ScoringEngine::class);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $scoringEngine,
            indexDataArray: []
        );

        $this->setPrivateProperty($context, 'potentialMatches', [
            'User_1' => [
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'name',
                ],
            ],
        ]);

        // Act: Process matches without corresponding model
        $this->stage->handle(
            context: $context,
            next: fn(): string => 'next'
        );

        // Assert: Should skip result due to missing model
        $this->assertEmpty($context->results);
    }

    public function test_handle_with_multiple_matches_for_same_model(): void
    {
        // Arrange: Model with matches in multiple fields
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test', normalizer: $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $mockModel = new stdClass();
        $mockModel->id = 1;

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')
            ->willReturnOnConsecutiveCalls(0.6, 0.8);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $scoringEngine,
            indexDataArray: []
        );

        $this->setPrivateProperty($context, 'potentialMatches', [
            'User_1' => [
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'name',
                    'original_value' => 'Test Name',
                ],
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'email',
                    'original_value' => 'test@example.com',
                ],
            ],
        ]);

        $this->setPrivateProperty($context, 'preloadedModels', ['User_1' => $mockModel]);

        // Act: Process model with multiple field matches
        $this->stage->handle(
            context: $context,
            next: fn(): string => 'next'
        );

        // Assert: Should use the highest score among all matches
        $this->assertArrayHasKey('User_1', $context->results);
        $this->assertEqualsWithDelta(0.8, $context->results['User_1']->score, PHP_FLOAT_EPSILON);
    }

    public function test_handle_with_multi_word_query(): void
    {
        // Arrange: Multi-word query with multiple matches
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create(query: 'test query', normalizer: $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $mockModel = new stdClass();
        $mockModel->id = 1;

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(0.7);
        $scoringEngine->method('calculateMultiWordScore')->willReturn(0.9);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createStub(IndexBuilder::class),
            indexRepository: $this->createStub(IndexRepositoryInterface::class),
            scoringEngine: $scoringEngine,
            indexDataArray: []
        );

        $this->setPrivateProperty($context, 'potentialMatches', [
            'User_1' => [
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'name',
                    'original_value' => 'Test Query',
                ],
                [
                    'indexable_type' => 'User',
                    'indexable_id' => 1,
                    'field' => 'description',
                    'original_value' => 'Another Query',
                ],
            ],
        ]);

        $this->setPrivateProperty($context, 'preloadedModels', ['User_1' => $mockModel]);

        // Act: Process multi-word query with multiple matches
        $this->stage->handle(
            context: $context,
            next: fn(): string => 'next'
        );

        // Assert: Should use multi-word scoring algorithm
        $this->assertArrayHasKey('User_1', $context->results);
        $this->assertEqualsWithDelta(0.9, $context->results['User_1']->score, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_best_score_clamping(): void
    {
        // Arrange: Score that exceeds maximum value
        $calculateBestScoreMethod = new ReflectionMethod($this->stage, 'calculateBestScore');
        $calculateBestScoreMethod->setAccessible(true);

        $mockModel = new stdClass();

        $context = $this->createMock(SearchContext::class);
        $context->method('hasMultipleWords')->willReturn(false);

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(2.0);

        $this->setPrivateProperty($context, 'scoringEngine', $scoringEngine);

        $matches = [['test' => 'match']];

        // Act: Calculate score that exceeds maximum
        $score = $calculateBestScoreMethod->invoke(
            $this->stage,
            $context,
            $matches,
            $mockModel
        );

        // Assert: Score should be clamped to maximum of 1.0
        $this->assertEqualsWithDelta(1.0, $score, PHP_FLOAT_EPSILON);
    }

    public function test_find_best_match(): void
    {
        // Arrange: Multiple potential matches
        $findBestMatchMethod = new ReflectionMethod($this->stage, 'extractBestMatchDetails');
        $findBestMatchMethod->setAccessible(true);

        $matches = [
            ['field' => 'name', 'value' => 'first'],
            ['field' => 'email', 'value' => 'second'],
        ];

        // Act: Find best match from array
        $bestMatch = $findBestMatchMethod->invoke($this->stage, $matches);

        // Assert: Should return first match in array
        $this->assertEquals('name', $bestMatch['field']);
        $this->assertEquals('first', $bestMatch['value']);
    }

    public function test_find_best_match_empty_array(): void
    {
        // Arrange: Empty matches array
        $findBestMatchMethod = new ReflectionMethod($this->stage, 'extractBestMatchDetails');
        $findBestMatchMethod->setAccessible(true);

        // Assert: Should throw exception for empty array
        $this->expectException(InvalidArgumentException::class);

        // Act: Attempt to find best match from empty array
        $findBestMatchMethod->invoke($this->stage, []);
    }

    /**
     * Helper method to set private property value using reflection.
     *
     * @param object $object The object to modify
     * @param string $propertyName The name of the private property
     * @param mixed $value The value to set
     */
    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
