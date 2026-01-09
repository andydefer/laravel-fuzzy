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
        // MODIFICATION CLAVIER : COMBINER AUTOMATIQUEMENT LES DEUX SOURCES
        $models = [];

        // 1. Récupérer les modèles configurés manuellement
        $configuredModels = config('fuzzy.searchable_models', []);

        // 2. Auto-détection TOUJOURS activée (sauf si désactivée dans config)
        $autoDiscoveryEnabled = config('fuzzy.auto_discovery.enabled', true);
        $discoveredModels = $autoDiscoveryEnabled ? $this->discoverSearchableModels() : [];

        // 3. Fusionner les deux listes (éliminer les doublons)
        $allModels = array_unique(array_merge($configuredModels, $discoveredModels));

        // 4. Filtrer uniquement les modèles valides
        $models = array_filter($allModels, function ($modelClass) {
            if (!class_exists($modelClass)) {
                return false;
            }

            $reflection = new ReflectionClass($modelClass);
            return $reflection->implementsInterface(MustFuzzySearch::class);
        });

        if (empty($models)) {
            $this->warn('No searchable models found.');
            $this->warn('Make sure your models:');
            $this->warn('1. Implement the MustFuzzySearch interface');
            $this->warn('2. Use the FuzzySearchable trait');
            $this->warn('');
            $this->warn('You can either:');
            $this->warn('a) Add models to config/fuzzy.php (searchable_models array)');
            $this->warn('b) Place models in app/Models/ directory');
            return;
        }

        $this->info('Starting full search index...');
        $this->info('Found ' . count($models) . ' searchable model(s):');

        // Afficher la source de chaque modèle
        foreach ($models as $model) {
            $source = in_array($model, $configuredModels) ? 'config' : 'auto-discovered';
            $this->info("  - {$model} ({$source})");
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
        $this->info('=== Current Configuration ===');

        $configuredModels = config('fuzzy.searchable_models', []);
        $autoDiscoveryEnabled = config('fuzzy.auto_discovery.enabled', true);

        if ($autoDiscoveryEnabled) {
            $discoveredModels = $this->discoverSearchableModels();
        } else {
            $discoveredModels = [];
            $this->warn('Auto-discovery is disabled in config');
        }

        // Modèles configurés manuellement
        if (empty($configuredModels)) {
            $this->warn('No models configured in config/fuzzy.php');
        } else {
            $this->info('Manually configured models:');
            foreach ($configuredModels as $model) {
                $exists = class_exists($model) ? '✓' : '✗';
                $implements = class_exists($model) &&
                    in_array(MustFuzzySearch::class, class_implements($model)) ? '✓' : '✗';
                $this->info("  {$exists}{$implements} {$model}");
            }
        }

        $this->newLine();

        // Modèles auto-détectés
        if ($autoDiscoveryEnabled) {
            $this->info('Auto-discovered models:');
            if (empty($discoveredModels)) {
                $this->warn('No models found via auto-discovery');
            } else {
                foreach ($discoveredModels as $model) {
                    $this->info("  ✓ {$model}");
                }
            }
        }

        $this->newLine();
        $this->info('=== Combined Result (what will be indexed) ===');

        $allModels = array_unique(array_merge($configuredModels, $discoveredModels));
        $validModels = array_filter($allModels, function ($modelClass) {
            if (!class_exists($modelClass)) {
                return false;
            }

            $reflection = new ReflectionClass($modelClass);
            return $reflection->implementsInterface(MustFuzzySearch::class);
        });

        if (empty($validModels)) {
            $this->error('No valid searchable models found!');
        } else {
            $this->info('Valid searchable models:');
            foreach ($validModels as $model) {
                $source = in_array($model, $configuredModels) ? 'config' : 'auto';
                $this->info("  ✓ {$model} ({$source})");
            }
        }

        $this->newLine();
        $this->info('Usage:');
        $this->info('  php artisan fuzzy:index              # Index all (config + auto-discovered)');
        $this->info('  php artisan fuzzy:index --force      # Force reindex');
        $this->info('  php artisan fuzzy:index --list       # List models only');
        $this->info('  php artisan fuzzy:index User         # Index specific model');
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
