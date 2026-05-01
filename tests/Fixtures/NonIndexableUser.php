<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Data\FuzzySearchableData;
use Fuzzy\Traits\FuzzySearchable;
use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture representing a User model that is NOT indexable.
 *
 * Used specifically for testing shouldBeIndexed() returning false.
 * This model implements all required interfaces but intentionally
 * prevents indexing for test scenarios.
 */
class NonIndexableUser extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'email', 'type'];

    /**
     * Get the searchable fields for the model.
     *
     * @return array<int, string>
     */
    public function getSearchableFields(): array
    {
        return ['name', 'email'];
    }

    /**
     * Get the custom formatter class for search data.
     *
     * @return class-string<FuzzySearchableData>|null
     */
    public function getFuzzyFormat(): ?string
    {
        return UserSearchData::class;
    }

    /**
     * Determine if the model should be indexed for search.
     *
     * This model intentionally returns false to test scenarios
     * where models implement the interface but should not be indexed.
     *
     * @return bool Always returns false to prevent indexing
     */
    public function shouldBeIndexed(): bool
    {
        return false;
    }
}
