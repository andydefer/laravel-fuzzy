<?php

declare(strict_types=1);

namespace Fuzzy\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when duplicate pipeline stages are detected in configuration.
 */
class DuplicateStageException extends InvalidArgumentException
{
    /**
     * Create a new exception instance for duplicate stage.
     *
     * @param string $stageClass The duplicate stage class name
     * @param int $occurrence The occurrence number (1st, 2nd, etc.)
     */
    public static function duplicate(string $stageClass, int $occurrence): self
    {
        return new self(
            sprintf(
                'Duplicate pipeline stage "%s" detected at position %d. '
                    . 'Please remove duplicate entries from your fuzzy.pipeline configuration.',
                $stageClass,
                $occurrence
            )
        );
    }

    /**
     * Create a new exception instance when a stage appears both in custom and core.
     *
     * @param string $stageClass The stage class name
     */
    public static function conflictsWithCore(string $stageClass): self
    {
        return new self(
            sprintf(
                'Pipeline stage "%s" cannot be added as a custom stage because it is already '
                    . 'part of the core pipeline. Core stages are not modifiable.',
                $stageClass
            )
        );
    }
}
