<?php

declare(strict_types=1);

namespace LaravelFuzzy\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelFuzzy\Data\FuzzySearchableData;

interface MustFuzzySearch
{
    /**
     * Get the searchable fields configuration
     */
    public function getSearchableFields(): array;

    /**
     * Get the output format class (optional)
     */
    public function getFuzzyFormat(): ?string;

    /**
     * Get the model's display name for search results
     */
    public function getSearchableName(): string;

    /**
     * Get the model's unique identifier for indexing
     */
    public function getIndexableId(): string|int;

    /**
     * Get the model's type/category for grouping results
     */
    public function getSearchableType(): string;

    /**
     * Convert model to searchable data (optional, for custom transformation)
     */
    public function toSearchableData(): ?FuzzySearchableData;

    // Ajoute ceci
    public function getAttribute($key);
}
