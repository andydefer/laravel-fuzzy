<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for models that support fuzzy search indexing and searching.
 *
 * Models implementing this interface can be indexed, searched, and retrieved
 * through the fuzzy search system with custom configuration per model.
 *
 * This interface is designed to work seamlessly with Laravel Eloquent models,
 * but can be implemented by any class that needs to be searchable.
 *
 * @package Fuzzy\Contracts
 */
interface MustFuzzySearch
{
    /**
     * Get the model fields to be indexed for fuzzy searching.
     *
     * Each field name in the returned array will be indexed separately,
     * allowing search queries to match against any of these attributes.
     *
     * Example return:
     * ```php
     * return ['name', 'email', 'description'];
     * ```
     *
     * @return array<int, string> List of attribute names to include in search index
     */
    public function getSearchableFields(): array;

    /**
     * Get the custom formatter class for model data transformation during indexing.
     *
     * The formatter class must implement a static `fromModel()` method that
     * receives the model instance and returns a formatted array for indexing.
     *
     * Example formatter class:
     * ```php
     * class UserSearchData extends Data
     * {
     *     public static function fromModel(User $user): self
     *     {
     *         return new self(
     *             id: $user->id,
     *             name: $user->full_name,
     *             // ... custom formatting
     *         );
     *     }
     * }
     * ```
     *
     * @return class-string|null Fully qualified formatter class name, or null for default formatting
     */
    public function getFuzzyFormat(): ?string;

    /**
     * Get the unique identifier for this model instance.
     *
     * Used to associate index entries with the correct model instance during
     * search result retrieval. Typically returns the model's primary key value.
     *
     * @return string|int Unique identifier for this model instance
     */
    public function getIndexableId(): string|int;

    /**
     * Determine if the model should be included in the search index.
     *
     * Allows conditional indexing based on model state. Return false to
     * exclude the model entirely from search results.
     *
     * Common use cases:
     * - Exclude soft-deleted records: `return ! $this->trashed();`
     * - Exclude inactive users: `return $this->is_active;`
     * - Exclude draft content: `return $this->status === 'published';`
     *
     * @return bool True to include model in index, false to exclude
     */
    public function shouldBeIndexed(): bool;

    /**
     * Get the list of protected fields that should preserve stop words.
     *
     * These fields will NOT have stop words removed during indexing.
     * Useful for names, emails, usernames, etc. where stop words are meaningful.
     *
     * Example return:
     * ```php
     * return ['name', 'email', 'username', 'firstname', 'lastname'];
     * ```
     *
     * By default, returns an empty array (no fields protected).
     * Override this method in your model to specify which fields should
     * preserve stop words during indexing.
     *
     * @return array<int, string> List of field names that should preserve stop words
     */
    public function getProtectedFields(): array;
}
