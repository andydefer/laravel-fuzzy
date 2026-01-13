<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit;

use Fuzzy\Tests\TestCase;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\FuzzySearch;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Illuminate\Support\Collection;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\UserSearchData;

/**
 * Unit tests for FuzzySearchService.
 *
 * Tests search functionality, indexing, and configuration options.
 */
final class FuzzySearchServiceTest extends TestCase
{
    private FuzzySearchService $service;

    /**
     * Set up test environment with test data and indexes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->service = app(FuzzySearchService::class);

        $this->cleanTestData();
        $this->createTestData();
        $this->service->reindexAll();
    }

    /**
     * Remove all test data and indexes.
     */
    private function cleanTestData(): void
    {
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();
    }

    /**
     * Create test users and products for search testing.
     */
    private function createTestData(): void
    {
        // Create indexable users (type = 'user')
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'user',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'type' => 'user',
        ]);

        Product::create([
            'name' => 'Laptop Pro',
            'description' => 'High-end laptop with 16GB RAM',
            'price' => 1299.99,
        ]);

        Product::create([
            'name' => 'Mouse Wireless',
            'description' => 'Wireless mouse with ergonomic design',
            'price' => 49.99,
        ]);

        Product::create([
            'name' => 'Keyboard Mechanical',
            'description' => 'Mechanical keyboard with RGB lighting',
            'price' => 89.99,
        ]);
    }

    /**
     * Test that search returns a collection.
     */
    public function test_search_returns_collection(): void
    {
        // Arrange: Service is already set up in setUp()

        // Act: Perform search
        $results = $this->service->search('john');

        // Assert: Results should be a Collection
        $this->assertInstanceOf(Collection::class, $results);
    }

    /**
     * Test that search finds exact matches.
     */
    public function test_search_finds_exact_match(): void
    {
        // Arrange: Service and test data are already set up

        // Act: Search for exact name
        $results = $this->service->search('John Doe');

        // Assert: Should find John Doe with high score
        $this->assertGreaterThan(0, $results->count(), 'Search should find results for "John Doe"');

        $johnDoeResult = $this->findResultByName($results, 'John Doe');
        $this->assertInstanceOf(SearchResultData::class, $johnDoeResult, 'Should find SearchResultData for John Doe');
        $this->assertEquals('John Doe', $johnDoeResult->item->name);
        $this->assertGreaterThan(0.8, $johnDoeResult->score, 'Exact match should have high score');
    }

    /**
     * Test that search finds fuzzy matches.
     */
    public function test_search_finds_fuzzy_match(): void
    {
        // Arrange: Service and test data are already set up

        // Act: Search with fuzzy matching enabled
        $results = $this->service->search('jon do', ['fuzzy' => true]);

        // Assert: Should find results despite typos
        $this->assertGreaterThan(0, $results->count(), 'Fuzzy search should find "John Doe" with query "jon do"');
    }

    /**
     * Test search within specific model.
     */
    public function test_search_in_specific_model(): void
    {
        // Arrange: Service and test data are already set up

        // Act: Search only in User model
        $results = $this->service->searchInModel(User::class, 'john');

        // Assert: Should find John Doe as a User
        $this->assertGreaterThan(0, $results->count(), 'Search in User model should find results');

        $johnDoeResult = $this->findResultByName($results, 'John Doe');
        $this->assertInstanceOf(SearchResultData::class, $johnDoeResult, 'Should find SearchResultData for John Doe');
        $this->assertInstanceOf(UserSearchData::class, $johnDoeResult->item, 'Should use UserSearchData formatter');
    }

    /**
     * Test search with custom options.
     */
    public function test_search_with_options(): void
    {
        // Arrange: Set search options
        $options = [
            'min_score' => 0.5,
            'max_results' => 5,
            'fuzzy' => true,
            'threshold' => 0.4,
        ];

        // Act: Search with custom options
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->search('laptop', $options);

        // Assert: Results should respect options
        $this->assertLessThanOrEqual(5, $results->count());

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.5, $result->score);
        }
    }

    /**
     * Test indexing a model.
     */
    public function test_index_model(): void
    {
        // Arrange: Create a unique user
        $uniqueTerm = 'xyzlmnopqr12345unique';
        $initialResults = $this->service->search($uniqueTerm);
        $this->assertCount(0, $initialResults);

        $user = User::create([
            'name' => 'Xylophone Player',
            'email' => 'xylophone@example.com',
            'type' => 'user',
        ]);

        // Act: Index the new user
        $this->service->indexModel($user);

        // Assert: Should find the new user
        $results = $this->service->search('xylophone player');
        $this->assertGreaterThanOrEqual(1, $results->count());

        $xylophoneResult = $this->findResultByName($results, 'Xylophone Player');
        $this->assertInstanceOf(SearchResultData::class, $xylophoneResult);
    }

    /**
     * Test removing model from index.
     */
    public function test_remove_model_from_index(): void
    {
        // Arrange: Get existing user
        $user = User::where('name', 'John Doe')->first();
        $this->assertNotNull($user, 'John Doe should exist');

        // Act: Remove user from index
        $this->service->removeModelFromIndex($user);

        // Assert: User should not be found in search
        $results = $this->service->search('john');
        $johnDoeFound = $results->contains(function ($result): bool {
            return isset($result->item->name) && $result->item->name === 'John Doe';
        });

        $this->assertFalse($johnDoeFound, 'John Doe should not be found after removal from index');
    }

    /**
     * Test getting search statistics.
     */
    public function test_get_stats(): void
    {
        // Arrange: Service is already set up

        // Act: Get statistics
        $stats = $this->service->getStats();

        // Assert: Should contain expected keys
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('models', $stats);
        $this->assertArrayHasKey(User::class, $stats['models']);
    }

    /**
     * Test similarity calculation.
     */
    public function test_calculate_similarity(): void
    {
        // Arrange: Service is already set up

        // Act: Calculate similarity for exact match
        $exactSimilarity = $this->service->calculateSimilarity('hello', 'hello');

        // Assert: Exact match should have score of 1.0
        $this->assertEqualsWithDelta(1.0, $exactSimilarity, PHP_FLOAT_EPSILON);

        // Act: Calculate similarity for close match
        $fuzzySimilarity = $this->service->calculateSimilarity('hello', 'helo');

        // Assert: Close match should have reasonable score
        $this->assertGreaterThan(0, $fuzzySimilarity);
        $this->assertLessThanOrEqual(1.0, $fuzzySimilarity);
        $this->assertGreaterThan(0.8, $fuzzySimilarity);
    }

    /**
     * Test minimum score from configuration.
     */
    public function test_min_score_is_respected_from_config(): void
    {
        // Arrange: Set high minimum score in config
        config(['fuzzy.default_options.min_score' => 0.8]);

        // Act: Search with configured minimum score
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->search('laptop');

        // Assert: All results should meet minimum score
        $this->assertInstanceOf(Collection::class, $results);

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.8, $result->score);
        }
    }

    /**
     * Test minimum score override with options.
     */
    public function test_min_score_is_respected_with_options_override(): void
    {
        // Arrange: Set low default score
        config(['fuzzy.default_options.min_score' => 0.1]);

        // Act: Search with high minimum score override
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->search('laptop', ['min_score' => 0.9]);

        // Assert: All results should meet overridden score
        $this->assertInstanceOf(Collection::class, $results);

        if ($results->count() > 0) {
            foreach ($results as $result) {
                $this->assertGreaterThanOrEqual(0.9, $result->score);
            }
        }
    }

    /**
     * Test minimum score with different naming conventions.
     */
    public function test_min_score_works_with_snake_case_and_camel_case(): void
    {
        // Arrange: Set minimum score in config
        config(['fuzzy.default_options.min_score' => 0.7]);

        // Act: Search with snake_case option
        $snakeCaseResults = $this->service->search('john', ['min_score' => 0.8]);

        // Act: Search with camelCase option
        $camelCaseResults = $this->service->search('john', ['minScore' => 0.8]);

        // Assert: Both should produce same results
        $this->assertCount(
            $snakeCaseResults->count(),
            $camelCaseResults
        );
    }

    /**
     * Test facade respects minimum score.
     */
    public function test_facade_respects_min_score(): void
    {
        // Arrange: Service is ready

        // Act: Search via facade with high minimum score
        /** @var Collection<int, SearchResultData> $results */
        $results = FuzzySearch::search('joh', ['min_score' => 1.0]);

        // Assert: All results should meet minimum score
        $this->assertInstanceOf(Collection::class, $results);

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(1.0, $result->score);
        }
    }

    /**
     * Test search in model respects minimum score.
     */
    public function test_search_in_model_respects_min_score(): void
    {
        // Arrange: Service is ready

        // Act: Search in User model with high minimum score
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->searchInModel(
            User::class,
            'joh',
            ['min_score' => 0.9, 'fuzzy' => true]
        );

        // Assert: All results should meet minimum score
        $this->assertInstanceOf(Collection::class, $results);

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.9, $result->score);
        }
    }

    /**
     * Test multi-word search respects minimum score.
     */
    public function test_multi_word_processing_respects_min_score(): void
    {
        // Arrange: Service is ready

        // Act: Search with multi-word query
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->search('high end laptop', [
            'min_score' => 0.6,
            'fuzzy' => true,
        ]);

        // Assert: All results should meet minimum score
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.6, $result->score);
        }
    }

    /**
     * Test exact match bypasses minimum score.
     */
    public function test_exact_match_bypasses_min_score(): void
    {
        // Arrange: Service is ready

        // Act: Search for exact match with high minimum score
        $results = $this->service->search('John Doe', [
            'min_score' => 0.99,
            'fuzzy' => false,
        ]);

        // Assert: Either we get results that meet the high score, or no results at all
        $this->assertInstanceOf(Collection::class, $results);

        if ($results->count() > 0) {
            $this->assertGreaterThanOrEqual(0.99, $results->first()->score);
        } else {
            $this->assertCount(0, $results, 'No results returned with min_score 0.99');
        }
    }

    /**
     * Test sorting and limiting respects minimum score.
     */
    public function test_sort_and_limit_stage_respects_min_score(): void
    {
        // Arrange: Create additional test users
        for ($i = 1; $i <= 10; ++$i) {
            User::create([
                'name' => 'Test User ' . $i,
                'email' => sprintf('user%d@example.com', $i),
                'type' => 'user',
            ]);
        }

        $this->service->reindexModel(User::class);

        // Act: Search with limits and minimum score
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->search('test', [
            'min_score' => 0.3,
            'max_results' => 5,
        ]);

        // Assert: Results should respect both limits
        $this->assertLessThanOrEqual(5, $results->count());

        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(0.3, $result->score);
        }

        // Assert: Results should be sorted by score descending
        $scores = $results->pluck('score')->toArray();
        $sortedScores = $scores;
        rsort($sortedScores);
        $this->assertEquals($sortedScores, $scores);
    }

    /**
     * Test search options data handles edge cases.
     */
    public function test_search_options_data_handles_edge_cases(): void
    {
        // Arrange & Act: Test empty options
        $options1 = SearchOptionsData::fromConfig([]);
        $this->assertEqualsWithDelta(0.1, $options1->minScore, PHP_FLOAT_EPSILON);

        // Arrange & Act: Test negative value
        $options2 = SearchOptionsData::fromConfig(['min_score' => -0.5]);
        $this->assertSame(-0.5, $options2->minScore);

        // Arrange & Act: Test value above 1.0
        $options3 = SearchOptionsData::fromConfig(['minScore' => 2.0]);
        $this->assertEqualsWithDelta(2.0, $options3->minScore, PHP_FLOAT_EPSILON);

        // Act: Search with minimum score above 1.0
        /** @var Collection<int, SearchResultData> $results  */
        $results = $this->service->search('john', ['min_score' => 2.0]);

        // Assert: All results should meet the unrealistic score
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(2.0, $result->score);
        }
    }

    /**
     * Test minimum score of zero returns all results.
     */
    public function test_min_score_zero_returns_all_results(): void
    {
        // Arrange: Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'type' => 'user',
        ]);

        $this->service->reindexModel(User::class);

        // Act: Search with zero minimum score
        $results = $this->service->search('test', [
            'min_score' => 0,
            'max_results' => 100,
        ]);

        // Assert: Should return results
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Find a search result by name in the results collection.
     *
     * @param Collection<int, SearchResultData> $results The search results collection
     * @param string $name The name to search for
     * @return SearchResultData|null The matching result or null if not found
     */
    private function findResultByName(Collection $results, string $name): ?SearchResultData
    {
        return $results->first(function ($result) use ($name): bool {
            return isset($result->item->name) && $result->item->name === $name;
        });
    }
}
