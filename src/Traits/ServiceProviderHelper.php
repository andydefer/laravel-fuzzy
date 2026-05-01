<?php

declare(strict_types=1);

namespace Fuzzy\Traits;

use Illuminate\Support\ServiceProvider;

/**
 * Trait that exposes protected ServiceProvider methods.
 * Allows ServiceRegistrar to use methods like mergeConfigFrom, publishes, commands.
 */
trait ServiceProviderHelper
{
    private ServiceProvider $provider;

    /**
     * Merge configuration from a file.
     */
    protected function mergeConfigFrom(string $path, string $key): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('mergeConfigFrom');
        $method->setAccessible(true);
        $method->invoke($this->provider, $path, $key);
    }

    /**
     * Register publishable resources.
     */
    protected function publishes(array $paths, string $group = null): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('publishes');
        $method->setAccessible(true);

        if ($group !== null) {
            $method->invoke($this->provider, $paths, $group);
        } else {
            $method->invoke($this->provider, $paths);
        }
    }

    /**
     * Register console commands.
     */
    protected function commands(array $commands): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('commands');
        $method->setAccessible(true);
        $method->invoke($this->provider, $commands);
    }
}
