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
     * The fields that should be searchable.
     *
     * @var array<string>
     */
    public array $searchableFields = ['name', 'email'];

    /**
     * Custom formatter class for search data.
     *
     * @var class-string<FuzzySearchableData>|null
     */
    public ?string $fuzzyFormat = UserSearchData::class;

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
