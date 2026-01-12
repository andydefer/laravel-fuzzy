<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface defining the contract for models that support fuzzy search indexing.
 *
 * Models implementing this interface can be indexed and searched
 * through the package's fuzzy search system.
 */
interface MustFuzzySearch
{
    /**
     * Get the list of model fields that should be indexed for search.
     *
     * @return array<string> The model attribute names to be indexed.
     */
    public function getSearchableFields(): array;

    /**
     * Specify a custom formatting class to transform the model during indexing.
     *
     * The returned class must implement a static `fromModel($model)` method.
     * If null is returned, the default formatting will be used.
     *
     * @return class-string|null The FQCN of the formatting class or null.
     */
    public function getFuzzyFormat(): ?string;

    /**
     * Get the unique identifier used for model indexing.
     *
     * @return string|int The unique identifier (typically the primary key).
     */
    public function getIndexableId(): string|int;


    /**
     * Determine whether the model should be included in the search index.
     *
     * Allows dynamic exclusion of certain models from indexing
     * (e.g., inactive models, drafts, archived items).
     *
     * @return bool True if the model should be indexed, false otherwise.
     */
    public function shouldBeIndexed(): bool;

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
