<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Tests\TestCase;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\Scoring\ScoringStrategy;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ScoringEngineTest extends TestCase
{
    private ScoringEngine $engine;
    private SearchContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock strategies
        $strategies = [
            $this->createMockStrategy('exact', 100, 0.95),
            $this->createMockStrategy('word', 90, 0.8),
            $this->createMockStrategy('fuzzy', 70, 0.6),
        ];

        $this->engine = new ScoringEngine(...$strategies);

        // Create context
        $normalizer = new StringNormalizer();
        $similarityCalculator = new SimilarityCalculator();
        $query = SearchQuery::create('test query', $normalizer);
        $options = new SearchOptionsData();

        $this->context = new SearchContext(
            $query,
            $options,
            $normalizer,
            $similarityCalculator,
            $this->createMock(\Fuzzy\Services\IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->engine,
            []
        );
    }

    private function createMockStrategy(
        string $name,
        int $priority,
        float $scoreToReturn
    ): ScoringStrategy {
        $strategy = $this->createMock(ScoringStrategy::class);
        $strategy->method('getPriority')->willReturn($priority);
        $strategy->method('supports')->willReturn(true);
        $strategy->method('calculate')->willReturn($scoreToReturn);

        return $strategy;
    }

    public function test_calculate_score_uses_strategies(): void
    {
        $indexEntry = [
            'field' => 'name',
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        $score = $this->engine->calculateScore($this->context, $indexEntry);

        // Should use the highest priority strategy that returns a score
        $this->assertEquals(0.95, $score);
    }

    public function test_calculate_score_clamping(): void
    {
        $indexEntry = [
            'field' => 'name',
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        // Create a strategy that returns out-of-bounds score
        $strategy = $this->createMock(ScoringStrategy::class);
        $strategy->method('getPriority')->willReturn(100);
        $strategy->method('supports')->willReturn(true);
        $strategy->method('calculate')->willReturn(2.0); // Above 1.0

        $engine = new ScoringEngine($strategy);

        $score = $engine->calculateScore($this->context, $indexEntry);

        $this->assertEquals(1.0, $score); // Should be clamped to 1.0
    }

    public function test_calculate_score_no_strategy_supports(): void
    {
        $indexEntry = [
            'field' => 'name',
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];

        // Create strategy that doesn't support
        $strategy = $this->createMock(ScoringStrategy::class);
        $strategy->method('getPriority')->willReturn(100);
        $strategy->method('supports')->willReturn(false);

        $engine = new ScoringEngine($strategy);

        $score = $engine->calculateScore($this->context, $indexEntry);

        // Should use fallback calculation
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_multi_word_score(): void
    {
        $indexEntries = [
            [
                'field' => 'name',
                'original_value' => 'test product',
                'normalized_words' => ['test', 'product'],
                'weight' => 1.0,
            ],
            [
                'field' => 'description',
                'original_value' => 'test description',
                'normalized_words' => ['test', 'description'],
                'weight' => 0.8,
            ],
        ];

        $score = $this->engine->calculateMultiWordScore($indexEntries, $this->context);

        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_multi_word_score_empty(): void
    {
        $score = $this->engine->calculateMultiWordScore([], $this->context);
        $this->assertEquals(0.0, $score);
    }

    public function test_calculate_multi_word_score_single_word_query(): void
    {
        // Create context with single word query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer); // Single word
        $context = new SearchContext(
            $query,
            new SearchOptionsData(),
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(\Fuzzy\Services\IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->engine,
            []
        );

        $indexEntries = [[
            'field' => 'name',
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ]];

        $score = $this->engine->calculateMultiWordScore($indexEntries, $context);
        $this->assertEquals(0.0, $score); // Not a multi-word query
    }

    public function test_calculate_multi_word_score_with_field_weighting(): void
    {
        config(['fuzzy.scoring.field_weights' => [
            'name' => 1.3,
            'description' => 0.8,
            'default' => 0.6,
        ]]);

        $indexEntries = [[
            'field' => 'name', // Should use name weight (1.3)
            'original_value' => 'test query product',
            'normalized_words' => ['test', 'query', 'product'],
            'weight' => 1.0,
        ]];

        $score = $this->engine->calculateMultiWordScore($indexEntries, $this->context);

        // Score should be affected by field weight
        $this->assertGreaterThan(0.0, $score);
    }

    public function test_calculate_multi_word_score_with_coverage_bonus(): void
    {
        // Create context with 2-word query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test query', $normalizer);
        $context = new SearchContext(
            $query,
            new SearchOptionsData(),
            $normalizer,
            new SimilarityCalculator(),
            $this->createMock(\Fuzzy\Services\IndexBuilder::class),
            $this->createMock(\Fuzzy\Contracts\IndexRepositoryInterface::class),
            $this->engine,
            []
        );

        config(['fuzzy.scoring.bonuses' => [
            'full_coverage' => 0.3,
            'high_coverage' => 0.15,
        ]]);

        $indexEntries = [[
            'field' => 'name',
            'original_value' => 'test query product',
            'normalized_words' => ['test', 'query', 'product'],
            'weight' => 1.0,
        ]];

        $score = $this->engine->calculateMultiWordScore($indexEntries, $context);

        // Should get coverage bonus
        $this->assertGreaterThan(0.0, $score);
    }
}
