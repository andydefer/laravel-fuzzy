<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

/**
 * Tests for the shouldBeIndexed method functionality.
 *
 * This test suite verifies that models can control their indexing behavior
 * through the shouldBeIndexed method, preventing unwanted records from
 * being added to the search index.
 */
final class ShouldBeIndexedTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
    }

    /**
     * Test that default shouldBeIndexed returns true.
     */
    public function test_default_should_be_indexed_returns_true(): void
    {
        // Arrange: Create a user instance
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        // Act & Assert: Verify default behavior returns true
        $this->assertTrue($user->shouldBeIndexed());
    }

    /**
     * Test custom shouldBeIndexed logic.
     */
    public function test_custom_should_be_indexed_logic(): void
    {
        // Arrange: Create model with custom indexing logic based on status
        $model = new class extends Model implements MustFuzzySearch {
            use FuzzySearchable;

            protected $table = 'users';
            protected $fillable = ['name', 'email', 'status'];

            /** @var string[] */
            public array $searchableFields = ['name', 'email'];

            public $status = 'inactive';

            /**
             * Determine if the model should be indexed based on status.
             */
            public function shouldBeIndexed(): bool
            {
                return $this->status === 'active';
            }

            /**
             * Get the fuzzy format for the model.
             */
            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            /**
             * Get the indexable ID for the model.
             */
            public function getIndexableId(): string|int
            {
                return $this->id ?? 1;
            }
        };

        $model->id = 1;
        $model->name = 'Test User';
        $model->email = 'test@example.com';

        // Act & Assert: Test inactive status prevents indexing
        $model->status = 'inactive';
        $this->assertFalse($model->shouldBeIndexed());

        // Act & Assert: Test active status allows indexing
        $model->status = 'active';
        $this->assertTrue($model->shouldBeIndexed());
    }

    /**
     * Test that shouldBeIndexed prevents indexing when returning false.
     */
    public function test_should_be_indexed_prevents_indexing(): void
    {
        // Arrange: Create user subclass with admin-only indexing
        $user = new class extends User {
            protected $table = 'users';

            /**
             * Only index admin users.
             */
            public function shouldBeIndexed(): bool
            {
                return $this->type === 'admin';
            }
        };

        $user->id = 1;
        $user->name = 'Regular User';
        $user->email = 'regular@example.com';
        $user->type = 'user';

        $searchService = app('laravel-fuzzy.search');

        // Act: Attempt to index the user
        $searchService->indexModel($user);

        // Assert: No index entry should be created for non-admin user
        $entry = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', 1)
            ->first();

        $this->assertNull($entry);
    }

    /**
     * Test that shouldBeIndexed allows indexing when returning true.
     */
    public function test_should_be_indexed_allows_indexing(): void
    {
        // Arrange: Create user subclass with admin-only indexing
        $user = new class extends User {
            protected $table = 'users';

            /**
             * Only index admin users.
             */
            public function shouldBeIndexed(): bool
            {
                return $this->type === 'admin';
            }
        };

        $user->id = 1;
        $user->name = 'Admin User';
        $user->email = 'admin@example.com';
        $user->type = 'admin';

        $searchService = app('laravel-fuzzy.search');

        // Act: Index the admin user
        $searchService->indexModel($user);

        // Assert: Two index entries should be created (name and email)
        $entries = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', 1)
            ->get();

        $this->assertCount(2, $entries);
    }

    /**
     * Test shouldBeIndexed with multiple conditions.
     */
    public function test_should_be_indexed_with_conditions(): void
    {
        // Arrange: Create product model requiring both active status and stock
        $product = new class extends Model implements MustFuzzySearch {
            use FuzzySearchable;

            protected $table = 'products';
            protected $fillable = ['name', 'price', 'stock', 'is_active'];

            /** @var string[] */
            public array $searchableFields = ['name'];

            public $stock = 0;
            public $is_active = false;

            /**
             * Index only active products with stock available.
             */
            public function shouldBeIndexed(): bool
            {
                return $this->is_active && $this->stock > 0;
            }

            /**
             * Get the fuzzy format for the model.
             */
            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            /**
             * Get the indexable ID for the model.
             */
            public function getIndexableId(): string|int
            {
                return $this->id ?? 1;
            }
        };

        $product->id = 1;
        $product->name = 'Test Product';
        $product->price = 100;

        $searchService = app('laravel-fuzzy.search');

        // Test 1: Inactive product with stock
        $product->is_active = false;
        $product->stock = 10;
        $searchService->indexModel($product);

        $inactiveEntry = FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($inactiveEntry);

        // Test 2: Active product without stock
        FuzzyIndex::query()->truncate();
        $product->is_active = true;
        $product->stock = 0;
        $searchService->indexModel($product);

        $outOfStockEntry = FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($outOfStockEntry);

        // Test 3: Active product with stock
        FuzzyIndex::query()->truncate();
        $product->is_active = true;
        $product->stock = 5;
        $searchService->indexModel($product);

        $availableEntry = FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNotNull($availableEntry);
    }

    /**
     * Test shouldBeIndexed with date conditions.
     */
    public function test_should_be_indexed_with_date_condition(): void
    {
        // Arrange: Create article model with publication date logic
        $article = new class extends Model implements MustFuzzySearch {
            use FuzzySearchable;

            protected $table = 'products';
            protected $fillable = ['name', 'published_at', 'status'];

            /** @var string[] */
            public array $searchableFields = ['name'];

            public $status = 'draft';
            public $published_at;

            /**
             * Index only published articles with past publication date.
             */
            public function shouldBeIndexed(): bool
            {
                return $this->status === 'published'
                    && $this->published_at
                    && $this->published_at <= now();
            }

            /**
             * Get the fuzzy format for the model.
             */
            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            /**
             * Get the indexable ID for the model.
             */
            public function getIndexableId(): string|int
            {
                return $this->id ?? 1;
            }
        };

        $article->id = 1;
        $article->name = 'Test Article';

        $searchService = app('laravel-fuzzy.search');

        // Test 1: Draft article with future date
        $article->status = 'draft';
        $article->published_at = now()->addDay();
        $searchService->indexModel($article);

        $draftEntry = FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($draftEntry);

        // Test 2: Published article with future date
        FuzzyIndex::query()->truncate();
        $article->status = 'published';
        $article->published_at = now()->addDay();
        $searchService->indexModel($article);

        $futureEntry = FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($futureEntry);

        // Test 3: Published article with past date
        FuzzyIndex::query()->truncate();
        $article->status = 'published';
        $article->published_at = now()->subDay();
        $searchService->indexModel($article);

        $publishedEntry = FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNotNull($publishedEntry);
    }
}
