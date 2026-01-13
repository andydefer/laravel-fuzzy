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
     * The fields that should be searchable.
     *
     * @var array<string>
     */
    public array $searchableFields = ['name', 'description'];

    /**
     * Get the searchable fields for the model.
     *
     * @return array<string>
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
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
