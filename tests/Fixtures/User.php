<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;
use Fuzzy\Data\FuzzySearchableData;

/**
 * Test fixture representing a User model with fuzzy search capabilities.
 *
 * Used for testing the fuzzy search functionality with custom data formatting.
 */
class User extends Model implements MustFuzzySearch
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
     */
    public ?string $fuzzyFormat = UserSearchData::class;
}

/**
 * Custom search data formatter for User model.
 *
 * Transforms User model instances into structured search data with specific
 * field mappings and formatting.
 */
class UserSearchData extends FuzzySearchableData
{
    /**
     * Create a search data instance from a User model.
     *
     * @param Model $user The User model instance
     */
    public static function fromModel(Model $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            type: 'user',
            data: $user->toArray(),
            description: $user->email,
            url: '/users/' . $user->id,
        );
    }
}
