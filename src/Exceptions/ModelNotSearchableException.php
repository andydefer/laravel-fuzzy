<?php

declare(strict_types=1);

namespace Fuzzy\Exceptions;

use Exception;

class ModelNotSearchableException extends Exception
{
    public function __construct(string $modelClass)
    {
        parent::__construct(
            "Model {$modelClass} must implement Fuzzy\\Contracts\\MustFuzzySearch interface."
        );
    }
}
