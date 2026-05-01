<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages\MatchDiscoveryStage;

use Fuzzy\Config\MatchDiscoveryConfig;
use Fuzzy\Stages\MatchDiscoveryStage\IndexOptimizer;
use Fuzzy\Tests\TestCase;

final class IndexOptimizerTest extends TestCase
{
    private IndexOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizer = new IndexOptimizer();
    }

    public function test_get_optimal_lengths_to_check_for_short_word(): void
    {
        $lengths = $this->optimizer->getOptimalLengthsToCheck(3);

        $this->assertContains(2, $lengths);
        $this->assertContains(3, $lengths);
        $this->assertContains(4, $lengths);
        $this->assertContains(5, $lengths);
        $this->assertContains(6, $lengths);
    }

    public function test_get_optimal_lengths_to_check_for_medium_word(): void
    {
        $lengths = $this->optimizer->getOptimalLengthsToCheck(6);

        $this->assertContains(4, $lengths);
        $this->assertContains(5, $lengths);
        $this->assertContains(6, $lengths);
        $this->assertContains(7, $lengths);
        $this->assertContains(8, $lengths);
    }

    public function test_get_optimal_lengths_to_check_for_long_word(): void
    {
        $lengths = $this->optimizer->getOptimalLengthsToCheck(12);

        $this->assertContains(11, $lengths);
        $this->assertContains(12, $lengths);
        $this->assertContains(13, $lengths);
        $this->assertCount(3, $lengths);
    }

    public function test_get_or_build_optimized_indexes_creates_structures(): void
    {
        $wordIndex = [
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 1]],
            'world' => [['indexable_type' => 'User', 'indexable_id' => 2]],
            'test' => [['indexable_type' => 'User', 'indexable_id' => 3]],
        ];

        $optimized = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);

        $this->assertArrayHasKey('byLength', $optimized);
        $this->assertArrayHasKey('byFirstChar', $optimized);
        $this->assertArrayHasKey('trigramIndex', $optimized);

        $this->assertArrayHasKey(5, $optimized['byLength']);
        $this->assertArrayHasKey('h', $optimized['byFirstChar']);
        $this->assertArrayHasKey('w', $optimized['byFirstChar']);
        $this->assertArrayHasKey('t', $optimized['byFirstChar']);
    }

    public function test_get_or_build_optimized_indexes_skips_short_words(): void
    {
        $wordIndex = [
            'a' => [['indexable_type' => 'User', 'indexable_id' => 1]],
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 2]],
        ];

        $optimized = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);

        // 'a' (length 1) should be skipped
        $this->assertArrayNotHasKey(1, $optimized['byLength']);
        $this->assertArrayHasKey(5, $optimized['byLength']);
    }

    public function test_get_or_build_optimized_indexes_caches_results(): void
    {
        $wordIndex = [
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 1]],
        ];

        $firstCall = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);
        $secondCall = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);

        $this->assertSame($firstCall, $secondCall);
    }

    public function test_trigram_index_generation(): void
    {
        $wordIndex = [
            'hello' => [['indexable_type' => 'User', 'indexable_id' => 1]],
            'help' => [['indexable_type' => 'User', 'indexable_id' => 2]],
        ];

        $optimized = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);

        // 'hel' trigram should index both 'hello' and 'help'
        $this->assertArrayHasKey('hel', $optimized['trigramIndex']);
        $this->assertArrayHasKey('hello', $optimized['trigramIndex']['hel']);
        $this->assertArrayHasKey('help', $optimized['trigramIndex']['hel']);
    }

    public function test_config_can_be_injected(): void
    {
        $config = MatchDiscoveryConfig::fromConfig();
        $optimizer = new IndexOptimizer($config);

        $this->assertInstanceOf(IndexOptimizer::class, $optimizer);
    }
}
