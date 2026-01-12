<?php

declare(strict_types=1);

namespace Fuzzy\Exceptions;

use Exception;

/**
 * Exception for when a model does not implement the required search interface.
 */
class ModelNotSearchableException extends Exception
{
    /**
     * @param string $modelClass The model class name that is not searchable
     */
    public function __construct(string $modelClass)
    {
        parent::__construct(
            sprintf('Model %s must implement Fuzzy\Contracts\MustFuzzySearch interface.', $modelClass)
        );
    }
}
