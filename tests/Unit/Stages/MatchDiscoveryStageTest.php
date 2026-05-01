<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Services\Scoring\ScoringEngine;
use ReflectionProperty;
use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\MatchDiscoveryStage\MatchFinder;
use Fuzzy\Config\MatchDiscoveryConfig;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Fuzzy\ValueObjects\IndexData;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;


#[AllowMockObjectsWithoutExpectations]
final class MatchDiscoveryStageTest extends TestCase
{
    private MatchDiscoveryStage $stage;
    private StringNormalizer $normalizer;
    private SimilarityCalculator&MockObject $similarityCalculator;
    private MatchFinder&MockObject $matchFinder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StringNormalizer();
        $this->similarityCalculator = $this->createMock(SimilarityCalculator::class);
        $this->matchFinder = $this->createMock(MatchFinder::class);

        $this->stage = new MatchDiscoveryStage(
            config: MatchDiscoveryConfig::fromConfig(),
            finder: $this->matchFinder
        );
    }

    private function createContext(
        string $query,
        SearchOptionsData $options,
        array $wordIndex
    ): SearchContext {
        $searchQuery = SearchQuery::create($query, $this->normalizer);
        $indexData = IndexData::fromArray(['wordIndex' => $wordIndex]);

        $context = new SearchContext(
            query: $searchQuery,
            options: $options,
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->createMock(ScoringEngine::class),
            indexDataArray: []
        );

        $reflection = new ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        return $context;
    }

    public function test_handle_with_empty_query(): void
    {
        $context = $this->createContext('', new SearchOptionsData(), []);

        $nextCalled = false;
        $next = function () use (&$nextCalled): string {
            $nextCalled = true;
            return 'next';
        };

        $result = $this->stage->handle($context, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('next', $result);

        $this->matchFinder->expects($this->never())
            ->method('discoverExactMatches');
    }

    /**
     * Test that when exact matches are found for a single word,
     * we go into the single-word-with-exact-match path instead
     * of the normal discovery path.
     */
    public function test_handle_discovers_exact_matches(): void
    {
        $wordIndex = [
            'test' => [
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name'],
            ],
        ];

        $context = $this->createContext('test', new SearchOptionsData(), $wordIndex);

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->with($context)
            ->willReturn(true);

        // For single word with exact match, we go to handleSingleWordWithExactMatch
        // So discoverWordMatches is NOT called
        $this->matchFinder->expects($this->never())
            ->method('discoverWordMatches');

        $this->matchFinder->expects($this->never())
            ->method('discoverFuzzyMatchesOptimized');

        $this->matchFinder->expects($this->never())
            ->method('discoverMultiWordMatches');

        // Instead, we expect discoverVeryCloseMatches or discoverCloseMatchesOptimized
        // depending on index size. Since wordIndex has 1 entry (small index),
        // we expect discoverVeryCloseMatches
        $this->matchFinder->expects($this->once())
            ->method('discoverVeryCloseMatches')
            ->with($context, 'test', $wordIndex);

        $this->stage->handle($context, fn() => 'next');
    }

    /**
     * Test with multiple words - then we go into the else branch.
     */
    public function test_handle_with_multiple_words(): void
    {
        $wordIndex = [
            'test' => [['indexable_type' => 'User', 'indexable_id' => 1]],
            'query' => [['indexable_type' => 'User', 'indexable_id' => 1]],
        ];

        $context = $this->createContext('test query', new SearchOptionsData(), $wordIndex);

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->with($context)
            ->willReturn(false);

        $this->matchFinder->expects($this->once())
            ->method('discoverWordMatches')
            ->with($context);

        $this->matchFinder->expects($this->once())
            ->method('discoverFuzzyMatchesOptimized')
            ->with($context);

        $this->matchFinder->expects($this->once())
            ->method('discoverMultiWordMatches')
            ->with($context);

        $this->stage->handle($context, fn() => 'next');
    }

    public function test_handle_skips_fuzzy_when_disabled(): void
    {
        $wordIndex = ['test' => [['indexable_type' => 'User', 'indexable_id' => 1]]];
        $context = $this->createContext('test query', new SearchOptionsData(fuzzy: false), $wordIndex);

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->willReturn(false);

        $this->matchFinder->expects($this->once())
            ->method('discoverWordMatches');

        $this->matchFinder->expects($this->never())
            ->method('discoverFuzzyMatchesOptimized');

        $this->matchFinder->expects($this->once())
            ->method('discoverMultiWordMatches');

        $this->stage->handle($context, fn() => 'next');
    }

    public function test_handle_skips_multi_word_when_single_word(): void
    {
        $wordIndex = ['test' => [['indexable_type' => 'User', 'indexable_id' => 1]]];
        $context = $this->createContext('test', new SearchOptionsData(), $wordIndex);

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->willReturn(true);

        // Single word with exact match - goes to single word path
        $this->matchFinder->expects($this->never())
            ->method('discoverWordMatches');

        $this->matchFinder->expects($this->never())
            ->method('discoverFuzzyMatchesOptimized');

        $this->matchFinder->expects($this->never())
            ->method('discoverMultiWordMatches');

        $this->matchFinder->expects($this->once())
            ->method('discoverVeryCloseMatches');

        $this->stage->handle($context, fn() => 'next');
    }

    public function test_handle_single_word_with_exact_match_small_index(): void
    {
        $wordIndex = [];
        for ($i = 0; $i < 50; $i++) {
            $wordIndex["word{$i}"] = [['indexable_type' => 'User', 'indexable_id' => $i]];
        }

        $context = $this->createContext('test', new SearchOptionsData(fuzzy: true), $wordIndex);

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->willReturn(true);

        $this->matchFinder->expects($this->once())
            ->method('discoverVeryCloseMatches')
            ->with($context, 'test', $wordIndex);

        $this->matchFinder->expects($this->never())
            ->method('discoverCloseMatchesOptimized');

        $this->stage->handle($context, fn() => 'next');
    }

    public function test_handle_single_word_with_exact_match_large_index(): void
    {
        $wordIndex = [];
        for ($i = 0; $i < 2000; $i++) {
            $wordIndex["word{$i}"] = [['indexable_type' => 'User', 'indexable_id' => $i]];
        }

        $context = $this->createContext('test', new SearchOptionsData(fuzzy: true), $wordIndex);

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->willReturn(true);

        $this->matchFinder->expects($this->never())
            ->method('discoverVeryCloseMatches');

        $this->matchFinder->expects($this->once())
            ->method('discoverCloseMatchesOptimized')
            ->with($context, 'test');

        $this->stage->handle($context, fn() => 'next');
    }

    public function test_handle_calls_next_with_results(): void
    {
        $context = $this->createContext('test', new SearchOptionsData(), []);

        $next = function (SearchContext $ctx) {
            return 'processed';
        };

        $this->matchFinder->expects($this->once())
            ->method('discoverExactMatches')
            ->willReturn(false);

        $this->matchFinder->expects($this->once())
            ->method('discoverWordMatches');

        $this->matchFinder->expects($this->once())
            ->method('discoverFuzzyMatchesOptimized');

        $this->matchFinder->expects($this->never())
            ->method('discoverMultiWordMatches');

        $result = $this->stage->handle($context, $next);

        $this->assertEquals('processed', $result);
    }

    public function test_get_priority(): void
    {
        $this->assertEquals(75, $this->stage->getPriority());
    }

    public function test_get_type(): void
    {
        $this->assertEquals(\Fuzzy\Enums\StageType::MATCH_DISCOVERY, $this->stage->getType());
    }
}
