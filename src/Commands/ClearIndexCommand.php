<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;
use Fuzzy\Models\FuzzyIndex;

class ClearIndexCommand extends Command
{
    protected $signature = 'fuzzy:clear
                            {model? : Specific model to clear}
                            {--force : Skip confirmation}';

    protected $description = 'Clear search index';

    public function handle()
    {
        $specificModel = $this->argument('model');
        $force = $this->option('force');

        if ($specificModel) {
            $this->clearModelIndex($specificModel, $force);
        } else {
            $this->clearAllIndexes($force);
        }
    }

    protected function clearModelIndex(string $modelClass, bool $force): void
    {
        if (!$force && !$this->confirm("Clear index for model {$modelClass}?")) {
            return;
        }

        $count = FuzzyIndex::forModel($modelClass)->count();

        FuzzyIndex::forModel($modelClass)->delete();

        $this->info("✓ Cleared {$count} entries for {$modelClass}");
    }

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
