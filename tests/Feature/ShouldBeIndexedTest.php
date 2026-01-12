<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Feature;

use Fuzzy\Tests\TestCase;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

final class ShouldBeIndexedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        FuzzyIndex::query()->truncate();
    }

    public function test_default_should_be_indexed_returns_true(): void
    {
        // Arrange
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        // Act & Assert
        $this->assertTrue($user->shouldBeIndexed());
    }

    public function test_custom_should_be_indexed_logic(): void
    {
        // Arrange - Créer un modèle avec logique personnalisée
        $model = new class extends Model implements MustFuzzySearch {
            use FuzzySearchable;

            protected $table = 'users';

            protected $fillable = ['name', 'email', 'status'];

            /**
             * @var string[]
             */
            public array $searchableFields = ['name', 'email'];

            public $status = 'inactive';

            public function shouldBeIndexed(): bool
            {
                return $this->status === 'active';
            }

            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            public function getIndexableId(): string|int
            {
                return $this->id ?? 1;
            }
        };

        // Créer l'instance
        $model->id = 1;
        $model->name = 'Test User';
        $model->email = 'test@example.com';

        // Act & Assert - Statut inactive
        $model->status = 'inactive';
        $this->assertFalse($model->shouldBeIndexed());

        // Act & Assert - Statut active
        $model->status = 'active';
        $this->assertTrue($model->shouldBeIndexed());
    }

    public function test_should_be_indexed_prevents_indexing(): void
    {
        // Arrange
        $user = new class extends User {
            protected $table = 'users';

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

        // Act
        $searchService->indexModel($user);

        // Assert - Ne devrait pas être indexé
        $entry = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', 1)
            ->first();

        $this->assertNull($entry);
    }

    public function test_should_be_indexed_allows_indexing(): void
    {
        // Arrange
        $user = new class extends User {
            protected $table = 'users';

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

        // Act
        $searchService->indexModel($user);

        // Assert - Devrait être indexé
        $entries = FuzzyIndex::where('indexable_type', get_class($user))
            ->where('indexable_id', 1)
            ->get();

        $this->assertCount(2, $entries); // name et email
    }

    public function test_should_be_indexed_with_conditions(): void
    {
        // Arrange
        $product = new class extends Model implements MustFuzzySearch {
            use FuzzySearchable;

            protected $table = 'products';

            protected $fillable = ['name', 'price', 'stock', 'is_active'];

            /**
             * @var string[]
             */
            public array $searchableFields = ['name'];

            public $stock = 0;

            public $is_active = false;

            public function shouldBeIndexed(): bool
            {
                return $this->is_active && $this->stock > 0;
            }

            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            public function getIndexableId(): string|int
            {
                return $this->id ?? 1;
            }
        };

        $product->id = 1;
        $product->name = 'Test Product';
        $product->price = 100;

        $searchService = app('laravel-fuzzy.search');

        // Test 1: Produit inactif
        $product->is_active = false;
        $product->stock = 10;

        $searchService->indexModel($product);

        $entry1 = FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($entry1);

        // Test 2: Produit sans stock
        FuzzyIndex::query()->truncate();
        $product->is_active = true;
        $product->stock = 0;

        $searchService->indexModel($product);

        $entry2 = FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($entry2);

        // Test 3: Produit actif avec stock
        FuzzyIndex::query()->truncate();
        $product->is_active = true;
        $product->stock = 5;

        $searchService->indexModel($product);

        $entry3 = FuzzyIndex::where('indexable_type', get_class($product))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNotNull($entry3);
    }

    public function test_should_be_indexed_with_date_condition(): void
    {
        // Arrange - Article avec date de publication
        $article = new class extends Model implements MustFuzzySearch {
            use FuzzySearchable;

            protected $table = 'products';

            // Réutiliser la table products
            protected $fillable = ['name', 'published_at', 'status'];

            /**
             * @var string[]
             */
            public array $searchableFields = ['name'];

            public $status = 'draft';

            public $published_at;

            public function shouldBeIndexed(): bool
            {
                return $this->status === 'published'
                    && $this->published_at
                    && $this->published_at <= now();
            }

            public function getFuzzyFormat(): ?string
            {
                return null;
            }

            public function getIndexableId(): string|int
            {
                return $this->id ?? 1;
            }
        };

        $article->id = 1;
        $article->name = 'Test Article';

        $searchService = app('laravel-fuzzy.search');

        // Test 1: Article brouillon
        $article->status = 'draft';
        $article->published_at = now()->addDay();

        $searchService->indexModel($article);

        $entry1 = FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($entry1);

        // Test 2: Article publié dans le futur
        FuzzyIndex::query()->truncate();
        $article->status = 'published';
        $article->published_at = now()->addDay();

        $searchService->indexModel($article);

        $entry2 = FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNull($entry2);

        // Test 3: Article publié dans le passé
        FuzzyIndex::query()->truncate();
        $article->status = 'published';
        $article->published_at = now()->subDay();

        $searchService->indexModel($article);

        $entry3 = FuzzyIndex::where('indexable_type', get_class($article))
            ->where('indexable_id', 1)
            ->first();
        $this->assertNotNull($entry3);
    }
}
