<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;
use Fuzzy\Data\FuzzySearchableData;

class User extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    protected $fillable = ['name', 'email', 'type'];

    public array $searchableFields = ['name', 'email']; // Ajout de 'public'

    public ?string $fuzzyFormat = UserSearchData::class;
}

class UserSearchData extends FuzzySearchableData
{
    public static function fromModel(Model $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            type: 'user',
            description: $user->email,
            url: "/users/{$user->id}",
            data: $user->toArray(),
        );
    }
}
