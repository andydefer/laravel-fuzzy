<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Fuzzy\Models\FuzzyIndex;
use Illuminate\Console\Command;

/**
 * Console command to clear search indexes for specific models or all indexed data.
 *
 * This command provides safe deletion of fuzzy search index entries with
 * confirmation prompts to prevent accidental data loss.
 */
class ClearIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:clear
                            {model? : Specific model class to clear (e.g., App\\Models\\User)}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear search index entries for specific models or all indexed data';

    /**
     * Execute the console command.
     *
     * Routes to either clear a specific model's index or all indexes
     * based on provided arguments.
     *
     * @return void
     */
    public function handle(): void
    {
        $modelClass = $this->argument('model');
        $shouldSkipConfirmation = $this->option('force');

        if ($modelClass) {
            $this->clearModelIndex($modelClass, $shouldSkipConfirmation);
        } else {
            $this->clearAllIndexes($shouldSkipConfirmation);
        }
    }

    /**
     * Clear search index entries for a specific model class.
     *
     * @param string $modelClass The fully qualified model class name
     * @param bool $shouldSkipConfirmation Whether to bypass user confirmation
     * @return void
     */
    protected function clearModelIndex(string $modelClass, bool $shouldSkipConfirmation): void
    {
        if (!$this->shouldProceedWithDeletion(
            sprintf('Clear index for model %s?', $modelClass),
            $shouldSkipConfirmation
        )) {
            return;
        }

        $deletedCount = $this->deleteModelIndex($modelClass);

        $this->info(sprintf('✓ Cleared %s entries for %s', $deletedCount, $modelClass));
    }

    /**
     * Clear all search index entries from the database.
     *
     * @param bool $shouldSkipConfirmation Whether to bypass user confirmation
     * @return void
     */
    protected function clearAllIndexes(bool $shouldSkipConfirmation): void
    {
        if (!$this->shouldProceedWithDeletion(
            'Clear ALL search indexes?',
            $shouldSkipConfirmation
        )) {
            return;
        }

        $totalCount = $this->deleteAllIndexes();

        $this->info(sprintf('✓ Cleared all indexes (%s entries)', $totalCount));
    }

    /**
     * Determine if the deletion operation should proceed.
     *
     * @param string $message The confirmation message to display
     * @param bool $shouldSkipConfirmation Whether to skip confirmation
     * @return bool True if the operation should proceed, false otherwise
     */
    private function shouldProceedWithDeletion(string $message, bool $shouldSkipConfirmation): bool
    {
        if ($shouldSkipConfirmation) {
            return true;
        }

        return $this->confirm($message);
    }

    /**
     * Delete all index entries for a specific model.
     *
     * @param string $modelClass The model class to clear
     * @return int The number of entries deleted
     */
    private function deleteModelIndex(string $modelClass): int
    {
        // Note: Cannot use named parameters here as forModel() is a scope that doesn't support them
        $entryCount = FuzzyIndex::forModel($modelClass)->count();
        FuzzyIndex::forModel($modelClass)->delete();

        return $entryCount;
    }

    /**
     * Delete all search index entries from the database.
     *
     * @return int The total number of entries cleared
     */
    private function deleteAllIndexes(): int
    {
        $totalCount = FuzzyIndex::count();
        FuzzyIndex::query()->truncate();

        return $totalCount;
    }
}
