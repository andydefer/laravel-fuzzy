<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Fuzzy\Exceptions\ModelNotSearchableException;

/**
 * Interface for discovering and validating searchable models.
 *
 * Defines the contract for finding all models that implement
 * the MustFuzzySearch interface and validating model classes
 * for search compatibility.
 *
 * @package Fuzzy\Contracts
 */
interface ModelDiscoveryInterface
{
    /**
     * Get all searchable model classes in the application.
     *
     * Discovers models that implement MustFuzzySearch interface,
     * either from configuration or through automatic discovery.
     *
     * @return array<int, class-string<MustFuzzySearch>> List of searchable model class names
     */
    public function getSearchableModels(): array;

    /**
     * Check if a model class is searchable.
     *
     * Determines whether the given model class implements the
     * MustFuzzySearch interface and is valid for indexing.
     *
     * @param string $modelClass Fully qualified model class name to check
     * @return bool True if the model is searchable, false otherwise
     */
    public function isValidModel(string $modelClass): bool;

    /**
     * Validate that a model class is searchable.
     *
     * Checks if the model implements MustFuzzySearch interface.
     * Throws an exception if validation fails.
     *
     * @param string $modelClass Fully qualified model class name to validate
     * @throws ModelNotSearchableException When the model does not implement MustFuzzySearch
     * @return void
     */
    public function validateModel(string $modelClass): void;
}
