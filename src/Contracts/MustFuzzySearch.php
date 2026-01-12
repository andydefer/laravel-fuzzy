<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for models that support fuzzy search indexing and searching.
 *
 * Models implementing this interface can be indexed, searched,
 * and retrieved through the fuzzy search system with custom configuration.
 */
interface MustFuzzySearch
{
    /**
     * Get the model fields to be indexed for fuzzy searching.
     *
     * @return array<int, string> Attribute names to include in search index.
     */
    public function getSearchableFields(): array;

    /**
     * Get the custom formatter class for model data transformation during indexing.
     *
     * @return class-string|null Fully qualified class name implementing static `fromModel()` method.
     */
    public function getFuzzyFormat(): ?string;

    /**
     * Get the unique identifier for model indexing.
     *
     * @return string|int Unique identifier, typically model's primary key.
     */
    public function getIndexableId(): string|int;

    /**
     * Determine if the model should be included in search index.
     *
     * @return bool True to index model, false to exclude (e.g., inactive, draft, archived).
     */
    public function shouldBeIndexed(): bool;

    /**
     * Get model attribute value.
     *
     * Required for interface completeness with Eloquent Model interaction.
     *
     * @param string $key Attribute name to retrieve.
     * @return mixed Attribute value.
     */
    public function getAttribute($key);
}
