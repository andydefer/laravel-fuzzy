<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;
use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture representing a Product model with fuzzy search capabilities.
 *
 * Used for testing the fuzzy search functionality with basic searchable fields.
 */
class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'description', 'price'];

    /**
     * Get the searchable fields for the model.
     *
     * @return array<int, string>
     */
    public function getSearchableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Get the custom formatter class for search data.
     *
     * @return class-string|null
     */
    public function getFuzzyFormat(): ?string
    {
        return null; // Utiliser le format par défaut
    }

    /**
     * Determine if the model should be indexed for search.
     *
     * @return bool
     */
    public function shouldBeIndexed(): bool
    {
        return true;
    }
}
