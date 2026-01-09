<?php

declare(strict_types=1);

namespace LaravelFuzzy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaravelFuzzy\Contracts\MustFuzzySearch;
use LaravelFuzzy\Traits\FuzzySearchable;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    protected $fillable = ['name', 'description', 'price'];

    public array $searchableFields = ['name', 'description'];
}
