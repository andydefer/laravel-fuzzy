<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Data\FuzzySearchableData;

interface MustFuzzySearch
{
    /**
     * Get the searchable fields configuration for the model.
     *
     * Should return an array where keys are field names and values are their weights.
     * Example: ['name', 'description']
     *
     * @return array
     */
    public function getSearchableFields(): array;

    /**
     * Get the output formatter class for custom search result formatting.
     *
     * @return class-string|null The fully qualified class name of the formatter,
     *                           or null to use default formatting.
     */
    public function getFuzzyFormat(): ?string;

    /**
     * Get the display name for this model in search results.
     *
     * @return string The human-readable name to display in search results.
     */
    public function getSearchableName(): string;

    /**
     * Get the unique identifier for this model used in indexing.
     *
     * @return string|int The model's unique identifier (typically primary key).
     */
    public function getIndexableId(): string|int;

    /**
     * Get the model's type/category for grouping and filtering search results.
     *
     * @return string The model type (e.g., 'user', 'product', 'article').
     */
    public function getSearchableType(): string;

    /**
     * Determine if this specific model instance should be indexed.
     *
     * Useful for excluding soft-deleted, draft, or unpublished models.
     *
     * @return bool True if the model should be indexed, false otherwise.
     */
    public function shouldBeIndexed(): bool;

    /**
     * Convert the model to searchable data for custom transformation.
     *
     * Allows models to provide custom data transformation before indexing.
     * Return null to use default transformation.
     *
     * @return FuzzySearchableData|null Custom searchable data or null for default.
     */
    public function toSearchableData(): ?FuzzySearchableData;

    /**
     * Get a model attribute value.
     *
     * This method is inherited from Eloquent Model but declared here
     * for interface completeness when working with searchable models.
     *
     * @param string $key The attribute name.
     * @return mixed The attribute value.
     */
    public function getAttribute($key);
}
