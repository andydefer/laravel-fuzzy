<?php

declare(strict_types=1);

namespace LaravelFuzzy\Exceptions;

use Exception;

class ModelNotSearchableException extends Exception
{
    public function __construct(string $modelClass)
    {
        parent::__construct(
            "Model {$modelClass} must implement LaravelFuzzy\\Contracts\\MustFuzzySearch interface."
        );
    }
}
