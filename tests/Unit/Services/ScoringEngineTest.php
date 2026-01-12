<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Services\IndexBuilder;
use Fuzzy\Contracts\IndexRepositoryInterface;
use Fuzzy\Tests\TestCase;
use Fuzzy\Services\Scoring\ScoringEngine;
use Fuzzy\Services\Scoring\ScoringStrategy;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\SearchContext;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class ScoringEngineTest extends TestCase
{
    private ScoringEngine $scoringEngine;
    private SearchContext $searchContext;

    protected function setUp(): void
    {
        parent::setUp();

        $strategies = [
            $this->createMockStrategy(100, 0.95),
            $this->createMockStrategy(90, 0.8),
            $this->createMockStrategy(70, 0.6),
        ];

        $this->scoringEngine = new ScoringEngine(...$strategies);
        $this->searchContext = $this->createSearchContext();
    }

    /**
     * Creates a mock scoring strategy.
     */
    private function createMockStrategy(int $priority, float $score): ScoringStrategy
    {
        $strategy = $this->createMock(ScoringStrategy::class);
        $strategy->method('getPriority')->willReturn($priority);
        $strategy->method('supports')->willReturn(true);
        $strategy->method('calculate')->willReturn($score);

        return $strategy;
    }

    /**
     * Creates a search context for testing.
     */
    private function createSearchContext(): SearchContext
    {
        $normalizer = new StringNormalizer();
        $similarityCalculator = new SimilarityCalculator();
        $query = SearchQuery::create('test query', $normalizer);
        $options = new SearchOptionsData();

        return new SearchContext(
            query: $query,
            options: $options,
            normalizer: $normalizer,
            similarityCalculator: $similarityCalculator,
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->scoringEngine,
            indexDataArray: []
        );
    }

    /**
     * Creates a test index entry.
     */
    private function createTestIndexEntry(string $field = 'name'): array
    {
        return [
            'field' => $field,
            'original_value' => 'test product',
            'normalized_words' => ['test', 'product'],
            'weight' => 1.0,
        ];
    }

    public function test_calculate_score_uses_strategies(): void
    {
        // Arrange: Create test index entry
        $indexEntry = $this->createTestIndexEntry();

        // Act: Calculate score using engine
        $score = $this->scoringEngine->calculateScore($this->searchContext, $indexEntry);

        // Assert: Should use highest priority strategy score
        $this->assertEqualsWithDelta(0.95, $score, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_score_clamping(): void
    {
        // Arrange: Create strategy returning out-of-bounds score
        $strategy = $this->createMock(ScoringStrategy::class);
        $strategy->method('getPriority')->willReturn(100);
        $strategy->method('supports')->willReturn(true);
        $strategy->method('calculate')->willReturn(2.0);

        $engine = new ScoringEngine($strategy);
        $indexEntry = $this->createTestIndexEntry();

        // Act: Calculate score with out-of-bounds value
        $score = $engine->calculateScore($this->searchContext, $indexEntry);

        // Assert: Score should be clamped to maximum 1.0
        $this->assertEqualsWithDelta(1.0, $score, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_score_no_strategy_supports(): void
    {
        // Arrange: Create strategy that doesn't support calculation
        $strategy = $this->createMock(ScoringStrategy::class);
        $strategy->method('getPriority')->willReturn(100);
        $strategy->method('supports')->willReturn(false);

        $engine = new ScoringEngine($strategy);
        $indexEntry = $this->createTestIndexEntry();

        // Act: Calculate score without supporting strategy
        $score = $engine->calculateScore($this->searchContext, $indexEntry);

        // Assert: Should return fallback score within valid range
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_multi_word_score(): void
    {
        // Arrange: Create multiple index entries
        $indexEntries = [
            $this->createTestIndexEntry('name'),
            [
                'field' => 'description',
                'original_value' => 'test description',
                'normalized_words' => ['test', 'description'],
                'weight' => 0.8,
            ],
        ];

        // Act: Calculate multi-word score
        $score = $this->scoringEngine->calculateMultiWordScore($indexEntries, $this->searchContext);

        // Assert: Score should be within valid range
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function test_calculate_multi_word_score_empty(): void
    {
        // Arrange: Empty index entries array

        // Act: Calculate score with empty entries
        $score = $this->scoringEngine->calculateMultiWordScore([], $this->searchContext);

        // Assert: Should return 0 for empty entries
        $this->assertEqualsWithDelta(0.0, $score, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_multi_word_score_single_word_query(): void
    {
        // Arrange: Create context with single word query
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test', $normalizer);
        $context = new SearchContext(
            query: $query,
            options: new SearchOptionsData(),
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->scoringEngine,
            indexDataArray: []
        );

        $indexEntries = [$this->createTestIndexEntry()];

        // Act: Calculate multi-word score with single word query
        $score = $this->scoringEngine->calculateMultiWordScore($indexEntries, $context);

        // Assert: Should return 0 for single word queries
        $this->assertEqualsWithDelta(0.0, $score, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_multi_word_score_with_field_weighting(): void
    {
        // Arrange: Configure field weights and create index entry
        config(['fuzzy.scoring.field_weights' => [
            'name' => 1.3,
            'description' => 0.8,
            'default' => 0.6,
        ]]);

        $indexEntries = [$this->createTestIndexEntry('name')];

        // Act: Calculate score with field weighting
        $score = $this->scoringEngine->calculateMultiWordScore($indexEntries, $this->searchContext);

        // Assert: Score should be affected by field weight
        $this->assertGreaterThan(0.0, $score);
    }

    public function test_calculate_multi_word_score_with_coverage_bonus(): void
    {
        // Arrange: Create context with 2-word query and configure bonuses
        $normalizer = new StringNormalizer();
        $query = SearchQuery::create('test query', $normalizer);
        $context = new SearchContext(
            query: $query,
            options: new SearchOptionsData(),
            normalizer: $normalizer,
            similarityCalculator: new SimilarityCalculator(),
            indexBuilder: $this->createMock(IndexBuilder::class),
            indexRepository: $this->createMock(IndexRepositoryInterface::class),
            scoringEngine: $this->scoringEngine,
            indexDataArray: []
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

        // Act: Calculate score with coverage bonus
        $score = $this->scoringEngine->calculateMultiWordScore($indexEntries, $context);

        // Assert: Should apply coverage bonus
        $this->assertGreaterThan(0.0, $score);
    }
}
