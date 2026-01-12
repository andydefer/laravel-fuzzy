<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Services\Scoring\ScoringEngine;
use ReflectionProperty;
use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Fuzzy\ValueObjects\IndexData;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class MatchDiscoveryStageTest extends TestCase
{
    private MatchDiscoveryStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new MatchDiscoveryStage();
    }

    public function test_handle_with_empty_query(): void
    {
        // Arrange: Creating a search context with an empty query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => ['test' => [['id' => 1]]],
        ]);

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

        $this->setPrivateProperty($context, 'indexData', $indexData);

        $nextCalled = false;
        $next = function () use (&$nextCalled): string {
            $nextCalled = true;
            return 'next';
        };

        // Act: Handling empty query through the match discovery stage
        $result = $this->stage->handle($context, $next);

        // Assert: Stage should call next without processing empty query
        $this->assertTrue($nextCalled);
        $this->assertEquals('next', $result);
        $this->assertEmpty($context->getAllPotentialMatches());
    }

    public function test_handle_discovers_exact_matches(): void
    {
        // Arrange: Setting up context with exact word match in index
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'test' => [
                    ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name'],
                    ['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'email'],
                ],
            ],
        ]);

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

        $this->setPrivateProperty($context, 'indexData', $indexData);

        // Act: Discovering matches for exact word
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Two exact matches should be found
        $matches = $context->getAllPotentialMatches();
        $this->assertCount(2, $matches);

        $user1Matches = $context->getPotentialMatchesForModel('User_1');
        $user2Matches = $context->getPotentialMatchesForModel('User_2');

        $this->assertCount(1, $user1Matches);
        $this->assertCount(1, $user2Matches);
        $this->assertEquals('name', $user1Matches[0]['field']);
        $this->assertEquals('email', $user2Matches[0]['field']);
    }

    public function test_handle_discovers_word_matches(): void
    {
        // Arrange: Setting up context with multiple words in query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('hello world', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
                'world' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
                'test' => [['indexable_type' => 'User', 'indexable_id' => 3, 'field' => 'name']],
            ],
        ]);

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

        $this->setPrivateProperty($context, 'indexData', $indexData);

        // Act: Discovering matches for individual query words
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should find matches for 'hello' and 'world', not 'test'
        $matches = $context->getAllPotentialMatches();
        $this->assertCount(2, $matches);
        $this->assertArrayHasKey('User_1', $matches);
        $this->assertArrayHasKey('User_2', $matches);
        $this->assertArrayNotHasKey('User_3', $matches);
    }

    public function test_handle_discovers_fuzzy_matches_when_enabled(): void
    {
        // Arrange: Setting up fuzzy search with typo in query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('helo', $normalizer);
        $options = new SearchOptionsData(fuzzy: true, threshold: 0.7);

        $similarityCalculator = $this->createMock(SimilarityCalculator::class);
        $similarityCalculator->method('calculateWordSimilarity')
            ->willReturnCallback(function (string $queryWord, string $targetWord): float {
                return $queryWord === 'helo' && $targetWord === 'hello' ? 0.8 : 0.0;
            });

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
                'test' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
            ],
        ]);

        $context = new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: $similarityCalculator,
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $this->setPrivateProperty($context, 'indexData', $indexData);

        // Act: Discovering fuzzy matches for typo
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should find fuzzy match for 'helo' -> 'hello'
        $matches = $context->getAllPotentialMatches();
        $this->assertArrayHasKey('User_1', $matches);
    }

    public function test_handle_skips_fuzzy_matches_when_disabled(): void
    {
        // Arrange: Fuzzy search disabled
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('helo', $normalizer);
        $options = new SearchOptionsData(fuzzy: false);

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
            ],
        ]);

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

        $this->setPrivateProperty($context, 'indexData', $indexData);

        // Act: Trying to find matches with fuzzy disabled
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should not find fuzzy matches when disabled
        $matches = $context->getAllPotentialMatches();
        $this->assertEmpty($matches);
    }

    public function test_handle_discovers_multi_word_matches(): void
    {
        // Arrange: Multiple words query with partial index match
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('hello world', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
            ],
        ]);

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

        $this->setPrivateProperty($context, 'indexData', $indexData);

        // Act: Discovering matches for multi-word query
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should find User_1 for 'hello' even though 'world' not in index
        $matches = $context->getAllPotentialMatches();
        $this->assertArrayHasKey('User_1', $matches);
    }

    public function test_handle_skips_short_words(): void
    {
        // Arrange: Query contains very short words
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('a be cat', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'a' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
                'be' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
                'cat' => [['indexable_type' => 'User', 'indexable_id' => 3, 'field' => 'name']],
            ],
        ]);

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

        $this->setPrivateProperty($context, 'indexData', $indexData);

        // Act: Discovering matches with short words
        $this->stage->handle($context, fn(): string => 'next');

        // Assert: Should ignore very short words (length < 2)
        $matches = $context->getAllPotentialMatches();
        $this->assertArrayNotHasKey('User_1', $matches); // 'a' ignored
        $this->assertArrayHasKey('User_2', $matches);    // 'be' processed
        $this->assertArrayHasKey('User_3', $matches);    // 'cat' processed
    }

    /**
     * Sets a private property value using reflection.
     *
     * @param object $object
     * @param string $propertyName
     * @param mixed $value
     */
    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
