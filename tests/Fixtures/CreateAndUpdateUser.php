<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Enums\IndexationLevel;
use Illuminate\Database\Eloquent\Model;

class CreateAndUpdateUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
{
    use \Fuzzy\Traits\FuzzySearchable;

    protected $table = 'create_and_update_users';

    protected $fillable = ['name', 'email', 'type'];

    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::CREATE_AND_UPDATE;
    }

    public function getSearchableFields(): array
    {
        return ['name', 'email'];
    }

    public function getFuzzyFormat(): ?string
    {
        return UserSearchData::class;
    }

    public function shouldBeIndexed(): bool
    {
        return $this->type === 'user';
    }

    public function getProtectedFields(): array
    {
        return ['name', 'email'];
    }
}
