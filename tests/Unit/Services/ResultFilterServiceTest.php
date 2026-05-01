<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Services\ResultFilterService;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Collection;

final class ResultFilterServiceTest extends TestCase
{
    private ResultFilterService $resultFilter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resultFilter = new ResultFilterService();
    }

    public function test_filter_and_sort_returns_empty_collection_for_empty_input(): void
    {
        $results = collect();

        $filtered = $this->resultFilter->filterAndSort($results, 0.5);

        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertCount(0, $filtered);
    }

    public function test_filter_and_sort_filters_by_min_score(): void
    {
        $results = collect([
            (object) ['score' => 0.8, 'id' => 1],
            (object) ['score' => 0.3, 'id' => 2],
            (object) ['score' => 0.6, 'id' => 3],
            (object) ['score' => 0.1, 'id' => 4],
        ]);

        $filtered = $this->resultFilter->filterAndSort($results, 0.5);

        $this->assertCount(2, $filtered);
        $this->assertEquals(0.8, $filtered[0]->score);
        $this->assertEquals(0.6, $filtered[1]->score);
    }

    public function test_filter_and_sort_sorts_by_score_descending(): void
    {
        $results = collect([
            (object) ['score' => 0.6, 'id' => 1],
            (object) ['score' => 0.9, 'id' => 2],
            (object) ['score' => 0.7, 'id' => 3],
        ]);

        $filtered = $this->resultFilter->filterAndSort($results, 0.0);

        $this->assertEquals(0.9, $filtered[0]->score);
        $this->assertEquals(0.7, $filtered[1]->score);
        $this->assertEquals(0.6, $filtered[2]->score);
    }

    public function test_filter_and_sort_removes_null_results(): void
    {
        $results = collect([
            (object) ['score' => 0.8, 'id' => 1],
            null,
            (object) ['score' => 0.6, 'id' => 2],
        ]);

        $filtered = $this->resultFilter->filterAndSort($results, 0.0);

        $this->assertCount(2, $filtered);
    }

    public function test_filter_and_sort_preserves_result_properties(): void
    {
        $originalResult = (object) ['score' => 0.9, 'id' => 42, 'name' => 'Test'];
        $results = collect([$originalResult]);

        $filtered = $this->resultFilter->filterAndSort($results, 0.0);

        $this->assertEquals(42, $filtered[0]->id);
        $this->assertEquals('Test', $filtered[0]->name);
    }

    public function test_filter_and_sort_returns_values_reset_keys(): void
    {
        $results = collect([
            5 => (object) ['score' => 0.8, 'id' => 1],
            3 => (object) ['score' => 0.6, 'id' => 2],
        ]);

        $filtered = $this->resultFilter->filterAndSort($results, 0.0);

        $this->assertEquals(0, array_key_first($filtered->toArray()));
        $this->assertEquals(1, array_key_last($filtered->toArray()));
    }
}
