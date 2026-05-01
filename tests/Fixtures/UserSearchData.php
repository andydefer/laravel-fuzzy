<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Data\FuzzySearchableData;
use Illuminate\Database\Eloquent\Model;

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
     * @return self
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
