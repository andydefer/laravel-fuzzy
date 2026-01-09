<?php

declare(strict_types=1);

namespace LaravelFuzzy\Tests\Unit;

use LaravelFuzzy\Tests\TestCase;
use LaravelFuzzy\Services\FuzzySearchService;
use LaravelFuzzy\Data\SearchOptionsData;
use LaravelFuzzy\Tests\Fixtures\User;
use LaravelFuzzy\Tests\Fixtures\Product;

class FuzzySearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create test models
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'admin',
        ]);

        Product::create([
            'name' => 'Laptop Pro',
            'description' => 'High-end laptop with 16GB RAM',
            'price' => 1299.99,
        ]);
    }

    public function test_search_returns_collection(): void
    {
        $service = app(FuzzySearchService::class);

        $results = $service->search('john');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
    }

    public function test_search_finds_exact_match(): void
    {
        $service = app(FuzzySearchService::class);

        $results = $service->search('John Doe');

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->item->name);
    }

    public function test_search_finds_fuzzy_match(): void
    {
        $service = app(FuzzySearchService::class);

        $results = $service->search('jon do', new SearchOptionsData(fuzzy: true));

        $this->assertGreaterThan(0, $results->count());
    }

    public function test_search_in_specific_model(): void
    {
        $service = app(FuzzySearchService::class);

        $results = $service->searchInModel(User::class, 'john');

        $this->assertCount(1, $results);
        $this->assertEquals(User::class, get_class($results->first()->item));
    }

    public function test_search_with_options(): void
    {
        $service = app(FuzzySearchService::class);

        $options = new SearchOptionsData(
            minScore: 0.5,
            maxResults: 5,
            fuzzy: true,
            threshold: 0.4
        );

        /** @var Collection<int, SearchResultData> $results  */
        $results = $service->search('laptop', $options);

        $this->assertLessThanOrEqual(5, $results->count());

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score);
        }
    }

    public function test_index_model(): void
    {
        $service = app(FuzzySearchService::class);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        $service->indexModel($user);

        $results = $service->search('test user');

        $this->assertCount(1, $results);
    }

    public function test_remove_model_from_index(): void
    {
        $service = app(FuzzySearchService::class);

        $user = User::first();

        $service->removeModelFromIndex($user);

        $results = $service->search('john');

        $this->assertCount(0, $results);
    }

    public function test_get_stats(): void
    {
        $service = app(FuzzySearchService::class);

        $stats = $service->getStats();

        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);
        $this->assertArrayHasKey(User::class, $stats['models']);
    }

    public function test_calculate_similarity(): void
    {
        $service = app(FuzzySearchService::class);

        $similarity = $service->calculateSimilarity('hello', 'hello');

        $this->assertEquals(1.0, $similarity);

        $similarity2 = $service->calculateSimilarity('hello', 'helo');
        $this->assertGreaterThan(0, $similarity2);
        $this->assertLessThan(1.0, $similarity2);
    }
}
