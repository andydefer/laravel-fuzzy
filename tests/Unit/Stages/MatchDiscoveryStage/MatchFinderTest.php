<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages\MatchDiscoveryStage;

use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Stages\MatchDiscoveryStage\MatchFinder;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\IndexBuilder;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use Fuzzy\ValueObjects\IndexData;
use Fuzzy\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;


#[AllowMockObjectsWithoutExpectations]
final class MatchFinderTest extends TestCase
{
    private MatchFinder $finder;
    private StringNormalizer $normalizer;
    private SimilarityCalculator&MockObject $similarityCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = new MatchFinder();
        $this->normalizer = new StringNormalizer();
        $this->similarityCalculator = $this->createMock(SimilarityCalculator::class);
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

        $reflection = new \ReflectionProperty($context, 'indexData');
        $reflection->setAccessible(true);
        $reflection->setValue($context, $indexData);

        return $context;
    }

    public function test_discover_exact_matches_finds_exact_word(): void
    {
        $wordIndex = [
            'test' => [
                ['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name'],
            ],
        ];

        $context = $this->createContext('test', new SearchOptionsData(), $wordIndex);

        $result = $this->finder->discoverExactMatches($context);

        $this->assertTrue($result);
        $matches = $context->getAllPotentialMatches();
        $this->assertCount(1, $matches);
    }

    public function test_discover_exact_matches_returns_false_when_not_found(): void
    {
        $wordIndex = [
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 1]],
        ];

        $context = $this->createContext('test', new SearchOptionsData(), $wordIndex);

        $result = $this->finder->discoverExactMatches($context);

        $this->assertFalse($result);
        $this->assertEmpty($context->getAllPotentialMatches());
    }

    public function test_discover_word_matches_finds_individual_words(): void
    {
        $wordIndex = [
            'php' => [['indexable_type' => 'User', 'indexable_id' => 1, 'field' => 'name']],
            'laravel' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
        ];

        $context = $this->createContext('php laravel', new SearchOptionsData(), $wordIndex);

        $this->finder->discoverWordMatches($context);

        $matches = $context->getAllPotentialMatches();
        $this->assertCount(2, $matches);
        $this->assertArrayHasKey('User_1', $matches);
        $this->assertArrayHasKey('User_2', $matches);
    }

    /**
     * Test that discoverWordMatches does NOT process single-word queries.
     * Single-word queries are handled by exact match discovery instead.
     */
    public function test_discover_word_matches_skips_single_word_queries(): void
    {
        $wordIndex = [
            'php' => [['indexable_type' => 'User', 'indexable_id' => 2, 'field' => 'name']],
        ];

        $context = $this->createContext('php', new SearchOptionsData(), $wordIndex);

        $this->finder->discoverWordMatches($context);

        $matches = $context->getAllPotentialMatches();
        // Single word queries are skipped by discoverWordMatches
        $this->assertCount(0, $matches);
    }

    public function test_discover_fuzzy_matches_with_small_index(): void
    {
        $wordIndex = [
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 1]],
        ];

        $this->similarityCalculator->method('calculateWordSimilarity')
            ->willReturn(0.85);

        $context = $this->createContext(
            'helo',
            new SearchOptionsData(fuzzy: true, threshold: 0.7),
            $wordIndex
        );

        $this->finder->discoverFuzzyMatchesOptimized($context);

        $matches = $context->getAllPotentialMatches();
        $this->assertCount(1, $matches);
    }

    public function test_discover_fuzzy_matches_respects_threshold(): void
    {
        $wordIndex = [
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 1]],
        ];

        $this->similarityCalculator->method('calculateWordSimilarity')
            ->willReturn(0.6);

        $context = $this->createContext(
            'helo',
            new SearchOptionsData(fuzzy: true, threshold: 0.8),
            $wordIndex
        );

        $this->finder->discoverFuzzyMatchesOptimized($context);

        $matches = $context->getAllPotentialMatches();
        $this->assertEmpty($matches);
    }

    public function test_discover_multi_word_matches(): void
    {
        $wordIndex = [
            'php' => [['indexable_type' => 'User', 'indexable_id' => 1]],
            'laravel' => [['indexable_type' => 'User', 'indexable_id' => 1]], // same model
        ];

        $context = $this->createContext('php laravel', new SearchOptionsData(), $wordIndex);

        $this->finder->discoverMultiWordMatches($context);

        $matches = $context->getAllPotentialMatches();
        // Should only add User_1 once
        $this->assertCount(1, $matches);
    }

    public function test_discover_very_close_matches_with_high_threshold(): void
    {
        $wordIndex = [
            'php' => [['indexable_type' => 'User', 'indexable_id' => 1]],
            'ph' => [['indexable_type' => 'User', 'indexable_id' => 2]],
        ];

        $this->similarityCalculator->method('calculateWordSimilarity')
            ->willReturnCallback(function ($a, $b) {
                if ($a === 'php' && $b === 'ph') {
                    return 0.9;
                }
                return 0.5;
            });

        $context = $this->createContext('php', new SearchOptionsData(threshold: 0.5), $wordIndex);

        $this->finder->discoverVeryCloseMatches($context, 'php', $wordIndex);

        $matches = $context->getAllPotentialMatches();
        // Only high similarity match should be added
        $this->assertArrayHasKey('User_2', $matches);
    }

    public function test_discover_close_matches_optimized_for_large_index(): void
    {
        // Build a large word index
        $wordIndex = [];
        for ($i = 0; $i < 100; $i++) {
            $wordIndex["word{$i}"] = [['indexable_type' => 'User', 'indexable_id' => $i]];
        }
        $wordIndex['php'] = [['indexable_type' => 'User', 'indexable_id' => 100]];

        $this->similarityCalculator->method('calculateWordSimilarity')
            ->willReturn(0.95);

        $context = $this->createContext('php', new SearchOptionsData(threshold: 0.5), $wordIndex);

        $this->finder->discoverCloseMatchesOptimized($context, 'php');

        $matches = $context->getAllPotentialMatches();
        $this->assertArrayHasKey('User_100', $matches);
    }
}
