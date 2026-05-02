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
 * Discovers all models implementing MustFuzzySearch interface and indexes
 * those that should be indexed according to their shouldBeIndexed() method.
 * Provides detailed progress reporting and statistics on indexed entries.
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

        $this->showInfo("Processing model: {$modelClass}");

        if ($shouldForceReindex) {
            $this->showWarning("Clearing existing index for {$modelClass}...");
            $this->getSearchService()->getIndexManager()->reindexModel($modelClass);
        } else {
            $this->performIncrementalIndexing($modelClass, $chunkSize);
        }

        $this->displayModelIndexingStatistics($modelClass);
    }

    /**
     * Index all searchable models.
     *
     * @param bool $shouldForceReindex Whether to clear existing index before indexing
     * @param int $chunkSize Number of records to process per batch
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
                $this->performIncrementalIndexing($modelClass, $chunkSize);
            }
        }

        $this->displayFinalStatistics();
    }

    /**
     * Perform incremental indexing for a model (only new/updated records).
     *
     * This method only indexes records that need to be indexed, without
     * clearing the existing index first.
     *
     * @param string $modelClass The model class to index
     * @param int $chunkSize Number of records to process per batch
     */
    private function performIncrementalIndexing(string $modelClass, int $chunkSize): void
    {
        $this->showInfo("Indexing model: {$modelClass}");

        /** @var Model&MustFuzzySearch $modelClass */
        $totalRecords = $modelClass::count();

        if ($totalRecords === 0) {
            $this->showWarning("No records found for {$modelClass}");
            return;
        }

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        $modelClass::chunk($chunkSize, function ($models) use ($progressBar, $modelClass): void {
            /** @var Model&MustFuzzySearch $model */
            foreach ($models as $model) {
                if (get_class($model) === $modelClass && $model->shouldBeIndexed()) {
                    $this->getSearchService()->getIndexManager()->indexModel($model);
                }
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->showNewLine();
    }

    /**
     * Display all discoverable models without performing indexing.
     */
    protected function displayDiscoverableModels(): void
    {
        $modelDiscovery = $this->getModelDiscovery();
        $models = $modelDiscovery->getSearchableModels();

        $this->showHeader('Discoverable Models');
        $this->showInfo('Models that implement ' . MustFuzzySearch::class . ':');

        if (empty($models)) {
            $this->showWarning('No discoverable models found.');
        } else {
            foreach ($models as $model) {
                $this->line("  ✓ {$model}");
            }
        }

        $this->displayUsageGuidance();
    }

    /**
     * Display indexing statistics for a specific model.
     *
     * @param string $modelClass The model class to display statistics for
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
     */
    private function displayModelsForIndexing(array $models): void
    {
        $this->showInfo('Starting full search index...');
        $this->showInfo('Found ' . count($models) . ' searchable model(s):');

        foreach ($models as $model) {
            $this->line("  - {$model}");
        }

        $this->showNewLine();
    }

    /**
     * Display warning when no searchable models are found.
     */
    private function displayNoModelsWarning(): void
    {
        $this->showWarning('No searchable models found.');
        $this->showWarning('Make sure your models:');
        $this->showWarning('1. Implement the ' . MustFuzzySearch::class . ' interface');
        $this->showWarning('2. Use the FuzzySearchable trait');
        $this->showWarning('3. Are placed in a directory scanned by the package (app/Models or tests/Fixtures)');
    }

    /**
     * Display final indexing statistics.
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
     * Display usage instructions for the command.
     */
    private function displayUsageGuidance(): void
    {
        $this->showNewLine();
        $this->showInfo('Usage:');
        $this->line('  php artisan fuzzy:index              # Incremental index (only new/updated)');
        $this->line('  php artisan fuzzy:index --force      # Full reindex (clear all first)');
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
