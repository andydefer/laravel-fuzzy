<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Enums\IndexationLevel;
use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture for a model with CREATE_ONLY indexation level but shouldBeIndexed() = false.
 *
 * This model implements CREATE_ONLY level for auto-indexing on create events,
 * but shouldBeIndexed() returns false to prevent any indexing.
 * Used to test the priority relationship between IndexationLevel and shouldBeIndexed().
 */
class NonIndexableCreateOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
{
    use \Fuzzy\Traits\FuzzySearchable;

    protected $table = 'non_indexable_create_only_users';

    protected $fillable = ['name', 'email', 'type'];

    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::CREATE_ONLY;
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
        return false;
    }

    public function getProtectedFields(): array
    {
        return ['name', 'email'];
    }
}
