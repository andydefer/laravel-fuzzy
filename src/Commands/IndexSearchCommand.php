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
 * Indexes searchable models for fuzzy search functionality.
 *
 * Handles indexing of models implementing the MustFuzzySearch interface,
 * supporting both manual configuration and auto-discovery.
 */
class IndexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:index
                            {model? : Specific model class to index}
                            {--force : Force reindexing of existing data}
                            {--chunk=100 : Number of records to process per batch}
                            {--auto : Use auto-discovery instead of configuration}
                            {--list : List discoverable models without indexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index searchable models for fuzzy search operations';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $specificModel = $this->argument('model');
        $shouldForceReindex = $this->option('force');
        $chunkSize = (int) $this->option('chunk');
        $shouldOnlyList = $this->option('list');

        if ($shouldOnlyList) {
            $this->displayDiscoverableModels();
            return;
        }

        $searchService = app(FuzzySearchService::class);

        if ($specificModel) {
            $this->indexSpecificModel(
                modelClass: $specificModel,
                searchService: $searchService,
                shouldForceReindex: $shouldForceReindex,
                chunkSize: $chunkSize
            );
        } else {
            $this->indexAllModels(
                searchService: $searchService,
                shouldForceReindex: $shouldForceReindex,
                chunkSize: $chunkSize
            );
        }
    }

    /**
     * Index a specific model class.
     *
     * @param string $modelClass
     * @param FuzzySearchService $searchService
     * @param bool $shouldForceReindex
     * @param int $chunkSize
     * @return void
     */
    protected function indexSpecificModel(
        string $modelClass,
        FuzzySearchService $searchService,
        bool $shouldForceReindex,
        int $chunkSize
    ): void {
        if (!$this->isValidSearchableModel($modelClass)) {
            $this->error("Model {$modelClass} must implement " . MustFuzzySearch::class);
            return;
        }

        $this->info("Indexing model: {$modelClass}");

        if ($shouldForceReindex) {
            $this->warn("Clearing existing index for {$modelClass}...");
            $searchService->reindexModel($modelClass);
        } else {
            $this->performBatchIndexing($modelClass, $searchService, $chunkSize);
        }

        $this->displayModelIndexingStatistics($modelClass, $searchService);
    }

    /**
     * Index all searchable models.
     *
     * @param FuzzySearchService $searchService
     * @param bool $shouldForceReindex
     * @param int $chunkSize
     * @return void
     */
    protected function indexAllModels(
        FuzzySearchService $searchService,
        bool $shouldForceReindex,
        int $chunkSize
    ): void {
        $models = $this->getAllSearchableModels();

        if (empty($models)) {
            $this->displayNoModelsWarning();
            return;
        }

        $this->displayModelsForIndexing($models);

        if ($shouldForceReindex) {
            $this->warn('Clearing all existing indexes...');
            $searchService->reindexAll();
        } else {
            foreach ($models as $modelClass) {
                $this->indexSpecificModel(
                    modelClass: $modelClass,
                    searchService: $searchService,
                    shouldForceReindex: false,
                    chunkSize: $chunkSize
                );
            }
        }

        $this->displayFinalStatistics($searchService);
    }

    /**
     * Display all discoverable models without performing indexing.
     *
     * @return void
     */
    protected function displayDiscoverableModels(): void
    {
        $this->info('=== Current Configuration ===');

        $configuredModels = config('fuzzy.searchable_models', []);
        $isAutoDiscoveryEnabled = config('fuzzy.auto_discovery.enabled', true);

        $this->displayConfigurationModels($configuredModels);

        if ($isAutoDiscoveryEnabled) {
            $this->displayAutoDiscoveredModels();
        } else {
            $this->warn('Auto-discovery is disabled in configuration');
        }

        $this->newLine();
        $this->displayValidModelsSummary();
        $this->displayUsageGuidance();
    }

    /**
     * Perform batch indexing for a model.
     *
     * @param string $modelClass
     * @param FuzzySearchService $searchService
     * @param int $chunkSize
     * @return void
     */
    private function performBatchIndexing(string $modelClass, FuzzySearchService $searchService, int $chunkSize): void
    {
        /** @var Model&MustFuzzySearch $modelClass */
        $modelClass::chunk($chunkSize, function ($models) use ($searchService, $modelClass): void {
            $progressBar = $this->output->createProgressBar(count($models));
            $progressBar->start();

            /** @var Model&MustFuzzySearch $model */
            foreach ($models as $model) {
                if (get_class($model) === $modelClass && $model->shouldBeIndexed()) {
                    $searchService->indexModel($model);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        });
    }

    /**
     * Display indexing statistics for a specific model.
     *
     * @param string $modelClass
     * @param FuzzySearchService $searchService
     * @return void
     */
    private function displayModelIndexingStatistics(string $modelClass, FuzzySearchService $searchService): void
    {
        $totalRecords = $modelClass::count();
        $stats = $searchService->getStats();
        $indexedCount = $stats['models'][$modelClass]['count'] ?? 0;

        $this->info("✓ Indexed {$indexedCount} entries for {$modelClass} ({$totalRecords} total records)");
    }

    /**
     * Get all searchable models from configuration and auto-discovery.
     *
     * @return array<int, string>
     */
    private function getAllSearchableModels(): array
    {
        $configuredModels = config('fuzzy.searchable_models', []);
        $isAutoDiscoveryEnabled = config('fuzzy.auto_discovery.enabled', true);

        $discoveredModels = $isAutoDiscoveryEnabled ? $this->discoverSearchableModels() : [];

        $allModels = array_unique(array_merge($configuredModels, $discoveredModels));

        return array_filter($allModels, fn(string $modelClass): bool => $this->isValidSearchableModel($modelClass));
    }

    /**
     * Display models that will be indexed.
     *
     * @param array<int, string> $models
     * @return void
     */
    private function displayModelsForIndexing(array $models): void
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
     * Display warning when no searchable models are found.
     *
     * @return void
     */
    private function displayNoModelsWarning(): void
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
    private function displayFinalStatistics(FuzzySearchService $searchService): void
    {
        $stats = $searchService->getStats();
        $this->info("✓ Indexing complete!");
        $this->info('Total entries: ' . $stats['total_entries']);

        foreach ($stats['models'] as $model => $modelStats) {
            $this->info("  {$model}: {$modelStats['count']} entries");
        }
    }

    /**
     * Display models configured in the configuration file.
     *
     * @param array<int, string> $configuredModels
     * @return void
     */
    private function displayConfigurationModels(array $configuredModels): void
    {
        if (empty($configuredModels)) {
            $this->warn('No models configured in config/fuzzy.php');
            return;
        }

        $this->info('Manually configured models:');
        foreach ($configuredModels as $model) {
            $classExists = class_exists($model) ? '✓' : '✗';
            $isSearchable = $this->isValidSearchableModel($model) ? '✓' : '✗';
            $this->info("  {$classExists}{$isSearchable} {$model}");
        }
    }

    /**
     * Display models discovered through auto-discovery.
     *
     * @return void
     */
    private function displayAutoDiscoveredModels(): void
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
     * Display summary of valid searchable models.
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
     * Display usage instructions for the command.
     *
     * @return void
     */
    private function displayUsageGuidance(): void
    {
        $this->newLine();
        $this->info('Usage:');
        $this->info('  php artisan fuzzy:index              # Index all (config + auto-discovered)');
        $this->info('  php artisan fuzzy:index --force      # Force reindex');
        $this->info('  php artisan fuzzy:index --list       # List models only');
        $this->info('  php artisan fuzzy:index User         # Index specific model');
    }

    /**
     * Discover models implementing MustFuzzySearch interface through file scanning.
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
            $modelClass = $this->extractClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isValidSearchableModel($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Extract fully qualified class name from a PHP file.
     *
     * @param string $filePath
     * @return string|null
     */
    private function extractClassNameFromFile(string $filePath): ?string
    {
        $fileContent = file_get_contents($filePath);

        $namespace = '';
        if (preg_match('/namespace\s+(.+?);/s', $fileContent, $matches)) {
            $namespace = $matches[1];
        }

        $className = '';
        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/', $fileContent, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            $fullClassName = $namespace . '\\' . $className;
            return class_exists($fullClassName) ? $fullClassName : null;
        }

        return null;
    }

    /**
     * Validate if a class is a searchable model.
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
