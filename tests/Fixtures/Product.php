<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    protected $fillable = ['name', 'description', 'price'];

    public array $searchableFields = ['name', 'description']; // Ajout de 'public'
}
