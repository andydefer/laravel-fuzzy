<?php

declare(strict_types=1);

namespace Fuzzy\Traits;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Services\FuzzySearchService;
use ReflectionClass;

/**
 * Trait providing common functionality for console commands.
 *
 * This trait centralizes reusable methods used across multiple commands
 * to avoid code duplication and ensure consistent behavior.
 *
 * @package Fuzzy\Traits
 */
trait CommandHelpers
{
    /**
     * Get the fuzzy search service from the container.
     *
     * @return FuzzySearchService
     */
    protected function getSearchService(): FuzzySearchService
    {
        return app(FuzzySearchService::class);
    }

    /**
     * Confirm an action with the user, skipping if force flag is provided.
     *
     * @param string $message The confirmation message to display
     * @param bool $force Whether to skip confirmation (--force flag)
     * @return bool True if the action should proceed, false otherwise
     */
    protected function confirmAction(string $message, bool $force): bool
    {
        if ($force) {
            return true;
        }

        return $this->confirm($message);
    }

    /**
     * Validate if a class is a searchable model.
     *
     * Checks that the class exists and implements the MustFuzzySearch interface.
     *
     * @param string $modelClass The fully qualified model class name to validate
     * @return bool True if the class exists and implements MustFuzzySearch
     */
    protected function isValidSearchableModel(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
    }

    /**
     * Display a success message with a checkmark prefix.
     *
     * @param string $message The success message to display
     * @return void
     */
    protected function showSuccess(string $message): void
    {
        $this->info("✓ {$message}");
    }

    /**
     * Display a warning message.
     *
     * @param string $message The warning message to display
     * @return void
     */
    protected function showWarning(string $message): void
    {
        $this->warn($message);
    }

    /**
     * Display an error message.
     *
     * @param string $message The error message to display
     * @return void
     */
    protected function showError(string $message): void
    {
        $this->error($message);
    }

    /**
     * Display an info message.
     *
     * @param string $message The info message to display
     * @return void
     */
    protected function showInfo(string $message): void
    {
        $this->info($message);
    }

    /**
     * Display a section header with equals signs.
     *
     * @param string $title The section title
     * @return void
     */
    protected function showHeader(string $title): void
    {
        $this->info("=== {$title} ===");
    }

    /**
     * Display a blank line.
     *
     * @return void
     */
    protected function showNewLine(): void
    {
        $this->line('');
    }
}
