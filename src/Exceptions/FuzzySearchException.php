<?php

declare(strict_types=1);

namespace Fuzzy\Exceptions;

use Exception;

/**
 * Generic exception for fuzzy search errors.
 *
 * This exception is thrown for general errors that occur during
 * fuzzy search operations, such as configuration issues,
 * indexing problems, or invalid operations.
 *
 * @package Fuzzy\Exceptions
 */
class FuzzySearchException extends Exception
{
    /**
     * Create an exception for when a model does not implement the required interface.
     *
     * @param string $modelClass The model class name that is not searchable
     * @return self Configured exception instance
     */
    public static function modelNotSearchable(string $modelClass): self
    {
        return new self(
            sprintf('Model %s is not searchable. Implement MustFuzzySearch interface.', $modelClass)
        );
    }

    /**
     * Create an exception for when a search index is not found for a model.
     *
     * This typically occurs when attempting to search before running the indexer.
     *
     * @param string $modelClass The model class name that has no index
     * @return self Configured exception instance
     */
    public static function indexNotFound(string $modelClass): self
    {
        return new self(
            sprintf("Index not found for model %s. Run 'php artisan fuzzy:index'.", $modelClass)
        );
    }

    /**
     * Create an exception for an invalid formatter class.
     *
     * The formatter class must extend Spatie\LaravelData\Data to be valid.
     *
     * @param string $formatClass The invalid formatter class name
     * @return self Configured exception instance
     */
    public static function invalidFormatClass(string $formatClass): self
    {
        return new self(
            sprintf('Invalid format class: %s. Must extend Spatie\LaravelData\Data.', $formatClass)
        );
    }
}
