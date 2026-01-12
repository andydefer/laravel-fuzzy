<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use stdClass;
use ReflectionProperty;
use ReflectionMethod;
use ReflectionClass;
use InvalidArgumentException;
use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Fuzzy\Services\Scoring\ScoringEngine;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

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
            $this->createMock(IndexRepositoryInterface::class),
            $this->createMock(ScoringEngine::class),
            []
        );

        $nextCalled = false;
        $next = function () use (&$nextCalled): string {
            $nextCalled = true;
            return 'next';
        };

        // Act
        $result = $this->stage->handle($context, $next);

        // Assert: Should call next without adding results
        $this->assertTrue($nextCalled);
        $this->assertEquals('next', $result);
        $this->assertEmpty($context->results);
    }

    public function test_handle_with_potential_matches(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $mockModel = new stdClass();
        $mockModel->id = 1;
        $mockModel->name = 'Test User';

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(0.8);
        $scoringEngine->method('calculateMultiWordScore')->willReturn(0.0);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $scoringEngine,
            []
        );

        // Add potential matches via reflection
        $reflection = new ReflectionProperty($context, 'potentialMatches');
        $reflection->setAccessible(true);
        $reflection->setValue($context, [
            'User_1' => [
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name', 'original_value' => 'Test User'],
            ],
        ]);

        // Add model instance via reflection
        $reflectionModels = new ReflectionProperty($context, 'preloadedModels');
        $reflectionModels->setAccessible(true);
        $reflectionModels->setValue($context, ['User_1' => $mockModel]);

        // Act
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should add result to context
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
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.5); // High min score

        $mockModel = new stdClass();
        $mockModel->id = 1;

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(0.3); // Low score
        $scoringEngine->method('calculateMultiWordScore')->willReturn(0.0);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $scoringEngine,
            []
        );

        $reflection = new ReflectionProperty($context, 'potentialMatches');
        $reflection->setAccessible(true);
        $reflection->setValue($context, [
            'User_1' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
        ]);

        $reflectionModels = new ReflectionProperty($context, 'preloadedModels');
        $reflectionModels->setAccessible(true);
        $reflectionModels->setValue($context, ['User_1' => $mockModel]);

        // Act
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should NOT add result (score below min)
        $this->assertEmpty($context->results);
    }

    public function test_handle_skips_missing_model(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $scoringEngine = $this->createMock(ScoringEngine::class);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $scoringEngine,
            []
        );

        // Add potential match but NO model instance
        $reflection = new ReflectionProperty($context, 'potentialMatches');
        $reflection->setAccessible(true);
        $reflection->setValue($context, [
            'User_1' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
        ]);

        // Act
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should not add result (model missing)
        $this->assertEmpty($context->results);
    }

    public function test_handle_with_multiple_matches_for_same_model(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $mockModel = new stdClass();
        $mockModel->id = 1;

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')
            ->willReturnOnConsecutiveCalls(0.6, 0.8); // Second match has higher score

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(IndexRepositoryInterface::class),
            $scoringEngine,
            []
        );

        $reflection = new ReflectionProperty($context, 'potentialMatches');
        $reflection->setAccessible(true);
        $reflection->setValue($context, [
            'User_1' => [
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name', 'original_value' => 'Test Name'],
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'email', 'original_value' => 'test@example.com'],
            ],
        ]);

        $reflectionModels = new ReflectionProperty($context, 'preloadedModels');
        $reflectionModels->setAccessible(true);
        $reflectionModels->setValue($context, ['User_1' => $mockModel]);

        // Act
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should use best score (0.8)
        $this->assertArrayHasKey('User_1', $context->results);
        $this->assertEqualsWithDelta(0.8, $context->results['User_1']->score, PHP_FLOAT_EPSILON);
    }

    public function test_handle_with_multi_word_query(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test query', $normalizer);
        $options = new SearchOptionsData(minScore: 0.1);

        $mockModel = new stdClass();
        $mockModel->id = 1;

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(0.7);
        $scoringEngine->method('calculateMultiWordScore')->willReturn(0.9);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createStub(IndexBuilder::class),
            $this->createStub(IndexRepositoryInterface::class),
            $scoringEngine,
            []
        );

        $reflection = new ReflectionProperty($context, 'potentialMatches');
        $reflection->setAccessible(true);
        // CORRECTION CRITIQUE : 2 matches au lieu de 1
        $reflection->setValue($context, [
            'User_1' => [
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name', 'original_value' => 'Test Query'],
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'description', 'original_value' => 'Another Query'],
            ],
        ]);

        $reflectionModels = new ReflectionProperty($context, 'preloadedModels');
        $reflectionModels->setAccessible(true);
        $reflectionModels->setValue($context, ['User_1' => $mockModel]);

        // Act
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should use multi-word score (0.9)
        $this->assertArrayHasKey('User_1', $context->results);
        $this->assertEqualsWithDelta(0.9, $context->results['User_1']->score, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_best_score_clamping(): void
    {
        // Use reflection to test private method
        $method = new ReflectionMethod($this->stage, 'calculateBestScore');
        $method->setAccessible(true);

        $mockModel = new stdClass();

        $context = $this->createMock(SearchContext::class);
        $context->method('hasMultipleWords')->willReturn(false);

        $scoringEngine = $this->createMock(ScoringEngine::class);
        $scoringEngine->method('calculateScore')->willReturn(2.0); // Above 1.0

        // Set scoringEngine on context via reflection
        $reflectionContext = new ReflectionClass($context);
        $engineProp = $reflectionContext->getProperty('scoringEngine');
        $engineProp->setAccessible(true);
        $engineProp->setValue($context, $scoringEngine);

        $matches = [['test' => 'match']];

        // Act
        $score = $method->invoke($this->stage, $context, $matches, $mockModel);

        // Assert: Should be clamped to 1.0
        $this->assertEqualsWithDelta(1.0, $score, PHP_FLOAT_EPSILON);
    }

    public function test_find_best_match(): void
    {
        $method = new ReflectionMethod($this->stage, 'findBestMatch');
        $method->setAccessible(true);

        $matches = [
            ['field' => 'name', 'value' => 'first'],
            ['field' => 'email', 'value' => 'second'],
        ];

        $bestMatch = $method->invoke($this->stage, $matches);

        // Should return first match
        $this->assertEquals('name', $bestMatch['field']);
        $this->assertEquals('first', $bestMatch['value']);
    }

    public function test_find_best_match_empty_array(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $method = new ReflectionMethod($this->stage, 'findBestMatch');
        $method->setAccessible(true);
        $method->invoke($this->stage, []);
    }
}
