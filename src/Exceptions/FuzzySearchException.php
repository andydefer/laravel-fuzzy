<?php

declare(strict_types=1);

namespace Fuzzy\Exceptions;

use Exception;

/**
 * Exception for generic fuzzy search errors.
 */
class FuzzySearchException extends Exception
{
    /**
     * Creates an exception for when a model is not searchable.
     *
     * @param string $modelClass The model class name
     */
    public static function modelNotSearchable(string $modelClass): self
    {
        return new self(
            sprintf('Model %s is not searchable. Implement MustFuzzySearch interface.', $modelClass)
        );
    }

    /**
     * Creates an exception for when a search index is not found.
     *
     * @param string $modelClass The model class name
     */
    public static function indexNotFound(string $modelClass): self
    {
        return new self(
            sprintf("Index not found for model %s. Run 'php artisan fuzzy:index'.", $modelClass)
        );
    }

    /**
     * Creates an exception for an invalid format class.
     *
     * @param string $formatClass The format class name
     */
    public static function invalidFormatClass(string $formatClass): self
    {
        return new self(
            sprintf('Invalid format class: %s. Must extend Spatie\LaravelData\Data.', $formatClass)
        );
    }
}
