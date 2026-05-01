<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for configuration value objects.
 *
 * Defines the contract for all configuration classes in the package.
 * Each configuration class must provide a way to create an instance
 * from Laravel configuration with fallback to defaults.
 */
interface ConfigInterface
{
    /**
     * Create configuration instance from Laravel config with fallback to defaults.
     *
     * @return self
     */
    public static function fromConfig(): self;

    /**
     * Create configuration instance with default values.
     *
     * @return self
     */
    public static function createDefault(): self;
}
