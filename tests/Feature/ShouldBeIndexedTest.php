<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;
use Fuzzy\Traits\FuzzySearchable;
use Illuminate\Database\Eloquent\Model;

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
     * Test that default shouldBeIndexed returns true for users with type 'user'.
     */
    public function test_default_should_be_indexed_returns_true_for_users(): void
    {
        // Arrange: Create a user instance with type 'user'
        $user = $this->createUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'type' => 'user',
        ]);

        // Act & Assert: Verify default behavior returns true for type 'user'
        $this->assertTrue($user->shouldBeIndexed());
    }

    /**
     * Test that shouldBeIndexed returns false for users with type 'admin'.
     */
    public function test_should_be_indexed_returns_false_for_admins(): void
    {
        // Arrange: Create a user instance with type 'admin'
        $user = $this->createUser([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'admin',
        ]);

        // Act & Assert: Verify returns false for type 'admin'
        $this->assertFalse($user->shouldBeIndexed());
    }

    /**
     * Test custom shouldBeIndexed logic.
     */
    public function test_custom_should_be_indexed_logic(): void
    {
        // Arrange: Create model with custom indexing logic based on status
        $model = $this->createCustomModel();

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
        // Arrange: Create user with type 'admin' (non indexable)
        $user = $this->createUser([
            'id' => 1,
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'type' => 'admin',
        ]);

        $searchService = app('laravel-fuzzy.search');

        // Act: Attempt to index the user
        $searchService->indexModel($user);

        // Assert: No index entry should be created for non-indexable user
        $entry = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', 1)
            ->first();

        $this->assertNull($entry);
    }

    /**
     * Test that shouldBeIndexed allows indexing when returning true.
     */
    public function test_should_be_indexed_allows_indexing(): void
    {
        // Arrange: Create user with type 'user' (indexable)
        $user = $this->createUser([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'type' => 'user',
        ]);

        $searchService = app('laravel-fuzzy.search');

        // Act: Index the user
        $searchService->indexModel($user);

        // Assert: Two index entries should be created (name and email)
        $entries = FuzzyIndex::where('indexable_type', User::class)
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
        $product = $this->createProductModel();
        $searchService = app('laravel-fuzzy.search');

        // Test 1: Inactive product with stock
        $this->setProductState($product, false, 10);
        $searchService->indexModel($product);

        $inactiveEntry = $this->getProductEntry($product);
        $this->assertNull($inactiveEntry);

        // Test 2: Active product without stock
        FuzzyIndex::query()->truncate();
        $this->setProductState($product, true, 0);
        $searchService->indexModel($product);

        $outOfStockEntry = $this->getProductEntry($product);
        $this->assertNull($outOfStockEntry);

        // Test 3: Active product with stock
        FuzzyIndex::query()->truncate();
        $this->setProductState($product, true, 5);
        $searchService->indexModel($product);

        $availableEntry = $this->getProductEntry($product);
        $this->assertNotNull($availableEntry);
    }

    /**
     * Test shouldBeIndexed with date conditions.
     */
    public function test_should_be_indexed_with_date_condition(): void
    {
        // Arrange: Create article model with publication date logic
        $article = $this->createArticleModel();
        $searchService = app('laravel-fuzzy.search');

        // Test 1: Draft article with future date
        $this->setArticleState($article, 'draft', now()->addDay());
        $searchService->indexModel($article);

        $draftEntry = $this->getArticleEntry($article);
        $this->assertNull($draftEntry);

        // Test 2: Published article with future date
        FuzzyIndex::query()->truncate();
        $this->setArticleState($article, 'published', now()->addDay());
        $searchService->indexModel($article);

        $futureEntry = $this->getArticleEntry($article);
        $this->assertNull($futureEntry);

        // Test 3: Published article with past date
        FuzzyIndex::query()->truncate();
        $this->setArticleState($article, 'published', now()->subDay());
        $searchService->indexModel($article);

        $publishedEntry = $this->getArticleEntry($article);
        $this->assertNotNull($publishedEntry);
    }

    /**
     * Create a user instance with given attributes.
     */
    private function createUser(array $attributes): User
    {
        $user = new User();

        foreach ($attributes as $key => $value) {
            $user->$key = $value;
        }

        return $user;
    }

    /**
     * Create a custom model with status-based indexing logic.
     */
    private function createCustomModel(): Model
    {
        return new class extends Model implements MustFuzzySearch {
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
    }

    /**
     * Create a product model with stock and active status conditions.
     */
    private function createProductModel(): Model
    {
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

        return $product;
    }

    /**
     * Create an article model with publication date logic.
     */
    private function createArticleModel(): Model
    {
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

        return $article;
    }

    /**
     * Set product state for testing.
     */
    private function setProductState(Model $product, bool $isActive, int $stock): void
    {
        $product->is_active = $isActive;
        $product->stock = $stock;
    }

    /**
     * Set article state for testing.
     */
    private function setArticleState(Model $article, string $status, \DateTimeInterface $publishedAt): void
    {
        $article->status = $status;
        $article->published_at = $publishedAt;
    }

    /**
     * Get product index entry from database.
     */
    private function getProductEntry(Model $product): ?FuzzyIndex
    {
        return FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
    }

    /**
     * Get article index entry from database.
     */
    private function getArticleEntry(Model $article): ?FuzzyIndex
    {
        return FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
    }
}
