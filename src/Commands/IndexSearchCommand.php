<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Traits\CommandHelpers;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Command to index searchable models for fuzzy search functionality.
 *
 * Supports both manual configuration and auto-discovery of models.
 * Provides detailed progress reporting and statistics on indexed entries.
 *
 * @package Fuzzy\Commands
 */
class IndexSearchCommand extends Command
{
    use CommandHelpers;

    /**
     * Default chunk size for batch processing when not specified in options.
     */
    private const DEFAULT_CHUNK_SIZE = 100;

    /**
     * Chunk size used for statistics calculation to avoid memory issues.
     */
    private const STATS_CHUNK_SIZE = 1000;

    /**
     * Percentage multiplier for converting ratios to percentages.
     */
    private const PERCENTAGE_MULTIPLIER = 100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:index
                            {model? : Specific model class to index}
                            {--force : Force reindexing of existing data}
                            {--chunk=100 : Number of records to process per batch}
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

        if ($specificModel) {
            $this->indexSpecificModel(
                modelClass: $specificModel,
                shouldForceReindex: $shouldForceReindex,
                chunkSize: $chunkSize
            );
        } else {
            $this->indexAllModels(
                shouldForceReindex: $shouldForceReindex,
                chunkSize: $chunkSize
            );
        }
    }

    /**
     * Index a specific model class.
     *
     * @param string $modelClass The fully qualified model class name
     * @param bool $shouldForceReindex Whether to clear existing index before indexing
     * @param int $chunkSize Number of records to process per batch
     * @return void
     */
    protected function indexSpecificModel(
        string $modelClass,
        bool $shouldForceReindex,
        int $chunkSize
    ): void {
        $modelDiscovery = $this->getModelDiscovery();

        if (!$modelDiscovery->isValidModel($modelClass)) {
            $this->showError("Model {$modelClass} must implement " . MustFuzzySearch::class);
            return;
        }

        $this->showInfo("Indexing model: {$modelClass}");

        if ($shouldForceReindex) {
            $this->showWarning("Clearing existing index for {$modelClass}...");
            $this->getSearchService()->getIndexManager()->reindexModel($modelClass);
        } else {
            $this->performBatchIndexing($modelClass, $chunkSize);
        }

        $this->displayModelIndexingStatistics($modelClass);
    }

    /**
     * Index all searchable models.
     *
     * @param bool $shouldForceReindex Whether to clear existing index before indexing
     * @param int $chunkSize Number of records to process per batch
     * @return void
     */
    protected function indexAllModels(
        bool $shouldForceReindex,
        int $chunkSize
    ): void {
        $modelDiscovery = $this->getModelDiscovery();
        $models = $modelDiscovery->getSearchableModels();

        if (empty($models)) {
            $this->displayNoModelsWarning();
            return;
        }

        $this->displayModelsForIndexing($models);

        if ($shouldForceReindex) {
            $this->showWarning('Clearing all existing indexes...');
            $this->getSearchService()->getIndexManager()->reindexAll();
        } else {
            foreach ($models as $modelClass) {
                $this->indexSpecificModel(
                    modelClass: $modelClass,
                    shouldForceReindex: false,
                    chunkSize: $chunkSize
                );
            }
        }

        $this->displayFinalStatistics();
    }

    /**
     * Display all discoverable models without performing indexing.
     *
     * @return void
     */
    protected function displayDiscoverableModels(): void
    {
        $modelDiscovery = $this->getModelDiscovery();
        $configuredModels = config('fuzzy.searchable_models', []);
        $discoveredModels = $modelDiscovery->getSearchableModels();

        $this->showHeader('Current Configuration');
        $this->displayConfigurationModels($configuredModels);
        $this->displayAutoDiscoveredModels($discoveredModels);
        $this->displayValidModelsSummary($configuredModels, $discoveredModels);
        $this->displayUsageGuidance();
    }

    /**
     * Perform batch indexing for a model with progress bar.
     *
     * @param string $modelClass The model class to index
     * @param int $chunkSize Number of records to process per batch
     * @return void
     */
    private function performBatchIndexing(string $modelClass, int $chunkSize): void
    {
        /** @var Model&MustFuzzySearch $modelClass */
        $modelClass::chunk($chunkSize, function ($models) use ($modelClass): void {
            $progressBar = $this->output->createProgressBar(count($models));
            $progressBar->start();

            /** @var Model&MustFuzzySearch $model */
            foreach ($models as $model) {
                if (get_class($model) === $modelClass && $model->shouldBeIndexed()) {
                    $this->getSearchService()->getIndexManager()->indexModel($model);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->showNewLine();
        });
    }

    /**
     * Display indexing statistics for a specific model.
     *
     * @param string $modelClass The model class to display statistics for
     * @return void
     */
    private function displayModelIndexingStatistics(string $modelClass): void
    {
        $stats = $this->calculatePreciseModelStatistics($modelClass);

        $this->showSuccess("Indexed {$stats['indexed_entries']} entries for {$modelClass}");

        if ($stats['indexed_models'] > 0) {
            $coveragePercentage = $stats['total_records'] > 0
                ? round(($stats['indexed_models'] / $stats['total_records']) * self::PERCENTAGE_MULTIPLIER, 1)
                : 0;

            $this->line("  Indexed models: {$stats['indexed_models']} out of {$stats['total_records']} total records ({$coveragePercentage}%)");

            if ($stats['indexed_models'] < $stats['total_records'] && $stats['skipped_records'] > 0) {
                $skippedPercentage = round(($stats['skipped_records'] / $stats['total_records']) * self::PERCENTAGE_MULTIPLIER, 1);
                $this->line("  Skipped records: {$stats['skipped_records']} ({$skippedPercentage}% - due to shouldBeIndexed())");
            }
        } else {
            $this->showWarning("  No models were indexed - check shouldBeIndexed() method");
        }
    }

    /**
     * Calculate precise statistics for model indexing.
     *
     * @param string $modelClass The model class to calculate statistics for
     * @return array{
     *     total_records: int,
     *     indexed_models: int,
     *     skipped_records: int,
     *     indexed_entries: int,
     *     fields_per_model: int
     * }
     */
    private function calculatePreciseModelStatistics(string $modelClass): array
    {
        $searchService = $this->getSearchService();
        $serviceStats = $searchService->getIndexManager()->getStats();
        $indexedEntries = $serviceStats['models'][$modelClass]['count'] ?? 0;

        $modelInstance = new $modelClass();
        $searchableFields = $modelInstance->getSearchableFields();
        $fieldsPerModel = count($searchableFields);

        $totalRecords = 0;
        $indexedModels = 0;
        $skippedRecords = 0;

        /** @var Model&MustFuzzySearch $modelClass */
        $modelClass::chunk(self::STATS_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexedModels, &$skippedRecords) {
            $totalRecords += count($models);

            /** @var Model&MustFuzzySearch $model */
            foreach ($models as $model) {
                if ($model->shouldBeIndexed()) {
                    $indexedModels++;
                } else {
                    $skippedRecords++;
                }
            }
        });

        if ($indexedModels === 0 && $indexedEntries > 0 && $fieldsPerModel > 0) {
            $estimatedIndexedModels = (int) round($indexedEntries / $fieldsPerModel);
            $indexedModels = min($estimatedIndexedModels, $totalRecords);
            $skippedRecords = $totalRecords - $indexedModels;
        }

        return [
            'total_records' => $totalRecords,
            'indexed_models' => $indexedModels,
            'skipped_records' => $skippedRecords,
            'indexed_entries' => $indexedEntries,
            'fields_per_model' => $fieldsPerModel,
        ];
    }

    /**
     * Display models that will be indexed.
     *
     * @param array<int, string> $models List of model classes to index
     * @return void
     */
    private function displayModelsForIndexing(array $models): void
    {
        $this->showInfo('Starting full search index...');
        $this->showInfo('Found ' . count($models) . ' searchable model(s):');

        $configuredModels = config('fuzzy.searchable_models', []);

        foreach ($models as $model) {
            $source = in_array($model, $configuredModels) ? 'config' : 'auto-discovered';
            $this->line("  - {$model} ({$source})");
        }

        $this->showNewLine();
    }

    /**
     * Display warning when no searchable models are found.
     *
     * @return void
     */
    private function displayNoModelsWarning(): void
    {
        $this->showWarning('No searchable models found.');
        $this->showWarning('Make sure your models:');
        $this->showWarning('1. Implement the MustFuzzySearch interface');
        $this->showWarning('2. Use the FuzzySearchable trait');
        $this->showWarning('');
        $this->showWarning('You can either:');
        $this->showWarning('a) Add models to config/fuzzy.php (searchable_models array)');
        $this->showWarning('b) Place models in app/Models/ directory (auto-discovery is always active)');
    }

    /**
     * Display final indexing statistics.
     *
     * @return void
     */
    private function displayFinalStatistics(): void
    {
        $stats = $this->getSearchService()->getIndexManager()->getStats();
        $this->showSuccess("Indexing complete!");
        $this->showInfo('Total entries: ' . $stats['total_entries']);

        foreach ($stats['models'] as $model => $modelStats) {
            $this->line("  {$model}: {$modelStats['count']} entries");
        }
    }

    /**
     * Display models configured in the configuration file.
     *
     * @param array<int, string> $configuredModels List of configured model classes
     * @return void
     */
    private function displayConfigurationModels(array $configuredModels): void
    {
        if (empty($configuredModels)) {
            $this->showWarning('No models configured in config/fuzzy.php');
            return;
        }

        $this->showInfo('Manually configured models:');
        foreach ($configuredModels as $model) {
            $classExists = class_exists($model) ? '✓' : '✗';
            $isSearchable = $this->getModelDiscovery()->isValidModel($model) ? '✓' : '✗';
            $this->line("  {$classExists}{$isSearchable} {$model}");
        }
    }

    /**
     * Display models discovered through auto-discovery.
     *
     * @param array<int, string> $discoveredModels List of discovered model classes
     * @return void
     */
    private function displayAutoDiscoveredModels(array $discoveredModels): void
    {
        $this->showInfo('Auto-discovered models:');

        if (empty($discoveredModels)) {
            $this->showWarning('No models found via auto-discovery');
            return;
        }

        foreach ($discoveredModels as $model) {
            $this->line("  ✓ {$model}");
        }
    }

    /**
     * Display summary of valid searchable models.
     *
     * @param array<int, string> $configuredModels Configured model classes
     * @param array<int, string> $discoveredModels Discovered model classes
     * @return void
     */
    private function displayValidModelsSummary(array $configuredModels, array $discoveredModels): void
    {
        $this->showHeader('Combined Result (what will be indexed)');

        $allModels = array_unique(array_merge($configuredModels, $discoveredModels));
        $modelDiscovery = $this->getModelDiscovery();
        $validModels = array_filter($allModels, fn($model) => $modelDiscovery->isValidModel($model));

        if (empty($validModels)) {
            $this->showError('No valid searchable models found!');
            return;
        }

        $this->showInfo('Valid searchable models:');

        foreach ($validModels as $model) {
            $source = in_array($model, $configuredModels) ? 'config' : 'auto';
            $this->line("  ✓ {$model} ({$source})");
        }
    }

    /**
     * Display usage instructions for the command.
     *
     * @return void
     */
    private function displayUsageGuidance(): void
    {
        $this->showNewLine();
        $this->showInfo('Usage:');
        $this->line('  php artisan fuzzy:index              # Index all (config + auto-discovered)');
        $this->line('  php artisan fuzzy:index --force      # Force reindex');
        $this->line('  php artisan fuzzy:index --list       # List models only');
        $this->line('  php artisan fuzzy:index User         # Index specific model');
    }

    /**
     * Get the search service instance from the container.
     *
     * @return SearchServiceInterface
     */
    private function getSearchService(): SearchServiceInterface
    {
        return app(SearchServiceInterface::class);
    }

    /**
     * Get the model discovery service from the container.
     *
     * @return ModelDiscoveryInterface
     */
    private function getModelDiscovery(): ModelDiscoveryInterface
    {
        return app(ModelDiscoveryInterface::class);
    }
}
