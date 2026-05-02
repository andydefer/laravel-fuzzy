<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Enums\IndexationLevel;
use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture for a model with UPDATE_ONLY indexation level but shouldBeIndexed() = false.
 *
 * This model implements UPDATE_ONLY level for auto-indexing on update events,
 * but shouldBeIndexed() returns false to prevent any indexing.
 * Used to test the priority relationship between IndexationLevel and shouldBeIndexed().
 */
class NonIndexableUpdateOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
{
    use \Fuzzy\Traits\FuzzySearchable;

    protected $table = 'non_indexable_update_only_users';

    protected $fillable = ['name', 'email', 'type'];

    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::UPDATE_ONLY;
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
