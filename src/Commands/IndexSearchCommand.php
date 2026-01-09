<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;
use Fuzzy\Contracts\MustFuzzySearch;

class IndexSearchCommand extends Command
{
    protected $signature = 'fuzzy:index
                            {model? : Specific model to index}
                            {--force : Force reindexing}
                            {--chunk=100 : Chunk size for batch indexing}';

    protected $description = 'Index searchable models for fuzzy search';

    public function handle()
    {
        $specificModel = $this->argument('model');
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');

        $searchService = app('laravel-fuzzy.search');

        if ($specificModel) {
            $this->indexSingleModel($specificModel, $searchService, $force, $chunkSize);
        } else {
            $this->indexAllModels($searchService, $force, $chunkSize);
        }
    }

    protected function indexSingleModel(string $modelClass, $searchService, bool $force, int $chunkSize): void
    {
        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");
            return;
        }

        if (!in_array(MustFuzzySearch::class, class_implements($modelClass))) {
            $this->error("Model {$modelClass} must implement " . MustFuzzySearch::class);
            return;
        }

        $this->info("Indexing model: {$modelClass}");

        if ($force) {
            $this->warn("Clearing existing index for {$modelClass}...");
            $searchService->reindexModel($modelClass);
        } else {
            $this->info("Indexing {$modelClass} records...");
            $modelClass::chunk($chunkSize, function ($models) use ($searchService) {
                $bar = $this->output->createProgressBar(count($models));
                $bar->start();

                foreach ($models as $model) {
                    $searchService->indexModel($model);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
            });
        }

        $count = $modelClass::count();
        $indexed = $searchService->getStats()['models'][$modelClass]['count'] ?? 0;

        $this->info("✓ Indexed {$indexed} entries for {$modelClass} ({$count} total records)");
    }

    protected function indexAllModels($searchService, bool $force, int $chunkSize): void
    {
        $models = config('fuzzy.searchable_models', []);

        if (empty($models)) {
            $this->warn('No searchable models configured. Add models to config/fuzzy.php');
            return;
        }

        $this->info('Starting full search index...');

        if ($force) {
            $this->warn('Clearing all existing indexes...');
            $searchService->reindexAll();
        } else {
            foreach ($models as $modelClass) {
                if (class_exists($modelClass) && in_array(MustFuzzySearch::class, class_implements($modelClass))) {
                    $this->indexSingleModel($modelClass, $searchService, false, $chunkSize);
                }
            }
        }

        $stats = $searchService->getStats();
        $this->info("✓ Indexing complete!");
        $this->info("Total entries: {$stats['total_entries']}");

        foreach ($stats['models'] as $model => $modelStats) {
            $this->info("  {$model}: {$modelStats['count']} entries");
        }
    }
}
