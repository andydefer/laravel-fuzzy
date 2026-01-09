<?php

declare(strict_types=1);

namespace LaravelFuzzy\Exceptions;

use Exception;

class FuzzySearchException extends Exception
{
    public static function modelNotSearchable(string $modelClass): self
    {
        return new self(
            "Model {$modelClass} is not searchable. Implement MustFuzzySearch interface."
        );
    }

    public static function indexNotFound(string $modelClass): self
    {
        return new self(
            "Index not found for model {$modelClass}. Run 'php artisan fuzzy:index'."
        );
    }

    public static function invalidFormatClass(string $formatClass): self
    {
        return new self(
            "Invalid format class: {$formatClass}. Must extend Spatie\\LaravelData\\Data."
        );
    }
}
