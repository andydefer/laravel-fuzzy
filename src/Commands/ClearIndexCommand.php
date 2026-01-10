<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Fuzzy\Models\FuzzyIndex;
use Illuminate\Console\Command;

/**
 * Command to clear the search index for specific models or all models.
 */
class ClearIndexCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected $signature = 'fuzzy:clear
                            {model? : Specific model to clear}
                            {--force : Skip confirmation}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Clear search index';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $modelClass = $this->argument('model');
        $force = $this->option('force');

        if ($modelClass) {
            $this->clearModelIndex($modelClass, $force);
        } else {
            $this->clearAllIndexes($force);
        }
    }

    /**
     * Clear the index for a specific model.
     *
     * @param string $modelClass The model class to clear
     * @param bool $force Whether to skip confirmation
     * @return void
     */
    protected function clearModelIndex(string $modelClass, bool $force): void
    {
        if (!$force && !$this->confirm("Clear index for model {$modelClass}?")) {
            return;
        }

        $count = FuzzyIndex::forModel($modelClass)->count();
        FuzzyIndex::forModel($modelClass)->delete();

        $this->info("✓ Cleared {$count} entries for {$modelClass}");
    }

    /**
     * Clear all search indexes.
     *
     * @param bool $force Whether to skip confirmation
     * @return void
     */
    protected function clearAllIndexes(bool $force): void
    {
        if (!$force && !$this->confirm('Clear ALL search indexes?')) {
            return;
        }

        $count = FuzzyIndex::count();
        FuzzyIndex::query()->truncate();

        $this->info("✓ Cleared all indexes ({$count} entries)");
    }
}
