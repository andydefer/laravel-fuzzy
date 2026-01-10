<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Services\FuzzySearchService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Command to index searchable models for fuzzy search.
 *
 * This command handles indexing of models that implement the MustFuzzySearch interface,
 * either from manual configuration or through auto-discovery.
 */
class IndexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:index
                            {model? : Specific model to index}
                            {--force : Force reindexing}
                            {--chunk=100 : Chunk size for batch indexing}
                            {--auto : Use auto-discovery instead of config}
                            {--list : List discoverable models without indexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index searchable models for fuzzy search';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $specificModel = $this->argument('model');
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');
        $listOnly = $this->option('list');

        if ($listOnly) {
            $this->listDiscoverableModels();
            return;
        }

        $searchService = app(FuzzySearchService::class);

        if ($specificModel) {
            $this->indexSingleModel(
                modelClass: $specificModel,
                searchService: $searchService,
                force: $force,
                chunkSize: $chunkSize
            );
        } else {
            $this->indexAllModels(
                searchService: $searchService,
                force: $force,
                chunkSize: $chunkSize
            );
        }
    }

    /**
     * Index a single model.
     *
     * @param string $modelClass
     * @param FuzzySearchService $searchService
     * @param bool $force
     * @param int $chunkSize
     * @return void
     */
    protected function indexSingleModel(string $modelClass, FuzzySearchService $searchService, bool $force, int $chunkSize): void
    {
        if (!$this->isValidSearchableModel($modelClass)) {
            $this->error("Model {$modelClass} must exist and implement " . MustFuzzySearch::class);
            return;
        }

        $this->info("Indexing model: {$modelClass}");

        if ($force) {
            $this->warn("Clearing existing index for {$modelClass}...");
            $searchService->reindexModel($modelClass);
        } else {
            $this->executeBatchIndexing($modelClass, $searchService, $chunkSize);
        }

        $this->displayIndexingStats($modelClass, $searchService);
    }

    /**
     * Index all searchable models.
     *
     * @param FuzzySearchService $searchService
     * @param bool $force
     * @param int $chunkSize
     * @return void
     */
    protected function indexAllModels(FuzzySearchService $searchService, bool $force, int $chunkSize): void
    {
        $models = $this->getAllSearchableModels();

        if (empty($models)) {
            $this->displayNoModelsFoundWarning();
            return;
        }

        $this->displayModelsToIndex($models);

        if ($force) {
            $this->warn('Clearing all existing indexes...');
            $searchService->reindexAll();
        } else {
            foreach ($models as $modelClass) {
                $this->indexSingleModel(
                    modelClass: $modelClass,
                    searchService: $searchService,
                    force: false,
                    chunkSize: $chunkSize
                );
            }
        }

        $this->displayFinalStats($searchService);
    }

    /**
     * List all discoverable models without indexing.
     *
     * @return void
     */
    protected function listDiscoverableModels(): void
    {
        $this->info('=== Current Configuration ===');

        $configuredModels = config('fuzzy.searchable_models', []);
        $autoDiscoveryEnabled = config('fuzzy.auto_discovery.enabled', true);

        $this->displayConfiguredModels($configuredModels);

        if ($autoDiscoveryEnabled) {
            $this->displayDiscoveredModels();
        } else {
            $this->warn('Auto-discovery is disabled in config');
        }

        $this->newLine();
        $this->displayValidModelsSummary();
        $this->displayUsageInstructions();
    }

    /**
     * Execute batch indexing for a model.
     *
     * @param string $modelClass
     * @param FuzzySearchService $searchService
     * @param int $chunkSize
     * @return void
     */
    private function executeBatchIndexing(string $modelClass, FuzzySearchService $searchService, int $chunkSize): void
    {
        /** @var Model&MustFuzzySearch $modelClass */
        $modelClass::chunk($chunkSize, function ($models) use ($searchService) {
            $progressBar = $this->output->createProgressBar(count($models));
            $progressBar->start();

            /** @var Model&MustFuzzySearch $model */
            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $searchService->indexModel($model);
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        });
    }

    /**
     * Display indexing statistics for a model.
     *
     * @param string $modelClass
     * @param FuzzySearchService $searchService
     * @return void
     */
    private function displayIndexingStats(string $modelClass, FuzzySearchService $searchService): void
    {
        $totalRecords = $modelClass::count();
        $stats = $searchService->getStats();
        $indexedCount = $stats['models'][$modelClass]['count'] ?? 0;

        $this->info("✓ Indexed {$indexedCount} entries for {$modelClass} ({$totalRecords} total records)");
    }

    /**
     * Get all searchable models from both configuration and auto-discovery.
     *
     * @return array<int, string>
     */
    private function getAllSearchableModels(): array
    {
        $configuredModels = config('fuzzy.searchable_models', []);
        $autoDiscoveryEnabled = config('fuzzy.auto_discovery.enabled', true);

        $discoveredModels = $autoDiscoveryEnabled ? $this->discoverSearchableModels() : [];

        $allModels = array_unique(array_merge($configuredModels, $discoveredModels));

        return array_filter($allModels, fn($modelClass) => $this->isValidSearchableModel($modelClass));
    }

    /**
     * Display models that will be indexed.
     *
     * @param array<int, string> $models
     * @return void
     */
    private function displayModelsToIndex(array $models): void
    {
        $this->info('Starting full search index...');
        $this->info('Found ' . count($models) . ' searchable model(s):');

        $configuredModels = config('fuzzy.searchable_models', []);

        foreach ($models as $model) {
            $source = in_array($model, $configuredModels) ? 'config' : 'auto-discovered';
            $this->info("  - {$model} ({$source})");
        }

        $this->newLine();
    }

    /**
     * Display warning when no models are found.
     *
     * @return void
     */
    private function displayNoModelsFoundWarning(): void
    {
        $this->warn('No searchable models found.');
        $this->warn('Make sure your models:');
        $this->warn('1. Implement the MustFuzzySearch interface');
        $this->warn('2. Use the FuzzySearchable trait');
        $this->warn('');
        $this->warn('You can either:');
        $this->warn('a) Add models to config/fuzzy.php (searchable_models array)');
        $this->warn('b) Place models in app/Models/ directory');
    }

    /**
     * Display final indexing statistics.
     *
     * @param FuzzySearchService $searchService
     * @return void
     */
    private function displayFinalStats(FuzzySearchService $searchService): void
    {
        $stats = $searchService->getStats();
        $this->info("✓ Indexing complete!");
        $this->info("Total entries: {$stats['total_entries']}");

        foreach ($stats['models'] as $model => $modelStats) {
            $this->info("  {$model}: {$modelStats['count']} entries");
        }
    }

    /**
     * Display configured models.
     *
     * @param array<int, string> $configuredModels
     * @return void
     */
    private function displayConfiguredModels(array $configuredModels): void
    {
        if (empty($configuredModels)) {
            $this->warn('No models configured in config/fuzzy.php');
            return;
        }

        $this->info('Manually configured models:');
        foreach ($configuredModels as $model) {
            $exists = class_exists($model) ? '✓' : '✗';
            $implements = $this->isValidSearchableModel($model) ? '✓' : '✗';
            $this->info("  {$exists}{$implements} {$model}");
        }
    }

    /**
     * Display discovered models.
     *
     * @return void
     */
    private function displayDiscoveredModels(): void
    {
        $this->info('Auto-discovered models:');
        $discoveredModels = $this->discoverSearchableModels();

        if (empty($discoveredModels)) {
            $this->warn('No models found via auto-discovery');
            return;
        }

        foreach ($discoveredModels as $model) {
            $this->info("  ✓ {$model}");
        }
    }

    /**
     * Display valid models summary.
     *
     * @return void
     */
    private function displayValidModelsSummary(): void
    {
        $this->info('=== Combined Result (what will be indexed) ===');

        $models = $this->getAllSearchableModels();

        if (empty($models)) {
            $this->error('No valid searchable models found!');
            return;
        }

        $this->info('Valid searchable models:');
        $configuredModels = config('fuzzy.searchable_models', []);

        foreach ($models as $model) {
            $source = in_array($model, $configuredModels) ? 'config' : 'auto';
            $this->info("  ✓ {$model} ({$source})");
        }
    }

    /**
     * Display usage instructions.
     *
     * @return void
     */
    private function displayUsageInstructions(): void
    {
        $this->newLine();
        $this->info('Usage:');
        $this->info('  php artisan fuzzy:index              # Index all (config + auto-discovered)');
        $this->info('  php artisan fuzzy:index --force      # Force reindex');
        $this->info('  php artisan fuzzy:index --list       # List models only');
        $this->info('  php artisan fuzzy:index User         # Index specific model');
    }

    /**
     * Discover models implementing MustFuzzySearch interface.
     *
     * @return array<int, string>
     */
    private function discoverSearchableModels(): array
    {
        $models = [];
        $finder = new Finder();

        $finder->files()
            ->in(app_path('Models'))
            ->name('*.php');

        foreach ($finder as $file) {
            $modelClass = $this->getClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isValidSearchableModel($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Get fully qualified class name from file path.
     *
     * @param string $filePath
     * @return string|null
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        $namespace = '';
        if (preg_match('/namespace\s+(.+?);/s', $content, $matches)) {
            $namespace = $matches[1];
        }

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
     * Check if a class is a valid searchable model.
     *
     * @param string $modelClass
     * @return bool
     */
    private function isValidSearchableModel(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
    }
}
