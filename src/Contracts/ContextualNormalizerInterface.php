<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for contextual string normalization.
 *
 * Extends the base StringNormalizerInterface with context-aware capabilities,
 * allowing different normalization strategies based on the field being processed.
 *
 * This interface is essential for handling fields that should preserve stop words
 * (like names, emails, usernames) versus fields that should remove them
 * (like descriptions, content, comments).
 *
 * @package Fuzzy\Contracts
 */
interface ContextualNormalizerInterface extends StringNormalizerInterface
{
    /**
     * Set the protected fields that should preserve stop words.
     *
     * Protected fields will NOT have stop words removed during normalization.
     * This is useful for names, emails, usernames, etc. where stop words
     * are meaningful (e.g., "Jean de La Fontaine").
     *
     * @param array<int, string> $protectedFields List of field names that should preserve stop words
     * @return self Returns self for method chaining
     */
    public function setProtectedFields(array $protectedFields): self;

    /**
     * Set the current field being processed for contextual normalization.
     *
     * This allows the normalizer to know which field is being processed
     * and apply the appropriate strategy (preserve or remove stop words).
     *
     * @param string|null $field The field name or null for default behavior (remove stop words)
     * @return self Returns self for method chaining
     */
    public function setCurrentField(?string $field): self;

    /**
     * Get the current field being processed.
     *
     * Returns the field name that was set via setCurrentField(), or null
     * if no field has been set.
     *
     * @return string|null The current field name or null if not set
     */
    public function getCurrentField(): ?string;

    /**
     * Get the protected fields array.
     *
     * Returns the list of fields that should preserve stop words.
     *
     * @return array<int, string> List of protected field names
     */
    public function getProtectedFields(): array;

    /**
     * Check if a field should preserve stop words.
     *
     * Returns true if the given field is in the protected fields list,
     * meaning stop words should be preserved during normalization.
     *
     * @param string $field The field name to check
     * @return bool True if stop words should be preserved, false otherwise
     */
    public function shouldPreserveStopWords(string $field): bool;

    /**
     * Normalize a value specifically for a field during indexing.
     *
     * This method respects the field's protected status and applies
     * appropriate normalization (with or without stop word removal).
     *
     * It temporarily sets the current field to the given field name,
     * performs the normalization, then resets the current field to null.
     *
     * @param string $value The raw value to normalize
     * @param string $field The field name being indexed
     * @return string Normalized value appropriate for the field
     */
    public function normalizeForField(string $value, string $field): string;
}
