<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Data\FuzzySearchableData;
use Illuminate\Database\Eloquent\Model;

/**
 * Test fixture representing a model that is implement MustFuzzySearch.
 *
 * Used specifically for testing shouldBeIndexed() returning false.
 * This model implements all required interfaces but intentionally
 * prevents indexing for test scenarios.
 */
class NonSearchableModel extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'email', 'type'];
}
