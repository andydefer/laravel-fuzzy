<?php

declare(strict_types=1);

namespace Fuzzy\Exceptions;

use Exception;

/**
 * Exception for when a model does not implement the required search interface.
 *
 * This exception is thrown when attempting to index or search a model
 * that does not implement the MustFuzzySearch interface.
 *
 * @package Fuzzy\Exceptions
 */
class ModelNotSearchableException extends Exception
{
    /**
     * Constructor for ModelNotSearchableException.
     *
     * @param string $modelClass The model class name that is not searchable
     */
    public function __construct(string $modelClass)
    {
        parent::__construct(
            sprintf('Model %s must implement Fuzzy\Contracts\MustFuzzySearch interface.', $modelClass)
        );
    }
}
