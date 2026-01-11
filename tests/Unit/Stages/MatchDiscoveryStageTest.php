<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

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

#[AllowMockObjectsWithoutExpectations] // Pour éviter les notices PHPUnit
class MatchDiscoveryStageTest extends TestCase
{
    private MatchDiscoveryStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new MatchDiscoveryStage();
    }

    public function test_handle_with_empty_query(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => ['test' => [['id' => 1]]],
        ]);

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

        // Set indexData via reflection since it's private
        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return 'next';
        };

        // Act
        $result = $this->stage->handle($context, $next);

        // Assert: Should call next without processing
        $this->assertTrue($nextCalled);
        $this->assertEquals('next', $result);
        $this->assertEmpty($context->getAllPotentialMatches());
    }

    public function test_handle_discovers_exact_matches(): void
    {
        // Arrange
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
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Avec la logique corrigée, pour un mot unique:
        // 1. discoverExactMatches() → ajoute 2 matches
        // 2. discoverWordMatches() → SAUTE (car déjà traité en exact)
        // Total attendu: 2 matches
        $matches = $context->getAllPotentialMatches();
        $this->assertCount(2, $matches); // CORRIGÉ: 2, pas 4

        $user1Matches = $context->getPotentialMatchesForModel('User_1');
        $user2Matches = $context->getPotentialMatchesForModel('User_2');

        $this->assertCount(1, $user1Matches);
        $this->assertCount(1, $user2Matches);
        $this->assertEquals('name', $user1Matches[0]['field']);
        $this->assertEquals('email', $user2Matches[0]['field']);
    }

    public function test_handle_discovers_word_matches(): void
    {
        // Arrange
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
            $query,
            $options,
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should find matches for 'hello' and 'world', not 'test'
        $matches = $context->getAllPotentialMatches();
        // 'hello' et 'world' trouvés par discoverWordMatches()
        // discoverExactMatches() ne s'exécute pas car 'hello world' n'est pas dans l'index
        $this->assertCount(2, $matches);
        $this->assertArrayHasKey('User_1', $matches);
        $this->assertArrayHasKey('User_2', $matches);
        $this->assertArrayNotHasKey('User_3', $matches);
    }

    public function test_handle_discovers_fuzzy_matches_when_enabled(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('helo', $normalizer); // Typo for 'hello'
        $options = new SearchOptionsData(fuzzy: true, threshold: 0.7);

        $similarityCalculator = $this->createMock(SimilarityCalculator::class);
        $similarityCalculator->method('calculateWordSimilarity')
            ->willReturnCallback(function ($queryWord, $targetWord) {
                return $queryWord === 'helo' && $targetWord === 'hello' ? 0.8 : 0.0;
            });

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
                'test' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
            ],
        ]);

        $context = new SearchContext(
            $query,
            $options,
            $normalizer,
            $similarityCalculator,
            $this->createMock(IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->createMock(\Fuzzy\Services\Scoring\ScoringEngine::class),
            []
        );

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should find fuzzy match for 'helo' -> 'hello'
        $matches = $context->getAllPotentialMatches();
        $this->assertArrayHasKey('User_1', $matches); // Fuzzy match
    }

    public function test_handle_skips_fuzzy_matches_when_disabled(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('helo', $normalizer);
        $options = new SearchOptionsData(fuzzy: false); // Fuzzy disabled

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
            ],
        ]);

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

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should not find fuzzy matches
        $matches = $context->getAllPotentialMatches();
        $this->assertEmpty($matches);
    }

    public function test_handle_discovers_multi_word_matches(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('hello world', $normalizer);
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'hello' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
                // Note: 'world' not in index
            ],
        ]);

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

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should find User_1 for 'hello'
        $matches = $context->getAllPotentialMatches();
        $this->assertArrayHasKey('User_1', $matches);
    }

    public function test_handle_skips_short_words(): void
    {
        // Arrange
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('a be cat', $normalizer); // 'a' (1 char) et 'be' (2 chars)
        $options = new SearchOptionsData();

        $indexData = IndexData::fromArray([
            'wordIndex' => [
                'a' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
                'be' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
                'cat' => [['indexable_type' => 'User', 'indexable_id' => 3, 'field' => 'name']],
            ],
        ]);

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

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        // Act
        $this->stage->handle($context, fn() => 'next');

        // Assert: Should only process words with length >= 2
        $matches = $context->getAllPotentialMatches();

        // 'a' (1 char) → IGNORÉ partout
        // 'be' (2 chars) → TRAITÉ
        // 'cat' (3 chars) → TRAITÉ
        $this->assertArrayNotHasKey('User_1', $matches); // 'a' ignoré
        $this->assertArrayHasKey('User_2', $matches);    // 'be' traité
        $this->assertArrayHasKey('User_3', $matches);    // 'cat' traité
    }
}
