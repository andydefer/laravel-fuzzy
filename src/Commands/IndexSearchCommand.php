<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Services\FuzzySearchService;
use Symfony\Component\Finder\Finder;
use ReflectionClass;

class IndexSearchCommand extends Command
{
    protected $signature = 'fuzzy:index
                            {model? : Specific model to index}
                            {--force : Force reindexing}
                            {--chunk=100 : Chunk size for batch indexing}
                            {--auto : Use auto-discovery instead of config}
                            {--list : List discoverable models without indexing}';

    protected $description = 'Index searchable models for fuzzy search';

    public function handle()
    {
        $specificModel = $this->argument('model');
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');
        $auto = $this->option('auto');
        $list = $this->option('list');

        $searchService = app(FuzzySearchService::class);

        if ($list) {
            $this->listDiscoverableModels();
            return;
        }

        if ($specificModel) {
            $this->indexSingleModel($specificModel, $searchService, $force, $chunkSize);
        } else {
            $this->indexAllModels($searchService, $force, $chunkSize, $auto);
        }
    }

    protected function indexSingleModel(string $modelClass, FuzzySearchService $searchService, bool $force, int $chunkSize): void
    {
        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");
            return;
        }

        $reflection = new ReflectionClass($modelClass);
        if (!$reflection->implementsInterface(MustFuzzySearch::class)) {
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
                    // Respecter la condition shouldBeIndexed()
                    if ($model->shouldBeIndexed()) {
                        $searchService->indexModel($model);
                    }
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

    protected function indexAllModels(FuzzySearchService $searchService, bool $force, int $chunkSize, bool $auto = false): void
    {
        // Détecter les modèles selon le mode
        if ($auto) {
            $models = $this->discoverSearchableModels();
            $this->info('Using auto-discovery mode');
        } else {
            $models = config('fuzzy.searchable_models', []);
            $this->info('Using manual configuration mode');

            // Filtrer les modèles non valides
            $models = array_filter($models, function ($modelClass) {
                return class_exists($modelClass) &&
                    in_array(MustFuzzySearch::class, class_implements($modelClass));
            });
        }

        if (empty($models)) {
            if ($auto) {
                $this->warn('No searchable models found via auto-discovery.');
                $this->warn('Make sure your models:');
                $this->warn('1. Are in the app/Models directory');
                $this->warn('2. Implement the MustFuzzySearch interface');
                $this->warn('3. Use the FuzzySearchable trait');
            } else {
                $this->warn('No searchable models configured.');
                $this->warn('Add models to config/fuzzy.php or use --auto flag for auto-discovery');
            }
            return;
        }

        $this->info('Starting full search index...');
        $this->info('Found ' . count($models) . ' searchable model(s):');

        foreach ($models as $model) {
            $this->info("  - {$model}");
        }

        $this->newLine();

        if ($force) {
            $this->warn('Clearing all existing indexes...');
            $searchService->reindexAll();
        } else {
            foreach ($models as $modelClass) {
                $this->indexSingleModel($modelClass, $searchService, false, $chunkSize);
            }
        }

        $stats = $searchService->getStats();
        $this->info("✓ Indexing complete!");
        $this->info("Total entries: {$stats['total_entries']}");

        foreach ($stats['models'] as $model => $modelStats) {
            $this->info("  {$model}: {$modelStats['count']} entries");
        }
    }

    /**
     * List all discoverable models
     */
    protected function listDiscoverableModels(): void
    {
        $this->info('=== Manual Configuration ===');
        $configuredModels = config('fuzzy.searchable_models', []);

        if (empty($configuredModels)) {
            $this->warn('No models configured in config/fuzzy.php');
        } else {
            $this->info('Models in config:');
            foreach ($configuredModels as $model) {
                $exists = class_exists($model) ? '✓' : '✗';
                $implements = class_exists($model) &&
                    in_array(MustFuzzySearch::class, class_implements($model)) ? '✓' : '✗';
                $this->info("  {$exists}{$implements} {$model}");
            }
        }

        $this->newLine();
        $this->info('=== Auto-Discovery ===');

        $discoveredModels = $this->discoverSearchableModels();

        if (empty($discoveredModels)) {
            $this->warn('No models found via auto-discovery');
        } else {
            $this->info('Discovered models:');
            foreach ($discoveredModels as $model) {
                $this->info("  ✓ {$model}");
            }
        }

        $this->newLine();
        $this->info('Usage:');
        $this->info('  php artisan fuzzy:index              # Use config (default)');
        $this->info('  php artisan fuzzy:index --auto       # Use auto-discovery');
        $this->info('  php artisan fuzzy:index --list       # List models only');
    }

    /**
     * Discover models implementing MustFuzzySearch interface
     */
    private function discoverSearchableModels(): array
    {
        $models = [];

        // Scanner le dossier Models
        $finder = new Finder();
        $finder->files()
            ->in(app_path('Models'))
            ->name('*.php');

        foreach ($finder as $file) {
            $modelClass = $this->getClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isModelSearchable($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Get fully qualified class name from file path
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // Extraire le namespace
        $namespace = '';
        if (preg_match('/namespace\s+(.+?);/s', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extraire le nom de classe
        $className = '';
        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/', $content, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            $fullClassName = $namespace . '\\' . $className;
            return class_exists($fullClassName) ? $fullClassName : null;
        }

        return null;
    }

    /**
     * Check if model is searchable
     */
    private function isModelSearchable(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
    }
}
