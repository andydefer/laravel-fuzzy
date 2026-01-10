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
     * @return self
     */
    public static function modelNotSearchable(string $modelClass): self
    {
        return new self(
            "Model {$modelClass} is not searchable. Implement MustFuzzySearch interface."
        );
    }

    /**
     * Creates an exception for when a search index is not found.
     *
     * @param string $modelClass The model class name
     * @return self
     */
    public static function indexNotFound(string $modelClass): self
    {
        return new self(
            "Index not found for model {$modelClass}. Run 'php artisan fuzzy:index'."
        );
    }

    /**
     * Creates an exception for an invalid format class.
     *
     * @param string $formatClass The format class name
     * @return self
     */
    public static function invalidFormatClass(string $formatClass): self
    {
        return new self(
            "Invalid format class: {$formatClass}. Must extend Spatie\\LaravelData\\Data."
        );
    }
}
