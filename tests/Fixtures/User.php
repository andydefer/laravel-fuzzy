<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

/**
 * Test fixture representing a User model with fuzzy search capabilities.
 *
 * Used for testing the fuzzy search functionality with custom data formatting.
 */
class User extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'email', 'type'];

    /**
     * The fields that should be searchable.
     *
     * @return array<int, string>
     */
    public function getSearchableFields(): array
    {
        return ['name', 'email'];
    }

    /**
     * Custom formatter class for search data.
     *
     * @return class-string|null
     */
    public function getFuzzyFormat(): ?string
    {
        return UserSearchData::class;
    }

    /**
     * Determine if the model should be indexed for search.
     *
     * @return bool
     */
    public function shouldBeIndexed(): bool
    {
        return $this->type === 'user';
    }

    /**
     * Protected fields that should preserve stop words.
     *
     * For users, name and email should preserve stop words to maintain
     * search accuracy for names like "Jean de La Fontaine".
     *
     * @return array<int, string>
     */
    public function getProtectedFields(): array
    {
        return ['name', 'email'];
    }
}
