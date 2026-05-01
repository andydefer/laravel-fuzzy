# Rector Refactoring Report
*Generated: ven. 01 mai 2026 18:00:35 WAT*


85 files with changes
=====================

1) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/ClearCacheCommand.php:39

    ---------- begin diff ----------
@@ @@

     /**
      * Execute the console command.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ @@

     /**
      * Clear only statistics cache.
-     *
-     * @return void
      */
     private function clearStatisticsCache(): void
     {
@@ @@
      * Clear cache for a specific model.
      *
      * @param string $modelClass The model class to clear cache for
-     * @return void
      */
     private function clearCacheForSpecificModel(string $modelClass): void
     {
@@ @@

     /**
      * Clear entire fuzzy search cache.
-     *
-     * @return void
      */
     private function clearEntireCache(): void
     {
@@ @@

     /**
      * Get the search service instance from the container.
-     *
-     * @return SearchServiceInterface
      */
     private function getSearchService(): SearchServiceInterface
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


2) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/ClearIndexCommand.php:41

    ---------- begin diff ----------
@@ @@
      *
      * Routes to either clear a specific model's index or all indexes
      * based on provided arguments.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ @@
      *
      * @param string $modelClass The fully qualified model class name
      * @param bool $shouldSkipConfirmation Whether to bypass user confirmation
-     * @return void
      */
     protected function clearModelIndex(string $modelClass, bool $shouldSkipConfirmation): void
     {
@@ @@
      * Clear all search index entries from the database.
      *
      * @param bool $shouldSkipConfirmation Whether to bypass user confirmation
-     * @return void
      */
     protected function clearAllIndexes(bool $shouldSkipConfirmation): void
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


3) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/IndexSearchCommand.php:24

    ---------- begin diff ----------
@@ @@
     use CommandHelpers;

     /**
-     * Default chunk size for batch processing when not specified in options.
-     */
-    private const DEFAULT_CHUNK_SIZE = 100;
-
-    /**
      * Chunk size used for statistics calculation to avoid memory issues.
      */
     private const STATS_CHUNK_SIZE = 1000;
@@ @@

     /**
      * Execute the console command.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ @@
      * @param string $modelClass The fully qualified model class name
      * @param bool $shouldForceReindex Whether to clear existing index before indexing
      * @param int $chunkSize Number of records to process per batch
-     * @return void
      */
     protected function indexSpecificModel(
         string $modelClass,
@@ @@
         $modelDiscovery = $this->getModelDiscovery();

         if (!$modelDiscovery->isValidModel($modelClass)) {
-            $this->showError("Model {$modelClass} must implement " . MustFuzzySearch::class);
+            $this->showError(sprintf('Model %s must implement ', $modelClass) . MustFuzzySearch::class);
             return;
         }

-        $this->showInfo("Indexing model: {$modelClass}");
+        $this->showInfo('Indexing model: ' . $modelClass);

         if ($shouldForceReindex) {
-            $this->showWarning("Clearing existing index for {$modelClass}...");
+            $this->showWarning(sprintf('Clearing existing index for %s...', $modelClass));
             $this->getSearchService()->getIndexManager()->reindexModel($modelClass);
         } else {
             $this->performBatchIndexing($modelClass, $chunkSize);
@@ @@
      *
      * @param bool $shouldForceReindex Whether to clear existing index before indexing
      * @param int $chunkSize Number of records to process per batch
-     * @return void
      */
     protected function indexAllModels(
         bool $shouldForceReindex,
@@ @@
         $modelDiscovery = $this->getModelDiscovery();
         $models = $modelDiscovery->getSearchableModels();

-        if (empty($models)) {
+        if ($models === []) {
             $this->displayNoModelsWarning();
             return;
         }
@@ @@

     /**
      * Display all discoverable models without performing indexing.
-     *
-     * @return void
      */
     protected function displayDiscoverableModels(): void
     {
@@ @@
      *
      * @param string $modelClass The model class to index
      * @param int $chunkSize Number of records to process per batch
-     * @return void
      */
     private function performBatchIndexing(string $modelClass, int $chunkSize): void
     {
@@ @@
      * Display indexing statistics for a specific model.
      *
      * @param string $modelClass The model class to display statistics for
-     * @return void
      */
     private function displayModelIndexingStatistics(string $modelClass): void
     {
         $stats = $this->calculatePreciseModelStatistics($modelClass);

-        $this->showSuccess("Indexed {$stats['indexed_entries']} entries for {$modelClass}");
+        $this->showSuccess(sprintf('Indexed %d entries for %s', $stats['indexed_entries'], $modelClass));

         if ($stats['indexed_models'] > 0) {
             $coveragePercentage = $stats['total_records'] > 0
@@ @@
                 ? round(($stats['indexed_models'] / $stats['total_records']) * self::PERCENTAGE_MULTIPLIER, 1)
                 : 0;

-            $this->line("  Indexed models: {$stats['indexed_models']} out of {$stats['total_records']} total records ({$coveragePercentage}%)");
+            $this->line(sprintf('  Indexed models: %d out of %d total records (%s%%)', $stats['indexed_models'], $stats['total_records'], $coveragePercentage));

             if ($stats['indexed_models'] < $stats['total_records'] && $stats['skipped_records'] > 0) {
                 $skippedPercentage = round(($stats['skipped_records'] / $stats['total_records']) * self::PERCENTAGE_MULTIPLIER, 1);
-                $this->line("  Skipped records: {$stats['skipped_records']} ({$skippedPercentage}% - due to shouldBeIndexed())");
+                $this->line(sprintf('  Skipped records: %d (%s%% - due to shouldBeIndexed())', $stats['skipped_records'], $skippedPercentage));
             }
         } else {
             $this->showWarning("  No models were indexed - check shouldBeIndexed() method");
@@ @@
         $skippedRecords = 0;

         /** @var Model&MustFuzzySearch $modelClass */
-        $modelClass::chunk(self::STATS_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexedModels, &$skippedRecords) {
+        $modelClass::chunk(self::STATS_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexedModels, &$skippedRecords): void {
             $totalRecords += count($models);

             /** @var Model&MustFuzzySearch $model */
             foreach ($models as $model) {
                 if ($model->shouldBeIndexed()) {
-                    $indexedModels++;
+                    ++$indexedModels;
                 } else {
-                    $skippedRecords++;
+                    ++$skippedRecords;
                 }
             }
         });
@@ @@
      * Display models that will be indexed.
      *
      * @param array<int, string> $models List of model classes to index
-     * @return void
      */
     private function displayModelsForIndexing(array $models): void
     {
@@ @@

         foreach ($models as $model) {
             $source = in_array($model, $configuredModels) ? 'config' : 'auto-discovered';
-            $this->line("  - {$model} ({$source})");
+            $this->line(sprintf('  - %s (%s)', $model, $source));
         }

         $this->showNewLine();
@@ @@

     /**
      * Display warning when no searchable models are found.
-     *
-     * @return void
      */
     private function displayNoModelsWarning(): void
     {
@@ @@

     /**
      * Display final indexing statistics.
-     *
-     * @return void
      */
     private function displayFinalStatistics(): void
     {
@@ @@
         $this->showInfo('Total entries: ' . $stats['total_entries']);

         foreach ($stats['models'] as $model => $modelStats) {
-            $this->line("  {$model}: {$modelStats['count']} entries");
+            $this->line(sprintf('  %s: %s entries', $model, $modelStats['count']));
         }
     }

@@ @@
      * Display models configured in the configuration file.
      *
      * @param array<int, string> $configuredModels List of configured model classes
-     * @return void
      */
     private function displayConfigurationModels(array $configuredModels): void
     {
-        if (empty($configuredModels)) {
+        if ($configuredModels === []) {
             $this->showWarning('No models configured in config/fuzzy.php');
             return;
         }
@@ @@
         foreach ($configuredModels as $model) {
             $classExists = class_exists($model) ? '✓' : '✗';
             $isSearchable = $this->getModelDiscovery()->isValidModel($model) ? '✓' : '✗';
-            $this->line("  {$classExists}{$isSearchable} {$model}");
+            $this->line(sprintf('  %s%s %s', $classExists, $isSearchable, $model));
         }
     }

@@ @@
      * Display models discovered through auto-discovery.
      *
      * @param array<int, string> $discoveredModels List of discovered model classes
-     * @return void
      */
     private function displayAutoDiscoveredModels(array $discoveredModels): void
     {
         $this->showInfo('Auto-discovered models:');

-        if (empty($discoveredModels)) {
+        if ($discoveredModels === []) {
             $this->showWarning('No models found via auto-discovery');
             return;
         }

         foreach ($discoveredModels as $model) {
-            $this->line("  ✓ {$model}");
+            $this->line('  ✓ ' . $model);
         }
     }

@@ @@
      *
      * @param array<int, string> $configuredModels Configured model classes
      * @param array<int, string> $discoveredModels Discovered model classes
-     * @return void
      */
     private function displayValidModelsSummary(array $configuredModels, array $discoveredModels): void
     {
@@ @@

         $allModels = array_unique(array_merge($configuredModels, $discoveredModels));
         $modelDiscovery = $this->getModelDiscovery();
-        $validModels = array_filter($allModels, fn($model) => $modelDiscovery->isValidModel($model));
+        $validModels = array_filter($allModels, fn(string $model): bool => $modelDiscovery->isValidModel($model));

-        if (empty($validModels)) {
+        if ($validModels === []) {
             $this->showError('No valid searchable models found!');
             return;
         }
@@ @@

         foreach ($validModels as $model) {
             $source = in_array($model, $configuredModels) ? 'config' : 'auto';
-            $this->line("  ✓ {$model} ({$source})");
+            $this->line(sprintf('  ✓ %s (%s)', $model, $source));
         }
     }

     /**
      * Display usage instructions for the command.
-     *
-     * @return void
      */
     private function displayUsageGuidance(): void
     {
@@ @@

     /**
      * Get the search service instance from the container.
-     *
-     * @return SearchServiceInterface
      */
     private function getSearchService(): SearchServiceInterface
     {
@@ @@

     /**
      * Get the model discovery service from the container.
-     *
-     * @return ModelDiscoveryInterface
      */
     private function getModelDiscovery(): ModelDiscoveryInterface
     {
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * RemoveUnusedPrivateClassConstantRector
 * RemoveUselessReturnTagRector
 * AddArrowFunctionReturnTypeRector
 * AddClosureVoidReturnTypeWhereNoReturnRector
 * AddArrayFunctionClosureParamTypeRector


4) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/StatsIndexCommand.php:39

    ---------- begin diff ----------
@@ @@
      *
      * Retrieves and displays comprehensive statistics about the search index,
      * including total entries and per-model breakdown with field counts.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ @@

     /**
      * Get the search service from the container.
-     *
-     * @return SearchServiceInterface
      */
     private function getSearchService(): SearchServiceInterface
     {
@@ @@

     /**
      * Display the command header.
-     *
-     * @return void
      */
     private function displayHeader(): void
     {
@@ @@
      * Display the total number of indexed entries.
      *
      * @param int $totalEntries The total number of indexed entries
-     * @return void
      */
     private function displayTotalEntries(int $totalEntries): void
     {
@@ @@
      * Shows entry counts and field distributions per model in a tabular format.
      *
      * @param array<string, array{count: int, fields: array<string, int>}> $modelsStats
-     * @return void
      */
     private function displayModelStatistics(array $modelsStats): void
     {
@@ @@
         $this->showInfo('Per model statistics:');
         $this->showNewLine();

-        if (empty($modelsStats)) {
+        if ($modelsStats === []) {
             $this->showWarning('No models indexed yet.');
             return;
         }
@@ @@
      * Example: ['name' => 100, 'email' => 50] becomes "name: 100, email: 50"
      *
      * @param array<string, int> $fieldCounts
-     * @return string
      */
     private function formatFieldCounts(array $fieldCounts): string
     {
-        if (empty($fieldCounts)) {
+        if ($fieldCounts === []) {
             return '';
         }

@@ @@
         $formattedParts = [];

         foreach ($fieldCounts as $fieldName => $count) {
-            $formattedParts[] = "{$fieldName}: {$count}";
+            $formattedParts[] = sprintf('%s: %d', $fieldName, $count);
         }

         return implode(', ', $formattedParts);
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * EncapsedStringsToSprintfRector
 * RemoveUselessReturnTagRector


5) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/CoverageBonusConfig.php:57

    ---------- begin diff ----------
@@ @@

     /**
      * Get the full coverage threshold value.
-     *
-     * @return float
      */
     public function getFullCoverageThreshold(): float
     {
@@ @@

     /**
      * Get the high coverage threshold value.
-     *
-     * @return float
      */
     public function getHighCoverageThreshold(): float
     {
@@ @@

     /**
      * Get the full coverage bonus value.
-     *
-     * @return float
      */
     public function getFullCoverageBonus(): float
     {
@@ @@

     /**
      * Get the high coverage bonus value.
-     *
-     * @return float
      */
     public function getHighCoverageBonus(): float
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


6) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/LevenshteinAlgorithmConfig.php:36

    ---------- begin diff ----------
@@ @@
     public const DEFAULT_CLOSE_MATCH_BONUS = 0.1;

     private int $emptyStringLength;
+
     private int $distancePenaltyThreshold;
+
     private float $penaltyFactorBase;
+
     private float $penaltyReductionPerDistance;
+
     private int $closeMatchBonusThreshold;
+
     private int $minLengthForBonus;
+
     private float $closeMatchBonus;
+
     private float $weight;

     private function __construct(
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


7) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/LongestCommonSubstringConfig.php:24

    ---------- begin diff ----------
@@ @@
     public const DEFAULT_MATCH_INCREMENT = 1;

     private int $baseIndex;
+
     private int $matchIncrement;
+
     private float $weight;

     private function __construct(
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


8) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/PrefixAlgorithmConfig.php:27

    ---------- begin diff ----------
@@ @@
     public const DEFAULT_PREFIX_MAX_SCORE = 0.6;

     private int $minPrefixLength;
+
     private float $prefixBaseScore;
+
     private float $prefixVariableMultiplier;
+
     private float $prefixMaxScore;
+
     private float $weight;

     private function __construct(
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


9) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/SimilarityCalculatorConfig.php:143

    ---------- begin diff ----------
@@ @@
     public const DEFAULT_UNMATCHED_LETTER_MULTIPLIER = 1.5;

     private string $regexRemoveSpecialChars;
+
     private string $regexCollapseSpaces;
+
     private string $regexWordSplitter;

     private function __construct(
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


10) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/WordSimilarityComparatorConfig.php:322

    ---------- begin diff ----------
@@ @@
     {
         return $this->scoreMultiplier;
     }
+
     public function getSigma(): float
     {
         return $this->sigma;
     }
+
     public function getHighContainmentRatio(): float
     {
         return $this->highContainmentRatio;
     }
+
     public function getMediumContainmentRatio(): float
     {
         return $this->mediumContainmentRatio;
     }
+
     public function getMinLengthForDivision(): int
     {
         return $this->minLengthForDivision;
     }
+
     public function getBaseIncrement(): int
     {
         return $this->baseIncrement;
     }
+
     public function getStartIndex(): int
     {
         return $this->startIndex;
     }
+
     public function getEmptyTextPenaltyFactor(): int
     {
         return $this->emptyTextPenaltyFactor;
     }
+
     public function getMaxScoreCap(): float
     {
         return $this->maxScoreCap;
     }
+
     public function getUnmatchedLetterPenalty(): float
     {
         return $this->unmatchedLetterPenalty;
     }
+
     public function getUnmatchedLetterMultiplier(): float
     {
         return $this->unmatchedLetterMultiplier;
     }
+
     public function getWordPenaltyPerChar(): float
     {
         return $this->wordPenaltyPerChar;
     }
+
     public function getLengthPenaltyMultiplier(): float
     {
         return $this->lengthPenaltyMultiplier;
     }
+
     public function getMinimalPenalty(): float
     {
         return $this->minimalPenalty;
     }
+
     public function getMatchFuzzinessPenalty(): float
     {
         return $this->matchFuzzinessPenalty;
     }
+
     public function getMinWordMatchRatio(): float
     {
         return $this->minWordMatchRatio;
     }
+
     public function getShortWordThreshold(): int
     {
         return $this->shortWordThreshold;
     }
+
     public function getVeryBadMatchThreshold(): float
     {
         return $this->veryBadMatchThreshold;
     }
+
     public function getVeryBadMatchPenalty(): float
     {
         return $this->veryBadMatchPenalty;
     }
+
     public function getStrictnessFactorPerWord(): float
     {
         return $this->strictnessFactorPerWord;
     }
+
     public function getRealSimilarityThreshold(): float
     {
         return $this->realSimilarityThreshold;
     }
+
     public function getRealSimilarityBasePenalty(): float
     {
         return $this->realSimilarityBasePenalty;
     }
+
     public function getRealSimilarityMultiplier(): float
     {
         return $this->realSimilarityMultiplier;
     }
+
     public function getLowSimilarityThreshold(): float
     {
         return $this->lowSimilarityThreshold;
     }
+
     public function getLowSimilarityPenalty(): float
     {
         return $this->lowSimilarityPenalty;
     }
+
     public function getBasicSimilarityThreshold(): float
     {
         return $this->basicSimilarityThreshold;
     }
+
     public function getBasicSimilarityFallback(): float
     {
         return $this->basicSimilarityFallback;
     }
+
     public function getLengthDifferencePenalty(): float
     {
         return $this->lengthDifferencePenalty;
     }
+
     public function getPhoneticReductionFactor(): float
     {
         return $this->phoneticReductionFactor;
     }
+
     public function getLowGlobalSimilarityThreshold(): float
     {
         return $this->lowGlobalSimilarityThreshold;
     }
+
     public function getLowGlobalSimilarityFallback(): float
     {
         return $this->lowGlobalSimilarityFallback;
     }
+
     public function getSearchWindowSize(): int
     {
         return $this->searchWindowSize;
     }
+
     public function getMatchDistanceZeroPenalty(): float
     {
         return $this->matchDistanceZeroPenalty;
     }
+
     public function getMaxCeiling(): float
     {
         return $this->maxCeiling;
     }
+
     public function getCeilingDivisor(): float
     {
         return $this->ceilingDivisor;
     }
+
     public function getPenaltyAdjustmentBase(): float
     {
         return $this->penaltyAdjustmentBase;
     }
+
     public function getMaxAdjustedPenalty(): float
     {
         return $this->maxAdjustedPenalty;
     }
+
     public function getPhoneticContextRadius(): int
     {
         return $this->phoneticContextRadius;
     }
+
     public function getPhoneticReductionExactContext(): float
     {
         return $this->phoneticReductionExactContext;
     }
+
     public function getPhoneticReductionSimilarContext(): float
     {
         return $this->phoneticReductionSimilarContext;
     }
+
     public function getPhoneticSimilarityPercentThreshold(): float
     {
         return $this->phoneticSimilarityPercentThreshold;
     }
+
     public function getImperfectMatchPenalty(): float
     {
         return $this->imperfectMatchPenalty;
     }
+
     public function getContainmentRatioHigh(): float
     {
         return $this->containmentRatioHigh;
     }
+
     public function getContainmentRatioMedium(): float
     {
         return $this->containmentRatioMedium;
     }
+
     public function getContainmentHighMultiplier(): float
     {
         return $this->containmentHighMultiplier;
     }
+
     public function getContainmentMediumMultiplier(): float
     {
         return $this->containmentMediumMultiplier;
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


11) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/CacheManagerInterface.php:34

    ---------- begin diff ----------
@@ @@
      *
      * Clears every cache key that was stored through the cache keys tracking
      * system. This is useful after bulk operations like reindexing all models.
-     *
-     * @return void
      */
     public function invalidateAll(): void;

@@ @@
      * results for queries that included this model type.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      */
     public function invalidateForModel(string $modelClass): void;

@@ @@
      * Clears only the cached statistics data without affecting other cached data.
      * This is useful after index modifications where statistics need to be refreshed
      * but search results cache should remain valid.
-     *
-     * @return void
      */
     public function invalidateStatsCache(): void;
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


12) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/ConfigInterface.php:15

    ---------- begin diff ----------
@@ @@
 {
     /**
      * Create configuration instance from Laravel config with fallback to defaults.
-     *
-     * @return self
      */
     public static function fromConfig(): self;

     /**
      * Create configuration instance with default values.
-     *
-     * @return self
      */
     public static function createDefault(): self;
 }
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


13) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/IndexManagerInterface.php:23

    ---------- begin diff ----------
@@ @@
      * Should respect the model's `shouldBeIndexed()` method.
      *
      * @param MustFuzzySearch $model The model instance to index
-     * @return void
      */
     public function indexModel(MustFuzzySearch $model): void;

@@ @@
      * with current data. Useful after model updates.
      *
      * @param MustFuzzySearch $model The model instance to update in the index
-     * @return void
      */
     public function updateModelIndex(MustFuzzySearch $model): void;

@@ @@
      * Called automatically when a model is deleted.
      *
      * @param MustFuzzySearch $model The model instance to remove from the index
-     * @return void
      */
     public function removeModel(MustFuzzySearch $model): void;

@@ @@
      *
      * Truncates the entire index and rebuilds it from scratch
      * for all models that implement MustFuzzySearch.
-     *
-     * @return void
      */
     public function reindexAll(): void;

@@ @@
      * by iterating through all instances of that model.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      */
     public function reindexModel(string $modelClass): void;
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


14) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/IndexRepositoryInterface.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Contracts;

+use Illuminate\Database\Eloquent\Model;
 use Illuminate\Support\Collection;

 /**
@@ @@
      *
      * @param string $modelClass Fully qualified model class name
      * @param array<int> $ids Array of model IDs to retrieve
-     * @return Collection<int, \Illuminate\Database\Eloquent\Model> Collection of retrieved models
+     * @return Collection<int, Model> Collection of retrieved models
      */
     public function getModelsBatch(string $modelClass, array $ids): Collection;

@@ @@
      * Provides quick lookup of models that have already been loaded
      * to avoid redundant database queries.
      *
-     * @return array<string, array<int, \Illuminate\Database\Eloquent\Model>>
+     * @return array<string, array<int, Model>>
      *         Map keyed by model class with arrays of model instances
      */
     public function getPreloadedModelsMap(): array;
    ----------- end diff -----------

Applied rules:


15) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/ModelDiscoveryInterface.php:46

    ---------- begin diff ----------
@@ @@
      *
      * @param string $modelClass Fully qualified model class name to validate
      * @throws ModelNotSearchableException When the model does not implement MustFuzzySearch
-     * @return void
      */
     public function validateModel(string $modelClass): void;
 }
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


16) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/SearchContextInterface.php:120

    ---------- begin diff ----------
@@ @@
      * before being processed by the ScoringStage.
      *
      * @param array<string, mixed> $match Raw match data containing index entry information
-     * @return void
      */
     public function addPotentialMatch(array $match): void;
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


17) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/Algorithms/WordSimilarity/LetterDistanceCalculatorTest.php:21

    ---------- begin diff ----------
@@ @@
     public function test_calculate_letter_distance_identical_strings(): void
     {
         $distance = $this->calculator->calculateLetterDistance('hello', 'hello');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distance);
     }

     public function test_calculate_letter_distance_similar_strings(): void
@@ @@
         $distanceSame = $this->calculator->calculateLetterDistance('a', 'a');
         $distanceDiff = $this->calculator->calculateLetterDistance('a', 'b');

-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distanceSame);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distanceSame);
         $this->assertGreaterThan(0, $distanceDiff);
     }

@@ @@
     public function test_calculate_letter_distance_with_single_character_matching(): void
     {
         $distance = $this->calculator->calculateLetterDistance('a', 'a');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distance);
     }

     public function test_calculate_letter_distance_with_empty_strings(): void
     {
         $distance = $this->calculator->calculateLetterDistance('', '');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distance);

         $distance2 = $this->calculator->calculateLetterDistance('abc', '');
         $this->assertGreaterThan(0, $distance2);
@@ @@
     public function test_calculate_letter_distance_with_numbers(): void
     {
         $distance = $this->calculator->calculateLetterDistance('123', '123');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distance);

         $distance2 = $this->calculator->calculateLetterDistance('123', '124');
         $this->assertGreaterThan(0, $distance2);
    ----------- end diff -----------

Applied rules:
 * AssertEqualsToSameRector


18) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/Algorithms/WordSimilarity/WordSimilarityCalculatorTest.php:21

    ---------- begin diff ----------
@@ @@
     public function test_calculate_word_similarity_exact_match(): void
     {
         $score = $this->calculator->calculateWordSimilarity('hello', 'hello');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $score);
     }

     public function test_calculate_word_similarity_contained_word(): void
@@ @@
     public function test_calculate_word_real_similarity_identical_letters(): void
     {
         $similarity = $this->calculator->calculateWordRealSimilarity('abc', 'abc');
-        $this->assertEquals(1.0, $similarity);
+        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
     }

     public function test_calculate_word_real_similarity_partial_letters(): void
     {
         $similarity = $this->calculator->calculateWordRealSimilarity('abc', 'abd');
-        $this->assertEquals(0.5, $similarity);
+        $this->assertEqualsWithDelta(0.5, $similarity, PHP_FLOAT_EPSILON);
     }

     public function test_calculate_word_real_similarity_no_common_letters(): void
     {
         $similarity = $this->calculator->calculateWordRealSimilarity('abc', 'xyz');
-        $this->assertEquals(0.0, $similarity);
+        $this->assertEqualsWithDelta(0.0, $similarity, PHP_FLOAT_EPSILON);
     }

     public function test_calculate_word_similarity_with_phonetic_similarity(): void
@@ @@
     {
         $similarity = $this->calculator->calculateWordRealSimilarity('AbC', 'aBc');
         // Après normalisation, les deux deviennent 'abc' -> similarité parfaite
-        $this->assertEquals(1.0, $similarity);
+        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
     }

     public function test_calculate_word_real_similarity_with_repeated_letters(): void
     {
         $similarity = $this->calculator->calculateWordRealSimilarity('aaa', 'aab');
-        $this->assertEquals(0.5, $similarity);
+        $this->assertEqualsWithDelta(0.5, $similarity, PHP_FLOAT_EPSILON);
     }

     public function test_calculate_word_similarity_with_empty_words(): void
     {
         $score = $this->calculator->calculateWordSimilarity('', '');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $score);

         $score2 = $this->calculator->calculateWordSimilarity('hello', '');
         $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score2);
@@ @@
     public function test_calculate_word_similarity_single_letter(): void
     {
         $score = $this->calculator->calculateWordSimilarity('a', 'a');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $score);

         $score2 = $this->calculator->calculateWordSimilarity('a', 'b');
         $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score2);
    ----------- end diff -----------

Applied rules:
 * AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector
 * AssertEqualsToSameRector


19) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/CacheManagerServiceTest.php:52

    ---------- begin diff ----------
@@ @@
         $this->cacheManager = new CacheManagerService();

         $executed = false;
-        $result = $this->cacheManager->remember('test', function () use (&$executed) {
+        $result = $this->cacheManager->remember('test', function () use (&$executed): string {
             $executed = true;
             return 'callback_result';
         }, []);
@@ @@

         $callbackExecutions = 0;

-        $result1 = $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
-            $callbackExecutions++;
+        $result1 = $this->cacheManager->remember('test', function () use (&$callbackExecutions): string {
+            ++$callbackExecutions;
             return 'cached_value';
         }, ['param1']);

-        $result2 = $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
-            $callbackExecutions++;
+        $result2 = $this->cacheManager->remember('test', function () use (&$callbackExecutions): string {
+            ++$callbackExecutions;
             return 'cached_value';
         }, ['param1']);

-        $this->assertEquals(1, $callbackExecutions);
+        $this->assertSame(1, $callbackExecutions);
         $this->assertEquals('cached_value', $result1);
         $this->assertEquals('cached_value', $result2);
     }
@@ @@

         $callbackExecutions = 0;

-        $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
-            $callbackExecutions++;
+        $this->cacheManager->remember('test', function () use (&$callbackExecutions): string {
+            ++$callbackExecutions;
             return 'value1';
         }, ['param1']);

-        $this->cacheManager->remember('test', function () use (&$callbackExecutions) {
-            $callbackExecutions++;
+        $this->cacheManager->remember('test', function () use (&$callbackExecutions): string {
+            ++$callbackExecutions;
             return 'value2';
         }, ['param2']);

-        $this->assertEquals(2, $callbackExecutions);
+        $this->assertSame(2, $callbackExecutions);
     }

     public function test_remember_stores_model_metadata_for_search_in_model(): void
@@ @@

         $userParams = [User::class, 'john', []];

-        $this->cacheManager->remember('search_in_model', fn() => 'user_result', $userParams);
+        $this->cacheManager->remember('search_in_model', fn(): string => 'user_result', $userParams);

         $storageKey = $this->getCacheKeysStorageKey();
         $storedKeys = Cache::get($storageKey, []);
@@ @@
         config(['fuzzy.cache.enabled' => true]);
         $this->cacheManager = new CacheManagerService();

-        $this->cacheManager->remember('test', fn() => 'value', []);
+        $this->cacheManager->remember('test', fn(): string => 'value', []);
         $this->cacheManager->invalidateAll();

         $callbackExecuted = false;
-        $result = $this->cacheManager->remember('test', function () use (&$callbackExecuted) {
+        $result = $this->cacheManager->remember('test', function () use (&$callbackExecuted): string {
             $callbackExecuted = true;
             return 'new_value';
         }, []);
@@ @@
         config(['fuzzy.cache.enabled' => true]);
         $this->cacheManager = new CacheManagerService();

-        $this->cacheManager->remember('test', fn() => 'value', []);
+        $this->cacheManager->remember('test', fn(): string => 'value', []);

         $storageKey = $this->getCacheKeysStorageKey();
         $this->assertNotNull(Cache::get($storageKey));
@@ @@
         $productParams = [Product::class, 'laptop', []];

         // Mettre en cache des résultats pour User et Product
-        $this->cacheManager->remember('search_in_model', fn() => 'user_result', $userParams);
-        $this->cacheManager->remember('search_in_model', fn() => 'product_result', $productParams);
+        $this->cacheManager->remember('search_in_model', fn(): string => 'user_result', $userParams);
+        $this->cacheManager->remember('search_in_model', fn(): string => 'product_result', $productParams);

         // Récupérer le storage des clés
         $storageKey = $this->getCacheKeysStorageKey();
@@ @@
             if ($model === User::class) {
                 $userCached = $key;
             }
+
             if ($model === Product::class) {
                 $productCached = $key;
             }
@@ @@
             if ($model === User::class) {
                 $userKeyStillExists = true;
             }
+
             if ($model === Product::class) {
                 $productKeyStillExists = true;
             }
@@ @@
         $userCallbackExecuted = false;
         $productCallbackExecuted = false;

-        $this->cacheManager->remember('search_in_model', function () use (&$userCallbackExecuted) {
+        $this->cacheManager->remember('search_in_model', function () use (&$userCallbackExecuted): string {
             $userCallbackExecuted = true;
             return 'new_user_result';
         }, $userParams);

-        $this->cacheManager->remember('search_in_model', function () use (&$productCallbackExecuted) {
+        $this->cacheManager->remember('search_in_model', function () use (&$productCallbackExecuted): string {
             $productCallbackExecuted = false;
             return 'product_result';
         }, $productParams);
@@ @@
         $longString = str_repeat('a', 300);
         $params = [$longString];

-        $result = $this->cacheManager->remember('test', fn() => 'value', $params);
+        $result = $this->cacheManager->remember('test', fn(): string => 'value', $params);

         // Devrait retourner la valeur, pas la clé
         $this->assertEquals('value', $result);
@@ @@
         // search_in_models n'a pas de modèle unique
         $modelsParams = [[User::class, Product::class], 'query', []];

-        $this->cacheManager->remember('search_in_models', fn() => 'combined_result', $modelsParams);
+        $this->cacheManager->remember('search_in_models', fn(): string => 'combined_result', $modelsParams);

         $storageKey = $this->getCacheKeysStorageKey();
         $storedKeys = Cache::get($storageKey, []);
@@ @@

         $executionCount = 0;

-        $result1 = $this->cacheManager->remember('test', function () use (&$executionCount) {
-            $executionCount++;
+        $result1 = $this->cacheManager->remember('test', function () use (&$executionCount): string {
+            ++$executionCount;
             return 'cached_value';
         }, []);

-        $result2 = $this->cacheManager->remember('test', function () use (&$executionCount) {
-            $executionCount++;
+        $result2 = $this->cacheManager->remember('test', function () use (&$executionCount): string {
+            ++$executionCount;
             return 'cached_value';
         }, []);

-        $this->assertEquals(1, $executionCount);
+        $this->assertSame(1, $executionCount);
         $this->assertEquals('cached_value', $result1);
         $this->assertEquals('cached_value', $result2);
     }
    ----------- end diff -----------

Applied rules:
 * PostIncDecToPreIncDecRector
 * NewlineAfterStatementRector
 * AssertEqualsToSameRector
 * AddArrowFunctionReturnTypeRector
 * ClosureReturnTypeRector


20) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/IndexBuilderTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

+use PHPUnit\Framework\Attributes\CoversClass;
+use Carbon\Carbon;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Services\IndexBuilder;
 use Fuzzy\Services\StringNormalizer;
@@ @@

 /**
  * Test suite for the IndexBuilder service.
- *
- * @covers \Fuzzy\Services\IndexBuilder
  */
+#[CoversClass(\Fuzzy\Services\IndexBuilder::class)]
 final class IndexBuilderTest extends TestCase
 {
     private IndexBuilder $builder;
@@ @@
     public function test_update_or_create_existing_entry(): void
     {
         $modelType = User::class;
-        $modelId = time();
+        $modelId = Carbon::now()
+            ->getTimestamp();
         $field = 'unique_test_field_' . $modelId;

         // Utiliser des mots qui ne sont PAS des stop words
    ----------- end diff -----------

Applied rules:
 * TimeFuncCallToCarbonRector
 * CoversAnnotationWithValueToAttributeRector


21) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/IndexManagerServiceTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

+use Exception;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
 use Fuzzy\Services\IndexBuilder;
@@ @@
 final class IndexManagerServiceTest extends TestCase
 {
     private IndexManagerService $indexManager;
+
     private $indexBuilder;
+
     private $indexRepository;
+
     private $modelDiscovery;

     protected function setUp(): void
@@ @@

         $stats = $this->indexManager->getStats();

-        $this->assertEquals($expectedStats, $stats);
+        $this->assertSame($expectedStats, $stats);
     }

     public function test_get_precise_model_stats_returns_detailed_stats(): void
@@ @@
         // soit ignorer, soit utiliser DatabaseMigrations
         try {
             $this->indexManager->reindexModel($modelClass);
-        } catch (\Exception $e) {
+        } catch (Exception $exception) {
             // En environnement de test sans base, on ignore l'erreur
             $this->addToAssertionCount(1);
             return;
    ----------- end diff -----------

Applied rules:
 * CatchExceptionNameMatchingTypeRector
 * NewlineBetweenClassLikeStmtsRector
 * AssertEqualsToSameRector


22) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ModelDiscoveryServiceTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

-use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Exceptions\ModelNotSearchableException;
 use Fuzzy\Services\ModelDiscoveryService;
 use Fuzzy\Tests\Fixtures\NonSearchableModel;
    ----------- end diff -----------

Applied rules:


23) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/PipelineManagerServiceTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

+use stdClass;
+use ReflectionClass;
 use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Contracts\StageInterface;
 use Fuzzy\Services\PipelineManagerService;
@@ @@
 final class PipelineManagerServiceTest extends TestCase
 {
     private PipelineManagerService $pipelineManager;
+
     private $pipeline;
-    private $stages;

     protected function setUp(): void
     {
@@ @@
         $stage2 = Mockery::mock(StageInterface::class);
         $stage2->shouldReceive('getPriority')->andReturn(60);

-        $this->stages = [$stage1, $stage2];
+        $stages = [$stage1, $stage2];

         $this->pipelineManager = new PipelineManagerService(
             $this->pipeline,
-            $this->stages
+            $stages
         );
     }

@@ @@

         $invalidStages = [
             Mockery::mock(StageInterface::class)->shouldReceive('getPriority')->andReturn(50)->getMock(),
-            new \stdClass(), // Invalid stage
+            new stdClass(), // Invalid stage
         ];

         new PipelineManagerService($this->pipeline, $invalidStages);
@@ @@

         $results = $this->pipelineManager->process($context);

-        $this->assertEquals($expectedResults, $results);
+        $this->assertSame($expectedResults, $results);
     }

     public function test_process_returns_empty_array_when_pipeline_returns_empty(): void
@@ @@
             {
                 return null;
             }
+
+            /**
+             * @return array{}
+             */
             public function getAllModelIds(): array
             {
                 return [];
             }
+
             public function hasMultipleWords(): bool
             {
                 return false;
             }
+
+            /**
+             * @return array{}
+             */
             public function getQueryWords(): array
             {
                 return [];
             }
+
             public function getNormalizedQuery(): string
             {
                 return '';
             }
+
+            /**
+             * @return array{}
+             */
             public function getWordIndex(): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getItemMap(): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getModelIndex(): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getIndexEntriesForModel(string $modelType, string $modelId): array
             {
                 return [];
             }
+
             public function getModelClass(): string
             {
                 return '';
             }
+
             public function addPotentialMatch(array $match): void {}
+
+            /**
+             * @return array{}
+             */
             public function getPotentialMatchesForModel(string $key): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getAllPotentialMatches(): array
             {
                 return [];
             }
+
             public function hasPotentialMatches(string $key): bool
             {
                 return false;
@@ @@
         $service->process($mockContext);

         // Verify the execution order was set by our mock
-        $this->assertEquals([1, 2], $executionOrder);
+        $this->assertSame([1, 2], $executionOrder);
     }

     public function test_process_passes_context_through_pipeline(): void
@@ @@
     {
         // Use a simple anonymous class instead of Mockery for context
         $mockContext = new class implements SearchContextInterface {
+            /**
+             * @var string[]
+             */
             public array $results = ['final_result'];

             public function getModelInstance(string $key): ?object
@@ @@
             {
                 return null;
             }
+
+            /**
+             * @return array{}
+             */
             public function getAllModelIds(): array
             {
                 return [];
             }
+
             public function hasMultipleWords(): bool
             {
                 return false;
             }
+
+            /**
+             * @return array{}
+             */
             public function getQueryWords(): array
             {
                 return [];
             }
+
             public function getNormalizedQuery(): string
             {
                 return '';
             }
+
+            /**
+             * @return array{}
+             */
             public function getWordIndex(): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getItemMap(): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getModelIndex(): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getIndexEntriesForModel(string $modelType, string $modelId): array
             {
                 return [];
             }
+
             public function getModelClass(): string
             {
                 return '';
             }
+
             public function addPotentialMatch(array $match): void {}
+
+            /**
+             * @return array{}
+             */
             public function getPotentialMatchesForModel(string $key): array
             {
                 return [];
             }
+
+            /**
+             * @return array{}
+             */
             public function getAllPotentialMatches(): array
             {
                 return [];
             }
+
             public function hasPotentialMatches(string $key): bool
             {
                 return false;
@@ @@

         $results = $this->pipelineManager->process($mockContext);

-        $this->assertEquals(['final_result'], $results);
+        $this->assertSame(['final_result'], $results);
     }

     public function test_process_sorts_stages_by_priority_descending(): void
@@ @@
         $stages = [$mediumPriorityStage, $lowPriorityStage, $highPriorityStage];

         // Create a temporary service to check sorting
-        $reflection = new \ReflectionClass(PipelineManagerService::class);
+        $reflection = new ReflectionClass(PipelineManagerService::class);
         $method = $reflection->getMethod('validateAndSortStages');
         $method->setAccessible(true);
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * NarrowUnusedSetUpDefinedPropertyRector
 * AssertEqualsToSameRector
 * DocblockReturnArrayFromDirectArrayInstanceRector
 * DocblockVarArrayFromPropertyDefaultsRector


24) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/PipelineStageManagerTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

-use Fuzzy\Contracts\StageInterface;
 use Fuzzy\Exceptions\DuplicateStageException;
 use Fuzzy\Services\PipelineStageManager;
 use Fuzzy\Stages\MatchDiscoveryStage;
@@ @@
             SortAndLimitStage::class,
         ];

-        $this->assertEquals($expected, $stages);
+        $this->assertSame($expected, $stages);
     }

     /**
    ----------- end diff -----------

Applied rules:
 * AssertEqualsToSameRector


25) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ResultFilterServiceTest.php:40

    ---------- begin diff ----------
@@ @@
         $filtered = $this->resultFilter->filterAndSort($results, 0.5);

         $this->assertCount(2, $filtered);
-        $this->assertEquals(0.8, $filtered[0]->score);
-        $this->assertEquals(0.6, $filtered[1]->score);
+        $this->assertEqualsWithDelta(0.8, $filtered[0]->score, PHP_FLOAT_EPSILON);
+        $this->assertEqualsWithDelta(0.6, $filtered[1]->score, PHP_FLOAT_EPSILON);
     }

     public function test_filter_and_sort_sorts_by_score_descending(): void
@@ @@

         $filtered = $this->resultFilter->filterAndSort($results, 0.0);

-        $this->assertEquals(0.9, $filtered[0]->score);
-        $this->assertEquals(0.7, $filtered[1]->score);
-        $this->assertEquals(0.6, $filtered[2]->score);
+        $this->assertEqualsWithDelta(0.9, $filtered[0]->score, PHP_FLOAT_EPSILON);
+        $this->assertEqualsWithDelta(0.7, $filtered[1]->score, PHP_FLOAT_EPSILON);
+        $this->assertEqualsWithDelta(0.6, $filtered[2]->score, PHP_FLOAT_EPSILON);
     }

     public function test_filter_and_sort_removes_null_results(): void
@@ @@

         $filtered = $this->resultFilter->filterAndSort($results, 0.0);

-        $this->assertEquals(0, array_key_first($filtered->toArray()));
-        $this->assertEquals(1, array_key_last($filtered->toArray()));
+        $this->assertSame(0, array_key_first($filtered->toArray()));
+        $this->assertSame(1, array_key_last($filtered->toArray()));
     }
 }
    ----------- end diff -----------

Applied rules:
 * AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector
 * AssertEqualsToSameRector


26) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ScoringEngineTest.php:21

    ---------- begin diff ----------
@@ @@
 final class ScoringEngineTest extends TestCase
 {
     private ScoringEngineInterface $scoringEngine;
+
     private SearchContext $searchContext;

     protected function setUp(): void
@@ @@

     /**
      * Creates a test index entry.
+     * @return array<string, string|string[]|float>
      */
     private function createTestIndexEntry(string $field = 'name'): array
     {
@@ @@
         // Assert: Should return perfect score
         $this->assertEqualsWithDelta(1.0, $score, PHP_FLOAT_EPSILON);
     }
+
     /**
      * Test fallback score uses similarity calculator.
      */
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * DocblockReturnArrayFromDirectArrayInstanceRector


27) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/SearchProcessorServiceTest.php:19

    ---------- begin diff ----------
@@ @@
 final class SearchProcessorServiceTest extends TestCase
 {
     private SearchProcessorService $searchProcessor;
+
     private $pipeline;
+
     private $normalizer;
-    private $similarityCalculator;
     private $indexRepository;
-    private $scoringEngine;
     private $modelDiscovery;
+
     private $resultFilter;

     protected function setUp(): void
@@ @@

         $this->pipeline = Mockery::mock(Pipeline::class);
         $this->normalizer = Mockery::mock(StringNormalizer::class);
-        $this->similarityCalculator = Mockery::mock(SimilarityCalculator::class);
+        $similarityCalculator = Mockery::mock(SimilarityCalculator::class);
         $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
-        $this->scoringEngine = Mockery::mock(ScoringEngine::class);
+        $scoringEngine = Mockery::mock(ScoringEngine::class);
         $this->modelDiscovery = Mockery::mock(ModelDiscoveryInterface::class);
         $this->resultFilter = Mockery::mock(ResultFilterInterface::class);

@@ @@
         $this->searchProcessor = new SearchProcessorService(
             $this->pipeline,
             $this->normalizer,
-            $this->similarityCalculator,
+            $similarityCalculator,
             $this->indexRepository,
-            $this->scoringEngine,
+            $scoringEngine,
             $this->modelDiscovery,
             $this->resultFilter
         );
@@ @@

         $this->resultFilter->shouldReceive('filterAndSort')
             ->once()
-            ->with(Mockery::on(function ($collection) use ($pipelineResults) {
+            ->with(Mockery::on(function ($collection): bool {
                 return $collection instanceof Collection && $collection->count() === 2;
             }), Mockery::any())
             ->andReturn(collect($pipelineResults));
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * RemoveUnusedClosureVariableUseRector
 * NarrowUnusedSetUpDefinedPropertyRector
 * ClosureReturnTypeRector


28) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ServiceRegistrarTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

+use ReflectionClass;
+use Fuzzy\Tests\Fixtures\CustomStage;
+use RuntimeException;
 use Fuzzy\Commands\ClearCacheCommand;
 use Fuzzy\Commands\ClearIndexCommand;
 use Fuzzy\Commands\IndexSearchCommand;
@@ @@
 use Fuzzy\Services\SimilarityCalculator;
 use Fuzzy\Services\StringNormalizer;
 use Fuzzy\Tests\TestCase;
-use Illuminate\Support\Facades\File;
 use Illuminate\Support\ServiceProvider;

 /**
@@ @@
 final class ServiceRegistrarTest extends TestCase
 {
     private ServiceRegistrar $registrar;
+
     private ServiceProvider $provider;

     protected function setUp(): void
@@ @@
             provider: $this->provider
         );

-        $this->app->detectEnvironment(fn() => 'testing');
+        $this->app->detectEnvironment(fn(): string => 'testing');
     }

     protected function tearDown(): void
@@ @@
         $calculator = $this->app->make(SimilarityCalculator::class);

         // Assert: Verify algorithms are registered using reflection
-        $reflection = new \ReflectionClass($calculator);
+        $reflection = new ReflectionClass($calculator);
         $algorithmsProperty = $reflection->getProperty('algorithms');
         $algorithmsProperty->setAccessible(true);
+
         $algorithms = $algorithmsProperty->getValue($calculator);

         $this->assertCount(3, $algorithms);
@@ @@
         $calculator = $this->app->make(SimilarityCalculator::class);

         // Assert: Verify LCS algorithm config is correctly injected
-        $reflection = new \ReflectionClass($calculator);
+        $reflection = new ReflectionClass($calculator);
         $algorithmsProperty = $reflection->getProperty('algorithms');
         $algorithmsProperty->setAccessible(true);
+
         $algorithms = $algorithmsProperty->getValue($calculator);

-        $lcsReflection = new \ReflectionClass($algorithms[0]);
+        $lcsReflection = new ReflectionClass($algorithms[0]);
         $lcsConfigProperty = $lcsReflection->getProperty('config');
         $lcsConfigProperty->setAccessible(true);
+
         $lcsConfig = $lcsConfigProperty->getValue($algorithms[0]);
         $this->assertInstanceOf(LongestCommonSubstringConfig::class, $lcsConfig);

         // Assert: Verify Levenshtein algorithm config is correctly injected
-        $levReflection = new \ReflectionClass($algorithms[1]);
+        $levReflection = new ReflectionClass($algorithms[1]);
         $levConfigProperty = $levReflection->getProperty('config');
         $levConfigProperty->setAccessible(true);
+
         $levConfig = $levConfigProperty->getValue($algorithms[1]);
         $this->assertInstanceOf(LevenshteinAlgorithmConfig::class, $levConfig);

         // Assert: Verify Prefix algorithm config is correctly injected
-        $prefixReflection = new \ReflectionClass($algorithms[2]);
+        $prefixReflection = new ReflectionClass($algorithms[2]);
         $prefixConfigProperty = $prefixReflection->getProperty('config');
         $prefixConfigProperty->setAccessible(true);
+
         $prefixConfig = $prefixConfigProperty->getValue($algorithms[2]);
         $this->assertInstanceOf(PrefixAlgorithmConfig::class, $prefixConfig);
     }
@@ @@
     public function test_register_all_handles_custom_pipeline_stages(): void
     {
         // Arrange: Set custom pipeline stages configuration
-        config(['fuzzy.pipeline' => [\Fuzzy\Tests\Fixtures\CustomStage::class]]);
+        config(['fuzzy.pipeline' => [CustomStage::class]]);

         // Act: Register all services
         $this->registrar->registerAll();
@@ @@

         try {
             // Act & Assert: Expect exception when helpers file is missing
-            $this->expectException(\RuntimeException::class);
+            $this->expectException(RuntimeException::class);
             $this->expectExceptionMessage('helpers.php not found at');

             $registrar = new ServiceRegistrar(
@@ @@
         $indexBuilder = $this->app->make(IndexBuilder::class);

         // Assert: Verify normalizer dependency is properly injected
-        $reflection = new \ReflectionClass($indexBuilder);
+        $reflection = new ReflectionClass($indexBuilder);
         $property = $reflection->getProperty('normalizer');
         $property->setAccessible(true);
+
         $normalizer = $property->getValue($indexBuilder);

         $this->assertInstanceOf(ContextualNormalizerInterface::class, $normalizer);
@@ @@
             $currentContent = file_get_contents($dummyMigrationFile);
             $currentMtime = filemtime($dummyMigrationFile);

-            $this->assertEquals(
+            $this->assertSame(
                 $originalContent,
                 $currentContent,
                 'Existing migration file content should not be overwritten by automatic publishing'
@@ @@
         $dummyMigrationFile = $migrationsPath . '/2025_01_01_000002_existing_migration_v2.php';
         $originalContent = '<?php // Original custom migration content v2';
         file_put_contents($dummyMigrationFile, $originalContent);
-        $originalMtime = filemtime($dummyMigrationFile);

         sleep(1);

         try {
             // Act: Call registerAll multiple times
-            for ($i = 0; $i < 3; $i++) {
+            for ($i = 0; $i < 3; ++$i) {
                 $this->registrar->registerAll();
             }

             // Assert: File content unchanged after multiple calls
             $currentContent = file_get_contents($dummyMigrationFile);
-            $this->assertEquals(
+            $this->assertSame(
                 $originalContent,
                 $currentContent,
                 'Migration files should remain unchanged after multiple registerAll calls'
@@ @@
     public function test_multiple_register_calls_are_safe(): void
     {
         // Act: Call registerAll multiple times
-        for ($i = 0; $i < 3; $i++) {
+        for ($i = 0; $i < 3; ++$i) {
             $this->registrar->registerAll();
         }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * NewlineBeforeNewAssignSetRector
 * PostIncDecToPreIncDecRector
 * RemoveUnusedVariableAssignRector
 * AssertEqualsToSameRector
 * AddArrowFunctionReturnTypeRector


29) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/StringNormalizerTest.php:158

    ---------- begin diff ----------
@@ @@
         $this->normalizer->setCurrentField('name');
         $result = $this->normalizer->normalizeQuery($input);

-        $this->assertEquals('jean de la fontaine', $result);
+        $this->assertSame('jean de la fontaine', $result);

         $this->normalizer->setCurrentField(null);
         $this->normalizer->setProtectedFields([]);
@@ @@
         $result = $this->normalizer->normalizeQuery($input);

         // 'the', 'and', 'are', 'in' sont supprimés, reste 'cat dog house'
-        $this->assertEquals('cat dog house', $result);
+        $this->assertSame('cat dog house', $result);

         $this->normalizer->setCurrentField(null);
         $this->normalizer->setProtectedFields([]);
@@ @@

         // Champ protégé : les stop words sont conservés
         $resultProtected = $this->normalizer->normalizeForField($value, 'full_name');
-        $this->assertEquals('john and jane doe', $resultProtected);
+        $this->assertSame('john and jane doe', $resultProtected);

         // Champ non protégé : les stop words sont supprimés
         $resultNonProtected = $this->normalizer->normalizeForField($value, 'description');
-        $this->assertEquals('john jane doe', $resultNonProtected);
+        $this->assertSame('john jane doe', $resultNonProtected);

         $this->normalizer->setProtectedFields([]);
     }
@@ @@
         $email = 'john.doe+test@example.com';
         $result = $this->normalizer->normalize($email);
         // Les caractères spéciaux sont supprimés par normalize()
-        $this->assertEquals('johndoetestexamplecom', $result);
+        $this->assertSame('johndoetestexamplecom', $result);
     }

     public function test_name_with_multiple_stop_words(): void
@@ @@
         $this->normalizer->setCurrentField('name');
         $result = $this->normalizer->normalizeQuery($name);

-        $this->assertEquals('charles de gaulle et jean de la fontaine', $result);
+        $this->assertSame('charles de gaulle et jean de la fontaine', $result);

         $this->normalizer->setCurrentField(null);
         $this->normalizer->setProtectedFields([]);
@@ @@
     {
         $protectedFields = ['name', 'email', 'username'];
         $this->normalizer->setProtectedFields($protectedFields);
-        $this->assertEquals($protectedFields, $this->normalizer->getProtectedFields());
+        $this->assertSame($protectedFields, $this->normalizer->getProtectedFields());
         $this->normalizer->setProtectedFields([]);
     }
 }
    ----------- end diff -----------

Applied rules:
 * AssertEqualsToSameRector


30) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/WordSimilarityComparatorTest.php:14

    ---------- begin diff ----------
@@ @@
 final class WordSimilarityComparatorTest extends TestCase
 {
     private WordSimilarityComparator $comparator;
-    private StringNormalizer $normalizer;

     /**
      * Set up test dependencies.
@@ @@
     protected function setUp(): void
     {
         parent::setUp();
-        $this->normalizer = new StringNormalizer();
+        $normalizer = new StringNormalizer();
         $this->comparator = new WordSimilarityComparator(
-            normalizer: $this->normalizer
+            normalizer: $normalizer
         );
     }

@@ @@
                 $expectedScore,
                 $score,
                 0.01,
-                "Failed for: '$inputA' vs '$inputB'. Got: $score, Expected: $expectedScore"
+                sprintf("Failed for: '%s' vs '%s'. Got: %s, Expected: %s", $inputA, $inputB, $score, $expectedScore)
             );
         }
     }
@@ @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
             $this->assertGreaterThanOrEqual(
                 0.1,
                 $score,
-                "Should have at least minimal penalty for non-exact match. Got: $score"
+                'Should have at least minimal penalty for non-exact match. Got: ' . $score
             );
         }
     }
@@ @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
             $this->assertGreaterThanOrEqual(
                 0.1,
                 $score,
-                "Should have at least minimal penalty for non-exact match. Got: $score"
+                'Should have at least minimal penalty for non-exact match. Got: ' . $score
             );
         }
     }
@@ @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
             $this->assertGreaterThanOrEqual(
                 0.1,
                 $score,
-                "Should have at least minimal penalty for non-exact match. Got: $score"
+                'Should have at least minimal penalty for non-exact match. Got: ' . $score
             );
         }
     }
@@ @@
             $this->assertGreaterThanOrEqual(
                 $expectedMinPenalty,
                 $score,
-                "Score too low for: '$inputA' vs '$inputB'. Got: $score, Min: $expectedMinPenalty"
+                sprintf("Score too low for: '%s' vs '%s'. Got: %s, Min: %s", $inputA, $inputB, $score, $expectedMinPenalty)
             );
         }
     }
@@ @@
         $this->assertGreaterThan(
             $scoreSigma1,
             $scoreSigma2,
-            "Sigma=2.0 ($scoreSigma2) should give higher score than Sigma=1.0 ($scoreSigma1)"
+            sprintf('Sigma=2.0 (%s) should give higher score than Sigma=1.0 (%s)', $scoreSigma2, $scoreSigma1)
         );
         $this->assertLessThan(
             $scoreSigma1,
             $scoreSigma05,
-            "Sigma=0.5 ($scoreSigma05) should give lower score than Sigma=1.0 ($scoreSigma1)"
+            sprintf('Sigma=0.5 (%s) should give lower score than Sigma=1.0 (%s)', $scoreSigma05, $scoreSigma1)
         );
     }

@@ @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
         }
     }
@@ @@
                 0.0,
                 $score,
                 0.01,
-                "Exact match should return 0 for: '$inputA' vs '$inputB'. Got: $score"
+                sprintf("Exact match should return 0 for: '%s' vs '%s'. Got: %s", $inputA, $inputB, $score)
             );
         }
     }
@@ @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
             $this->assertGreaterThan(0.0, $score);
         }
@@ @@
         $difference1 = $scoreSigma1 - $scoreSigma05;
         $difference2 = $scoreSigma2 - $scoreSigma1;

-        $this->assertGreaterThan(0.02, $difference1, "Sigma should have noticeable effect (diff1: $difference1)");
-        $this->assertGreaterThan(0.02, $difference2, "Sigma should have noticeable effect (diff2: $difference2)");
+        $this->assertGreaterThan(0.02, $difference1, sprintf('Sigma should have noticeable effect (diff1: %s)', $difference1));
+        $this->assertGreaterThan(0.02, $difference2, sprintf('Sigma should have noticeable effect (diff2: %s)', $difference2));
     }

     /**
@@ @@
                 0.0,
                 $score,
                 0.01,
-                "Case should be ignored for: '$inputA' vs '$inputB'. Got: $score"
+                sprintf("Case should be ignored for: '%s' vs '%s'. Got: %s", $inputA, $inputB, $score)
             );
         }
     }
    ----------- end diff -----------

Applied rules:
 * EncapsedStringsToSprintfRector
 * NarrowUnusedSetUpDefinedPropertyRector


31) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/MatchDiscoveryStage/MatchFinderTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Stages\MatchDiscoveryStage;

+use ReflectionProperty;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use Fuzzy\Stages\MatchDiscoveryStage\MatchFinder;
@@ @@
 final class MatchFinderTest extends TestCase
 {
     private MatchFinder $finder;
+
     private StringNormalizer $normalizer;
+
     private SimilarityCalculator&MockObject $similarityCalculator;

     protected function setUp(): void
@@ @@
         $this->similarityCalculator = $this->createMock(SimilarityCalculator::class);
     }

+    /**
+     * @param array<array<string, array<int, array<string, mixed>>>, mixed> $wordIndex
+     */
     private function createContext(
         string $query,
         SearchOptionsData $options,
@@ @@
             indexDataArray: []
         );

-        $reflection = new \ReflectionProperty($context, 'indexData');
+        $reflection = new ReflectionProperty($context, 'indexData');
         $reflection->setAccessible(true);
         $reflection->setValue($context, $indexData);

@@ @@
         ];

         $this->similarityCalculator->method('calculateWordSimilarity')
-            ->willReturnCallback(function ($a, $b) {
+            ->willReturnCallback(function (string $a, string $b): float {
                 if ($a === 'php' && $b === 'ph') {
                     return 0.9;
                 }
+
                 return 0.5;
             });

@@ @@
     {
         // Build a large word index
         $wordIndex = [];
-        for ($i = 0; $i < 100; $i++) {
-            $wordIndex["word{$i}"] = [['indexable_type' => 'User', 'indexable_id' => $i]];
+        for ($i = 0; $i < 100; ++$i) {
+            $wordIndex['word' . $i] = [['indexable_type' => 'User', 'indexable_id' => $i]];
         }
+
         $wordIndex['php'] = [['indexable_type' => 'User', 'indexable_id' => 100]];

         $this->similarityCalculator->method('calculateWordSimilarity')
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * NewlineAfterStatementRector
 * TypeWillReturnCallableArrowFunctionRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


32) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/MatchDiscoveryStageTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Stages;

+use Fuzzy\Enums\StageType;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use ReflectionProperty;
@@ @@
 final class MatchDiscoveryStageTest extends TestCase
 {
     private MatchDiscoveryStage $stage;
+
     private StringNormalizer $normalizer;
+
     private SimilarityCalculator&MockObject $similarityCalculator;
+
     private MatchFinder&MockObject $matchFinder;

     protected function setUp(): void
@@ @@
         );
     }

+    /**
+     * @param array<array<string, array<int, array<string, mixed>>>, mixed> $wordIndex
+     */
     private function createContext(
         string $query,
         SearchOptionsData $options,
@@ @@
             ->method('discoverVeryCloseMatches')
             ->with($context, 'test', $wordIndex);

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     /**
@@ @@
             ->method('discoverMultiWordMatches')
             ->with($context);

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_skips_fuzzy_when_disabled(): void
@@ @@
         $this->matchFinder->expects($this->once())
             ->method('discoverMultiWordMatches');

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_skips_multi_word_when_single_word(): void
@@ @@
         $this->matchFinder->expects($this->once())
             ->method('discoverVeryCloseMatches');

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_single_word_with_exact_match_small_index(): void
     {
         $wordIndex = [];
-        for ($i = 0; $i < 50; $i++) {
-            $wordIndex["word{$i}"] = [['indexable_type' => 'User', 'indexable_id' => $i]];
+        for ($i = 0; $i < 50; ++$i) {
+            $wordIndex['word' . $i] = [['indexable_type' => 'User', 'indexable_id' => $i]];
         }

         $context = $this->createContext('test', new SearchOptionsData(fuzzy: true), $wordIndex);
@@ @@
         $this->matchFinder->expects($this->never())
             ->method('discoverCloseMatchesOptimized');

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_single_word_with_exact_match_large_index(): void
     {
         $wordIndex = [];
-        for ($i = 0; $i < 2000; $i++) {
-            $wordIndex["word{$i}"] = [['indexable_type' => 'User', 'indexable_id' => $i]];
+        for ($i = 0; $i < 2000; ++$i) {
+            $wordIndex['word' . $i] = [['indexable_type' => 'User', 'indexable_id' => $i]];
         }

         $context = $this->createContext('test', new SearchOptionsData(fuzzy: true), $wordIndex);
@@ @@
             ->method('discoverCloseMatchesOptimized')
             ->with($context, 'test');

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_calls_next_with_results(): void
@@ @@
     {
         $context = $this->createContext('test', new SearchOptionsData(), []);

-        $next = function (SearchContext $ctx) {
+        $next = function (SearchContext $ctx): string {
             return 'processed';
         };

@@ @@

     public function test_get_priority(): void
     {
-        $this->assertEquals(75, $this->stage->getPriority());
+        $this->assertSame(75, $this->stage->getPriority());
     }

     public function test_get_type(): void
     {
-        $this->assertEquals(\Fuzzy\Enums\StageType::MATCH_DISCOVERY, $this->stage->getType());
+        $this->assertSame(StageType::MATCH_DISCOVERY, $this->stage->getType());
     }
 }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * AssertEqualsToSameRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector
 * AddArrowFunctionReturnTypeRector
 * ClosureReturnTypeRector


33) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/IntegrationTest.php:13

    ---------- begin diff ----------
@@ @@
 use Fuzzy\Services\FuzzySearchService;
 use Illuminate\Support\Collection;
 use Fuzzy\Data\SearchResultData;
-use Illuminate\Support\Facades\Cache;

 /**
  * Integration tests for the complete fuzzy search system.
@@ @@
 {
     /**
      * Set up test environment.
-     *
-     * @return void
      */
     protected function setUp(): void
     {
@@ @@
      * - Searches return expected results
      * - Cache invalidation works correctly
      * - Statistics are accurate
-     *
-     * @return void
      */
     public function test_complete_search_workflow(): void
     {
@@ @@
             'type' => 'user',
         ]);

-        $product1 = Product::create([
+        Product::create([
             'name' => 'MacBook Pro',
             'description' => 'Apple laptop with M1 chip',
             'price' => 1999.99,
         ]);

-        $product2 = Product::create([
+        Product::create([
             'name' => 'Wireless Mouse',
             'description' => 'Ergonomic mouse with Bluetooth',
             'price' => 59.99,
@@ @@
         $exactMatch = $exactResults->first(function ($result): bool {
             return $result->item->name === 'John Smith';
         });
-        $this->assertNotNull($exactMatch, 'Should find John Smith in exact search');
+        $this->assertInstanceOf(SearchResultData::class, $exactMatch, 'Should find John Smith in exact search');
         $this->assertGreaterThan(0.9, $exactMatch->score, 'Exact match should have high score');

         // === ACT: Multi-word search ===
@@ @@
      * Test automatic indexing via FuzzySearchable trait.
      *
      * Verifies that model events automatically trigger index updates.
-     *
-     * @return void
      */
     public function test_model_auto_indexing_via_trait(): void
     {
@@ @@
      * Test custom shouldBeIndexed logic.
      *
      * Verifies that the shouldBeIndexed method controls which models are indexed.
-     *
-     * @return void
      */
     public function test_should_be_indexed_logic(): void
     {
@@ @@
      * Test custom formatting in search results.
      *
      * Verifies that custom formatters are applied to search results.
-     *
-     * @return void
      */
     public function test_custom_formatting(): void
     {
@@ @@
      * Test performance with large datasets.
      *
      * Verifies that indexing and search operations perform within acceptable limits.
-     *
-     * @return void
      */
     public function test_performance_with_large_dataset(): void
     {
@@ @@
      * Test cache integration.
      *
      * Verifies that caching works correctly and is properly invalidated.
-     *
-     * @return void
      */
     public function test_cache_integration(): void
     {
@@ @@
      * Test error handling scenarios.
      *
      * Verifies that the system handles edge cases gracefully.
-     *
-     * @return void
      */
     public function test_error_handling(): void
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUnusedVariableAssignRector
 * RemoveUselessReturnTagRector
 * AssertEmptyNullableObjectToAssertInstanceofRector


34) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/CustomStage.php:76

    ---------- begin diff ----------
@@ @@
     {
         // Custom processing logic here
         // Example: Add a marker that this stage was executed
-        if (!isset($context->processedStages)) {
+        if (!property_exists($context, 'processedStages') || $context->processedStages === null) {
             $context->processedStages = [];
         }
    ----------- end diff -----------

Applied rules:
 * IssetOnPropertyObjectToPropertyExistsRector


35) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/CustomStage2.php:28

    ---------- begin diff ----------
@@ @@

     public function handle(SearchContextInterface $context, Closure $next): mixed
     {
-        if (!isset($context->processedStages)) {
+        if (!property_exists($context, 'processedStages') || $context->processedStages === null) {
             $context->processedStages = [];
         }
    ----------- end diff -----------

Applied rules:
 * IssetOnPropertyObjectToPropertyExistsRector


36) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/NonSearchableModel.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Fixtures;

-use Fuzzy\Data\FuzzySearchableData;
 use Illuminate\Database\Eloquent\Model;

 /**
    ----------- end diff -----------

Applied rules:


37) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/Product.php:46

    ---------- begin diff ----------
@@ @@

     /**
      * Determine if the model should be indexed for search.
-     *
-     * @return bool
      */
     public function shouldBeIndexed(): bool
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


38) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/User.php:53

    ---------- begin diff ----------
@@ @@

     /**
      * Determine if the model should be indexed for search.
-     *
-     * @return bool
      */
     public function shouldBeIndexed(): bool
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


39) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/UserSearchData.php:19

    ---------- begin diff ----------
@@ @@
      * Create a search data instance from a User model.
      *
      * @param Model $user The User model instance
-     * @return self
      */
     public static function fromModel(Model $user): self
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


40) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/ClearCacheCommandTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Commands;

-use Fuzzy\Commands\ClearCacheCommand;
+use Fuzzy\Tests\Fixtures\User;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\SearchServiceInterface;
 use Fuzzy\Tests\TestCase;
@@ @@

 final class ClearCacheCommandTest extends TestCase
 {
-    private $searchService;
     private $cacheManager;

     protected function setUp(): void
@@ @@
         parent::setUp();

         $this->cacheManager = Mockery::mock(CacheManagerInterface::class);
-        $this->searchService = Mockery::mock(SearchServiceInterface::class);
+        $searchService = Mockery::mock(SearchServiceInterface::class);

         // Configure le mock pour retourner le cacheManager via getCacheManager()
-        $this->searchService->shouldReceive('getCacheManager')
+        $searchService->shouldReceive('getCacheManager')
             ->zeroOrMoreTimes()
             ->andReturn($this->cacheManager);

         // Bind the search service in the container
-        $this->app->instance(SearchServiceInterface::class, $this->searchService);
+        $this->app->instance(SearchServiceInterface::class, $searchService);

         config(['fuzzy.cache.prefix' => 'fuzzy_test:']);
     }
@@ @@

     public function test_clear_cache_for_specific_model(): void
     {
-        $modelClass = 'Fuzzy\\Tests\\Fixtures\\User';
+        $modelClass = User::class;

         $this->cacheManager->shouldReceive('invalidateForModel')
             ->once()
@@ @@
         $output = Artisan::output();

         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("Cache cleared for model: {$modelClass}", $output);
+        $this->assertStringContainsString('Cache cleared for model: ' . $modelClass, $output);
     }

     public function test_clear_stats_cache_only(): void
    ----------- end diff -----------

Applied rules:
 * EncapsedStringsToSprintfRector
 * NarrowUnusedSetUpDefinedPropertyRector
 * StringClassNameToClassConstantRector


41) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/ClearIndexCommandTest.php:78

    ---------- begin diff ----------
@@ @@

         // Act: Execute command with force flag
         $this->artisan('fuzzy:clear', ['--force' => true])
-            ->expectsOutput("✓ Cleared all indexes ({$initialCount} entries)")
+            ->expectsOutput(sprintf('✓ Cleared all indexes (%s entries)', $initialCount))
             ->assertExitCode(0);

         // Assert: All indexes should be removed
@@ @@

         // Assert: Command should succeed and display correct message
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("✓ Cleared all indexes ({$initialCount} entries)", $output);
+        $this->assertStringContainsString(sprintf('✓ Cleared all indexes (%s entries)', $initialCount), $output);

         // Assert: Database should be empty after clearing
         $finalCount = FuzzyIndex::count();
@@ @@

         // Assert: Command should succeed and display correct model-specific message
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("✓ Cleared {$initialUserEntries} entries for " . User::class, $output);
+        $this->assertStringContainsString(sprintf('✓ Cleared %s entries for ', $initialUserEntries) . User::class, $output);

         // Assert: User entries should be removed, Product entries should remain
         $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
@@ @@
             'model' => User::class,
             '--force' => true,
         ])
-            ->expectsOutput("✓ Cleared {$initialEntries} entries for " . User::class)
+            ->expectsOutput(sprintf('✓ Cleared %s entries for ', $initialEntries) . User::class)
             ->assertExitCode(0);

         // Assert: User entries should be removed
@@ @@
         // Act: Execute command and accept confirmation
         $this->artisan('fuzzy:clear')
             ->expectsConfirmation('Clear ALL search indexes?', 'yes')
-            ->expectsOutput("✓ Cleared all indexes ({$initialCount} entries)")
+            ->expectsOutput(sprintf('✓ Cleared all indexes (%s entries)', $initialCount))
             ->assertExitCode(0);

         // Assert: All indexes should be removed
@@ @@
         $reflection = new ReflectionClass($command);
         $signatureProperty = $reflection->getProperty('signature');
         $signatureProperty->setAccessible(true);
+
         $signature = $signatureProperty->getValue($command);

         // Assert: Signature should contain model and force options
-        $this->assertStringContainsString('model?', $signature);
-        $this->assertStringContainsString('--force', $signature);
+        $this->assertStringContainsString('model?', (string) $signature);
+        $this->assertStringContainsString('--force', (string) $signature);
     }

     /**
@@ @@
         $reflection = new ReflectionClass($command);
         $descriptionProperty = $reflection->getProperty('description');
         $descriptionProperty->setAccessible(true);
+
         $description = $descriptionProperty->getValue($command);

         // Assert: Description should not be empty and should mention clearing indexes
         $this->assertNotEmpty($description);
-        $this->assertStringContainsString('Clear search index', $description);
+        $this->assertStringContainsString('Clear search index', (string) $description);
     }

     /**
@@ @@

         // Assert: Command should report the correct total number of entries
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("✓ Cleared all indexes ({$totalEntries} entries)", $output);
+        $this->assertStringContainsString(sprintf('✓ Cleared all indexes (%s entries)', $totalEntries), $output);
     }
 }
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * EncapsedStringsToSprintfRector
 * StringCastAssertStringContainsStringRector


42) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/IndexSearchCommandTest.php:83

    ---------- begin diff ----------
@@ @@

         FuzzyIndex::query()->truncate();

-        $user = User::withoutEvents(function () {
+        User::withoutEvents(function () {
             return User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
         });

-        $product = Product::withoutEvents(function () {
+        Product::withoutEvents(function () {
             return Product::create(['name' => 'Product One', 'description' => 'Test', 'price' => 100]);
         });

@@ @@
     public function test_index_command_with_custom_chunk_size(): void
     {
         // Arrange: Create 150 users to test chunking
-        for ($i = 1; $i <= 150; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 150; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }

         // Act: Index with chunk size of 50 (should process in 3 batches)
@@ @@
     public function test_index_command_displays_statistics_correctly(): void
     {
         // Arrange: Create 5 indexable users
-        for ($i = 1; $i <= 5; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 5; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }

         // Act: Index the User model
@@ @@
         FuzzyIndex::query()->truncate();

         // Create indexable users (type='user')
-        for ($i = 1; $i <= 3; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 3; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }

         // Create non-indexable users within same class (type='admin')
-        for ($i = 1; $i <= 2; $i++) {
-            User::create(['name' => "Admin User {$i}", 'email' => "admin{$i}@example.com", 'type' => 'admin']);
+        for ($i = 1; $i <= 2; ++$i) {
+            User::create(['name' => 'Admin User ' . $i, 'email' => sprintf('admin%d@example.com', $i), 'type' => 'admin']);
         }

         // Act: Index the User model
@@ @@
         $reflection = new ReflectionClass($command);
         $signatureProperty = $reflection->getProperty('signature');
         $signatureProperty->setAccessible(true);
+
         $signature = $signatureProperty->getValue($command);

         // Assert: Signature should contain all expected options
-        $this->assertStringContainsString('model?', $signature);
-        $this->assertStringContainsString('--force', $signature);
-        $this->assertStringContainsString('--chunk=', $signature);
-        $this->assertStringContainsString('--list', $signature);
+        $this->assertStringContainsString('model?', (string) $signature);
+        $this->assertStringContainsString('--force', (string) $signature);
+        $this->assertStringContainsString('--chunk=', (string) $signature);
+        $this->assertStringContainsString('--list', (string) $signature);
     }

     /**
@@ @@
         $reflection = new ReflectionClass($command);
         $descriptionProperty = $reflection->getProperty('description');
         $descriptionProperty->setAccessible(true);
+
         $description = $descriptionProperty->getValue($command);

         // Assert: Description should not be empty and mention indexing
         $this->assertNotEmpty($description);
-        $this->assertStringContainsString('Index searchable models', $description);
+        $this->assertStringContainsString('Index searchable models', (string) $description);
     }

     /**
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * RemoveUnusedVariableAssignRector
 * StringCastAssertStringContainsStringRector


43) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/StatsIndexCommandTest.php:103

    ---------- begin diff ----------
@@ @@

         // Assert: Total entries should match the database count
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("Total entries: {$expectedTotal}", $output);
+        $this->assertStringContainsString('Total entries: ' . $expectedTotal, $output);
     }

     /**
@@ @@
     public function test_stats_command_with_multiple_entries_per_field(): void
     {
         // Arrange: Create 5 users with searchable fields
-        for ($i = 1; $i <= 5; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 5; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }
+
         Artisan::call('fuzzy:index');

         // Act: Execute stats command
@@ @@
         $reflection = new ReflectionClass($command);
         $signatureProperty = $reflection->getProperty('signature');
         $signatureProperty->setAccessible(true);
+
         $signature = $signatureProperty->getValue($command);

         // Assert: Signature should be 'fuzzy:stats'
@@ @@
         $reflection = new ReflectionClass($command);
         $descriptionProperty = $reflection->getProperty('description');
         $descriptionProperty->setAccessible(true);
+
         $description = $descriptionProperty->getValue($command);

         // Assert: Description should mention index statistics
         $this->assertNotEmpty($description);
-        $this->assertStringContainsString('Show search index statistics', $description);
+        $this->assertStringContainsString('Show search index statistics', (string) $description);
     }

     /**
@@ @@
     public function test_stats_command_with_large_number_of_models(): void
     {
         // Arrange: Create 50 users (50 × 2 fields = 100 index entries)
-        for ($i = 1; $i <= 50; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 50; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }
+
         Artisan::call('fuzzy:index');

         // Act: Execute stats command
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * NewlineAfterStatementRector
 * StringCastAssertStringContainsStringRector


44) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/FuzzySearchServiceProviderTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit;

+use Fuzzy\Contracts\CacheManagerInterface;
+use Fuzzy\Contracts\ModelDiscoveryInterface;
+use Fuzzy\Contracts\IndexManagerInterface;
+use Fuzzy\Contracts\SearchProcessorInterface;
+use Fuzzy\Contracts\ResultFilterInterface;
+use Fuzzy\Contracts\PipelineManagerInterface;
+use Fuzzy\Contracts\SearchContextInterface;
+use Fuzzy\Contracts\ScoringEngineInterface;
+use Fuzzy\Config\AdvancedScoringConfig;
+use Fuzzy\Config\SimilarityCalculatorConfig;
+use RuntimeException;
 use Fuzzy\FuzzySearchServiceProvider;
 use Fuzzy\Services\FuzzySearchService;
 use Fuzzy\Tests\TestCase;
@@ @@
         $providedServices = $this->provider->provides();

         $expectedServices = [
-            \Fuzzy\Contracts\CacheManagerInterface::class,
-            \Fuzzy\Contracts\ModelDiscoveryInterface::class,
-            \Fuzzy\Contracts\IndexManagerInterface::class,
-            \Fuzzy\Contracts\SearchProcessorInterface::class,
-            \Fuzzy\Contracts\ResultFilterInterface::class,
-            \Fuzzy\Contracts\PipelineManagerInterface::class,
-            \Fuzzy\Contracts\SearchContextInterface::class,
-            \Fuzzy\Contracts\ScoringEngineInterface::class,
-            \Fuzzy\Config\AdvancedScoringConfig::class,
-            \Fuzzy\Config\SimilarityCalculatorConfig::class,
+            CacheManagerInterface::class,
+            ModelDiscoveryInterface::class,
+            IndexManagerInterface::class,
+            SearchProcessorInterface::class,
+            ResultFilterInterface::class,
+            PipelineManagerInterface::class,
+            SearchContextInterface::class,
+            ScoringEngineInterface::class,
+            AdvancedScoringConfig::class,
+            SimilarityCalculatorConfig::class,
             FuzzySearchService::class,
             'laravel-fuzzy.search',
         ];
@@ @@
         $this->provider->register();

         // Assert: Custom values should be preserved (not overwritten by defaults)
-        $this->assertEquals(0.5, config('fuzzy.default_options.min_score'));
+        $this->assertEqualsWithDelta(0.5, config('fuzzy.default_options.min_score'), PHP_FLOAT_EPSILON);
         $this->assertEquals(100, config('fuzzy.default_options.max_results'));
         $this->assertFalse(config('fuzzy.default_options.fuzzy'));
         $this->assertFalse(config('fuzzy.cache.enabled'));
@@ @@
         }

         try {
-            $this->expectException(\RuntimeException::class);
+            $this->expectException(RuntimeException::class);
             $this->expectExceptionMessage('helpers.php not found');
             $this->provider->register();
         } finally {
    ----------- end diff -----------

Applied rules:
 * AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector


45) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/FuzzySearchServiceTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

+use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\IndexManagerInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
@@ @@
 final class FuzzySearchServiceTest extends TestCase
 {
     private FuzzySearchService $service;
+
     private CacheManagerInterface&MockInterface $cacheManager;
+
     private ModelDiscoveryInterface&MockInterface $modelDiscovery;
+
     private IndexManagerInterface&MockInterface $indexManager;
+
     private SearchProcessorInterface&MockInterface $searchProcessor;

     protected function setUp(): void
@@ @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ @@
         // Assert: Verify exact match results
         $this->assertInstanceOf(Collection::class, $results);
         $this->assertGreaterThan(0, $results->count());
-        $this->assertEquals(0.95, $results->first()->score);
+        $this->assertEqualsWithDelta(0.95, $results->first()->score, PHP_FLOAT_EPSILON);
     }

     /**
@@ @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ @@
     public function test_index_model_via_index_manager(): void
     {
         // Arrange: Create mock model and expect index call
-        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
+        $model = Mockery::mock(MustFuzzySearch::class);

         $this->indexManager
             ->shouldReceive('indexModel')
@@ @@
     public function test_remove_model_via_index_manager(): void
     {
         // Arrange: Create mock model and expect removal call
-        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
+        $model = Mockery::mock(MustFuzzySearch::class);

         $this->indexManager
             ->shouldReceive('removeModel')
@@ @@
     public function test_update_model_index_via_index_manager(): void
     {
         // Arrange: Create mock model and expect update call
-        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
+        $model = Mockery::mock(MustFuzzySearch::class);

         $this->indexManager
             ->shouldReceive('updateModelIndex')
@@ @@
         $stats = $this->service->getIndexManager()->getStats();

         // Assert: Verify statistics are returned correctly
-        $this->assertEquals($expectedStats, $stats);
+        $this->assertSame($expectedStats, $stats);
     }

     /**
@@ @@
         $stats = $this->service->getIndexManager()->getPreciseModelStats($modelClass);

         // Assert: Verify model statistics are returned correctly
-        $this->assertEquals($expectedStats, $stats);
+        $this->assertSame($expectedStats, $stats);
     }

     /**
@@ @@
         $models = $this->service->getModelDiscovery()->getSearchableModels();

         // Assert: Verify searchable models are returned correctly
-        $this->assertEquals($expectedModels, $models);
+        $this->assertSame($expectedModels, $models);
     }
 }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * RemoveUnusedClosureVariableUseRector
 * AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector
 * AssertEqualsToSameRector


46) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Models/FuzzyIndexTest.php:350

    ---------- begin diff ----------
@@ @@
      * Helper method to create a FuzzyIndex entry with given data.
      *
      * @param array<string, mixed> $data
-     * @return FuzzyIndex
      */
     private function createFuzzyIndexEntry(array $data): FuzzyIndex
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


47) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Repositories/IndexRepositoryTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Repositories;

+use PHPUnit\Framework\Attributes\Test;
 use Fuzzy\Data\SearchOptionsData;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Repositories\IndexRepository;
@@ @@
 use Fuzzy\Tests\Fixtures\Product;
 use Fuzzy\Tests\Fixtures\User;
 use Fuzzy\Tests\TestCase;
-use Fuzzy\ValueObjects\IndexData;
 use Fuzzy\ValueObjects\SearchQuery;
 use Illuminate\Support\Collection;

@@ @@
         $this->repository = new IndexRepository();
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_empty_index_data_for_model_with_empty_database(): void
     {
         // Arrange : Prepare repository with empty database
@@ @@
         $this->assertEmpty($data['modelIndex']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_index_data_for_model_with_existing_data(): void
     {
         // Arrange : Create a user and its index entries
@@ @@
         $this->assertArrayHasKey($userKey, $data['modelIndex']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_filters_index_data_by_specific_model_ids(): void
     {
         // Arrange : Create two users with index entries
@@ @@
         $this->assertArrayNotHasKey($user2Key, $data['itemMap']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_skips_short_words_when_building_index(): void
     {
         // Arrange : Create a user with short words in name field
@@ @@
         $this->assertArrayHasKey('ef', $data['wordIndex']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_empty_collection_for_empty_ids_batch(): void
     {
         // Arrange : Prepare repository with empty ID list
@@ @@
         $this->assertTrue($models->isEmpty());
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_specific_models_for_given_ids_batch(): void
     {
         // Arrange : Create three users in database
@@ @@
         $this->assertEquals([$user1->id, $user2->id], $models->pluck('id')->sort()->values()->toArray());
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_preloads_models_into_context_cache(): void
     {
         // Arrange : Create two users with index entries and prepare search context
@@ @@
         $this->assertInstanceOf(User::class, $modelsMap[$user2Key]);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_handles_preload_with_empty_search_context(): void
     {
         // Arrange : Create search context with empty index data
@@ @@
         $this->assertEmpty($modelsMap);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_empty_statistics_with_no_data(): void
     {
         // Arrange : Prepare empty database
@@ @@
         $this->assertEmpty($stats['models']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_statistics_with_indexed_data(): void
     {
         // Arrange : Create users and products with multiple index entries
@@ @@

     /**
      * Helper method to create index entries for user fields.
+     * @param string[] $words
      */
     private function createIndexEntryForUserField(int $userId, string $field, string $originalValue, string $normalizedValue, array $words): void
     {
@@ @@

     /**
      * Helper method to create index entries for product fields.
+     * @param string[] $words
      */
     private function createIndexEntryForProductField(int $productId, string $field, string $originalValue, string $normalizedValue, array $words): void
     {
@@ @@

     /**
      * Helper method to create a search context for testing.
+     * @param array<string, mixed> $indexData
      */
     private function createSearchContext(array $indexData): SearchContext
     {
    ----------- end diff -----------

Applied rules:
 * AnnotationToAttributeRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


48) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/AdvancedScoringCalculatorTest.php:27

    ---------- begin diff ----------
@@ @@
 final class AdvancedScoringCalculatorTest extends TestCase
 {
     private AdvancedScoringCalculator $calculator;
+
     private SearchContext $context;

     protected function setUp(): void
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


49) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/SearchServiceInterface.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Contracts;

+use Fuzzy\Data\SearchResultData;
 use Illuminate\Support\Collection;

 /**
@@ @@
      * Get the cache manager instance.
      *
      * Provides access to cache operations for advanced cache management.
-     *
-     * @return CacheManagerInterface
      */
     public function getCacheManager(): CacheManagerInterface;

@@ @@
      * Get the model discovery instance.
      *
      * Provides access to model discovery for advanced model operations.
-     *
-     * @return ModelDiscoveryInterface
      */
     public function getModelDiscovery(): ModelDiscoveryInterface;

@@ @@
      * Get the index manager instance.
      *
      * Provides access to index operations for advanced index management.
-     *
-     * @return IndexManagerInterface
      */
     public function getIndexManager(): IndexManagerInterface;

@@ @@
      * Get the search processor instance.
      *
      * Provides access to search processor for advanced search operations.
-     *
-     * @return SearchProcessorInterface
      */
     public function getSearchProcessor(): SearchProcessorInterface;

@@ @@
      *
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     public function search(string $query, array $options = []): Collection;

@@ @@
      * @param string $modelClass The fully qualified model class name
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     public function searchInModel(string $modelClass, string $query, array $options = []): Collection;

@@ @@
      * @param array<int, string> $modelClasses Array of fully qualified model class names
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     public function searchInModels(array $modelClasses, string $query, array $options = []): Collection;
 }
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


50) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/FuzzySearch.php:6

    ---------- begin diff ----------
@@ @@

 use Illuminate\Support\Facades\Facade;
 use Illuminate\Support\Collection;
-use Fuzzy\Contracts\MustFuzzySearch;

 /**
  * Facade for the fuzzy search service
    ----------- end diff -----------

Applied rules:


51) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/FuzzySearchServiceProvider.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy;

+use Fuzzy\Contracts\CacheManagerInterface;
+use Fuzzy\Contracts\ModelDiscoveryInterface;
+use Fuzzy\Contracts\IndexManagerInterface;
+use Fuzzy\Contracts\SearchProcessorInterface;
+use Fuzzy\Contracts\ResultFilterInterface;
+use Fuzzy\Contracts\PipelineManagerInterface;
+use Fuzzy\Contracts\SearchContextInterface;
+use Fuzzy\Contracts\ScoringEngineInterface;
+use Fuzzy\Config\AdvancedScoringConfig;
+use Fuzzy\Config\SimilarityCalculatorConfig;
+use Fuzzy\Services\FuzzySearchService;
 use Fuzzy\Services\ServiceRegistrar;
 use Illuminate\Support\ServiceProvider;

@@ @@
     public function provides(): array
     {
         return [
-            \Fuzzy\Contracts\CacheManagerInterface::class,
-            \Fuzzy\Contracts\ModelDiscoveryInterface::class,
-            \Fuzzy\Contracts\IndexManagerInterface::class,
-            \Fuzzy\Contracts\SearchProcessorInterface::class,
-            \Fuzzy\Contracts\ResultFilterInterface::class,
-            \Fuzzy\Contracts\PipelineManagerInterface::class,
-            \Fuzzy\Contracts\SearchContextInterface::class,
-            \Fuzzy\Contracts\ScoringEngineInterface::class,
-            \Fuzzy\Config\AdvancedScoringConfig::class,
-            \Fuzzy\Config\SimilarityCalculatorConfig::class,
-            \Fuzzy\Services\FuzzySearchService::class,
+            CacheManagerInterface::class,
+            ModelDiscoveryInterface::class,
+            IndexManagerInterface::class,
+            SearchProcessorInterface::class,
+            ResultFilterInterface::class,
+            PipelineManagerInterface::class,
+            SearchContextInterface::class,
+            ScoringEngineInterface::class,
+            AdvancedScoringConfig::class,
+            SimilarityCalculatorConfig::class,
+            FuzzySearchService::class,
             'laravel-fuzzy.search',
         ];
     }
    ----------- end diff -----------

Applied rules:


52) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Repositories/IndexRepository.php:98

    ---------- begin diff ----------
@@ @@

     /**
      * {@inheritDoc}
+     * @return array<string, Model>
      */
     public function getPreloadedModelsMap(): array
     {
@@ @@

     /**
      * {@inheritDoc}
+     * @return array<string, mixed>
      */
     public function getStats(): array
     {
    ----------- end diff -----------

Applied rules:
 * DocblockGetterReturnArrayFromPropertyDocblockVarRector
 * DocblockReturnArrayFromDirectArrayInstanceRector


53) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/SearchContext.php:102

    ---------- begin diff ----------
@@ @@

     /**
      * Preload all required models for efficient access.
-     *
-     * @return void
      */
     private function preloadModels(): void
     {
@@ @@

     /**
      * {@inheritDoc}
+     * @return array<string, array>
      */
     public function getAllPotentialMatches(): array
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector
 * DocblockGetterReturnArrayFromPropertyDocblockVarRector


54) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/LevenshteinSimilarityAlgorithm.php:138

    ---------- begin diff ----------
@@ @@
         $closeMatchBonus = $this->config->getCloseMatchBonus();

         if ($levenshteinDistance <= $closeMatchThreshold && $longestLength >= $minimumLengthForBonus) {
-            $currentSimilarity = min($currentSimilarity + $closeMatchBonus, FUZZY_SCORE_IDENTICAL);
+            return min($currentSimilarity + $closeMatchBonus, FUZZY_SCORE_IDENTICAL);
         }

         return $currentSimilarity;
    ----------- end diff -----------

Applied rules:
 * CompleteMissingIfElseBracketRector
 * ReturnEarlyIfVariableRector


55) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarity/LetterDistanceCalculator.php:71

    ---------- begin diff ----------
@@ @@

     /**
      * Find matching letters between two sets with position windows.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function findLetterMatches(array $lettersA, array $lettersB): array
     {
@@ @@

     /**
      * Find the best matching letter in the target string.
+     * @param array<int, mixed> $searchLetters
      */
     private function findBestLetterMatch(
         string $targetLetter,
@@ @@
         $startSearch = max($startIndex, $currentPosition - $searchWindow);
         $endSearch = min(count($searchLetters), $currentPosition + $searchWindow + $baseIncrement);

-        for ($searchPosition = $startSearch; $searchPosition < $endSearch; $searchPosition++) {
+        for ($searchPosition = $startSearch; $searchPosition < $endSearch; ++$searchPosition) {
             if (in_array($searchPosition, $usedPositions, true)) {
                 continue;
             }
@@ @@

     /**
      * Calculate total distance from matched letter pairs.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function calculateTotalMatchedDistance(array $matchedPairs, array $lettersA, array $lettersB): float
     {
@@ @@
             );

             if (!$pair['isExact']) {
-                $imperfectMatchCount++;
+                ++$imperfectMatchCount;
             }
         }

@@ @@
         $unmatchedMultiplier = $this->config->getUnmatchedLetterMultiplier();
         $totalDistance += ($unmatchedCountA + $unmatchedCountB) * $unmatchedPenaltyBase * $unmatchedMultiplier;

-        $totalDistance += $imperfectMatchCount * $this->config->getImperfectMatchPenalty();
-
-        return $totalDistance;
+        return $totalDistance + $imperfectMatchCount * $this->config->getImperfectMatchPenalty();
     }

     /**
@@ @@

     /**
      * Count common letters between two letter sets.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function countCommonLetters(array $lettersA, array $lettersB): int
     {
@@ @@

         foreach ($lettersA as $letterA) {
             if (in_array($letterA, $lettersB, true)) {
-                $commonCount++;
+                ++$commonCount;
             }
         }
    ----------- end diff -----------

Applied rules:
 * SimplifyUselessVariableRector
 * PostIncDecToPreIncDecRector
 * AddParamArrayDocblockFromDimFetchAccessRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


56) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarity/WordMatchScorer.php:12

    ---------- begin diff ----------
@@ @@
 class WordMatchScorer
 {
     private WordSimilarityComparatorConfig $config;
+
     private WordSimilarityCalculator $wordSimilarityCalculator;

     public function __construct(WordSimilarityComparatorConfig $config)
@@ @@
         $emptyTextPenalty = $this->calculateEmptyTextPenalty($queryWords);
         $baseIncrement = $this->config->getBaseIncrement();

-        if (empty($textWords)) {
+        if ($textWords === []) {
             return $emptyTextPenalty * $sigma;
         }

         $bestScores = $this->findBestScoresForQuery($queryWords, $textWords);

-        if (empty($bestScores)) {
+        if ($bestScores === []) {
             return $emptyTextPenalty * $sigma;
         }

@@ @@

         foreach ($scores as $score) {
             if ($score > $threshold) {
-                $badMatchCount++;
+                ++$badMatchCount;
             }
         }

@@ @@
         return $badMatchCount;
     }

+    /**
+     * @param string[] $queryWords
+     */
     private function calculateEmptyTextPenalty(array $queryWords): float
     {
         $wordCount = count($queryWords);
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * NewlineBetweenClassLikeStmtsRector
 * PostIncDecToPreIncDecRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


57) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarity/WordSimilarityCalculator.php:12

    ---------- begin diff ----------
@@ @@
 class WordSimilarityCalculator
 {
     private WordSimilarityComparatorConfig $config;
+
     private LetterDistanceCalculator $letterDistanceCalculator;

     public function __construct(WordSimilarityComparatorConfig $config)
@@ @@

     /**
      * Count common letters between two letter sets.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function countCommonLetters(array $lettersA, array $lettersB): int
     {
@@ @@

         foreach ($lettersA as $letterA) {
             if (in_array($letterA, $lettersB, true)) {
-                $commonCount++;
+                ++$commonCount;
             }
         }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * PostIncDecToPreIncDecRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


58) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarityComparator.php:7

    ---------- begin diff ----------
@@ @@
 use Fuzzy\Contracts\StringNormalizerInterface;
 use Fuzzy\Config\WordSimilarityComparatorConfig;
 use Fuzzy\Services\Algorithms\WordSimilarity\WordMatchScorer;
-use Fuzzy\Services\Algorithms\WordSimilarity\LetterDistanceCalculator;

 /**
  * Advanced lexical similarity comparator for strings.
@@ @@
 class WordSimilarityComparator
 {
     private StringNormalizerInterface $normalizer;
+
     private WordSimilarityComparatorConfig $config;
+
     private WordMatchScorer $wordMatchScorer;
-    private LetterDistanceCalculator $letterDistanceCalculator;

     /**
      * Constructor for WordSimilarityComparator.
@@ @@
         $this->normalizer = $normalizer;
         $this->config = $config ?? WordSimilarityComparatorConfig::createDefault();
         $this->wordMatchScorer = new WordMatchScorer($this->config);
-        $this->letterDistanceCalculator = new LetterDistanceCalculator($this->config);
     }

     /**
@@ @@
         }

         // Empty query handling
-        if (empty($queryWords)) {
+        if ($queryWords === []) {
             return $this->config->getMaxScoreCap();
         }

         // Empty text handling - penalty based on query word count
-        if (empty($textWords)) {
+        if ($textWords === []) {
             $emptyTextPenalty = $this->calculateEmptyTextPenalty($queryWords);
             return min($this->config->getMaxScoreCap(), $emptyTextPenalty);
         }
@@ @@

         $filteredQueryWords = $this->filterShortWords($queryWords);

-        if (empty($filteredQueryWords)) {
+        if ($filteredQueryWords === []) {
             return $this->config->getMaxScoreCap();
         }
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * NewlineBetweenClassLikeStmtsRector
 * RemoveUnusedPrivatePropertyRector


59) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/CacheManagerService.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

+use Carbon\Carbon;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Config\CacheConfig;
 use Illuminate\Support\Facades\Cache;
@@ @@
 class CacheManagerService implements CacheManagerInterface
 {
     private const MIN_CACHE_KEY_LENGTH_FOR_HASH = 250;
+
     private const STATS_CACHE_TYPE = 'stats';

     private CacheConfig $config;
@@ @@

     /**
      * Extract model class from parameters array
+     * @param array<int, mixed> $parameters
      */
     private function extractModelClassFromParameters(array $parameters): ?string
     {
@@ @@
             return $parameters[0];
         }

-        // Pour search_in_models: [modelClasses, query, options]
-        if (isset($parameters[0]) && is_array($parameters[0])) {
-            // Pour l'invalidation, on ne stocke pas tous les modèles
-            // On retourne null car l'invalidation se fera par modèle individuel
-            return null;
-        }
-
         return null;
     }

@@ @@
         // Structure des données stockées
         $keyData = [
             'key' => $key,
-            'created_at' => time(),
+            'created_at' => Carbon::now()
+                ->getTimestamp(),
         ];

         if ($modelClass !== null) {
@@ @@
                 $keyExists = true;
                 break;
             }
+
             if (is_string($existingKeyData) && $existingKeyData === $key) {
                 $keyExists = true;
                 break;
@@ @@
      * Remove stats key from stored keys tracking.
      *
      * @param string $statsKey The stats cache key to remove
-     * @return void
      */
     private function removeStatsKeyFromStorage(string $statsKey): void
     {
@@ @@
                 $keyRemoved = true;
                 continue;
             }
+
             $keysToKeep[] = $keyData;
         }
    ----------- end diff -----------

Applied rules:
 * TimeFuncCallToCarbonRector
 * NewlineBetweenClassLikeStmtsRector
 * NewlineAfterStatementRector
 * RemoveUselessReturnTagRector
 * RemoveDeadConditionAboveReturnRector
 * AddParamArrayDocblockFromDimFetchAccessRector


60) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/FuzzySearchService.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

+use Fuzzy\Data\SearchResultData;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\IndexManagerInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
@@ @@
     {
         return $this->cacheManager->remember(
             type: 'search',
-            callback: fn() => $this->executeSearch($query, $options),
+            callback: fn(): Collection => $this->executeSearch($query, $options),
             parameters: [$query, $options]
         );
     }
@@ @@
      *
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     private function executeSearch(string $query, array $options = []): Collection
     {
@@ @@
     {
         return $this->cacheManager->remember(
             type: 'search_in_model',
-            callback: fn() => $this->searchProcessor->searchInModel($modelClass, $query, $options),
+            callback: fn(): Collection => $this->searchProcessor->searchInModel($modelClass, $query, $options),
             parameters: [$modelClass, $query, $options]
         );
     }
@@ @@
     {
         return $this->cacheManager->remember(
             type: 'search_in_models',
-            callback: fn() => $this->searchProcessor->searchInModels($modelClasses, $query, $options),
+            callback: fn(): Collection => $this->searchProcessor->searchInModels($modelClasses, $query, $options),
             parameters: [$modelClasses, $query, $options]
         );
     }
    ----------- end diff -----------

Applied rules:
 * AddArrowFunctionReturnTypeRector


61) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/IndexBuilder.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

+use ArrayAccess;
 use Fuzzy\Contracts\ContextualNormalizerInterface;
 use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Models\FuzzyIndex;
@@ @@
      * from the model to preserve stop words where appropriate.
      *
      * @param MustFuzzySearch $model The searchable model instance to index
-     * @return void
      */
     public function indexModel(MustFuzzySearch $model): void
     {
@@ @@
     {
         // Try Eloquent's getAttribute method
         if (method_exists($model, 'getAttribute')) {
-            /** @var \Illuminate\Database\Eloquent\Model $model */
+            /** @var Model $model */
             return $model->getAttribute($field);
         }

@@ @@
         }

         // Try array access if model implements ArrayAccess
-        if ($model instanceof \ArrayAccess && isset($model[$field])) {
+        if ($model instanceof ArrayAccess && isset($model[$field])) {
             return $model[$field];
         }

@@ @@
      * @param mixed $modelId The model's primary key value
      * @param string $field The field name being indexed
      * @param string $value The field value to index
-     * @return void
      */
     public function indexField(string $modelType, mixed $modelId, string $field, string $value): void
     {
@@ @@

         $words = $this->normalizer->splitIntoWords($normalizedValue);

-        if (empty($words)) {
+        if ($words === []) {
             return;
         }

@@ @@
      * Efficiently indexes an array of models in a single operation.
      *
      * @param array<MustFuzzySearch|Model> $models Array of models to index
-     * @return void
      */
     public function batchIndex(array $models): void
     {
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * RemoveUselessReturnTagRector


62) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/IndexManagerService.php:13

    ---------- begin diff ----------
@@ @@
 class IndexManagerService implements IndexManagerInterface
 {
     private const REINDEX_CHUNK_SIZE = 100;
+
     private const PCT_FACTOR = 100;

     public function __construct(
@@ @@
         return $this->indexRepository->getStats();
     }

+    /**
+     * @return array<string, mixed>
+     */
     public function getPreciseModelStats(string $modelClass): array
     {
         $this->modelDiscovery->validateModel($modelClass);
@@ @@
         $totalRecords = 0;
         $indexableRecords = 0;

-        $modelClass::chunk(self::REINDEX_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexableRecords) {
+        $modelClass::chunk(self::REINDEX_CHUNK_SIZE, function ($models) use (&$totalRecords, &$indexableRecords): void {
             $totalRecords += count($models);

             foreach ($models as $model) {
                 if ($model->shouldBeIndexed()) {
-                    $indexableRecords++;
+                    ++$indexableRecords;
                 }
             }
         });
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * PostIncDecToPreIncDecRector
 * DocblockReturnArrayFromDirectArrayInstanceRector
 * AddClosureVoidReturnTypeWhereNoReturnRector


63) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/ModelDiscoveryService.php:13

    ---------- begin diff ----------
@@ @@
 class ModelDiscoveryService implements ModelDiscoveryInterface
 {
     private const EXTRACT_NAMESPACE_REGEX = '/namespace\s+(.+?);/s';
+
     private const EXTRACT_CLASS_REGEX = '/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/';

     /**
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


64) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/PipelineStageManager.php:40

    ---------- begin diff ----------
@@ @@
         $customStages = config('fuzzy.pipeline', []);

         // Validate each custom stage
-        foreach ($customStages as $index => $stage) {
+        foreach ($customStages as $stage) {
             $this->validateStage($stage);
         }

@@ @@
             if (in_array($stage, $seen, true)) {
                 throw DuplicateStageException::duplicate($stage, $index + 1);
             }
+
             $seen[] = $stage;
         }
     }
    ----------- end diff -----------

Applied rules:
 * NewlineAfterStatementRector
 * RemoveUnusedForeachKeyRector


65) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/RelevanceScoringStageTest.php:5

    ---------- begin diff ----------
@@ @@
 namespace Fuzzy\Tests\Unit\Stages;

 use Fuzzy\Contracts\IndexRepositoryInterface;
-use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Config\RelevanceScoringConfig;
 use Fuzzy\Data\SearchOptionsData;
 use Fuzzy\Data\SearchResultData;
@@ @@
 use Fuzzy\ValueObjects\SearchQuery;
 use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
 use ReflectionMethod;
-use ReflectionProperty;

 #[AllowMockObjectsWithoutExpectations]
 final class RelevanceScoringStageTest extends TestCase
 {
     private RelevanceScoringStage $stage;
-    private WordSimilarityComparator $comparator;
-    private StringNormalizer $normalizer;
     private RelevanceScoringConfig $config;

     /**
@@ @@
     {
         parent::setUp();

-        $this->normalizer = new StringNormalizer();
-        $this->comparator = new WordSimilarityComparator(
-            normalizer: $this->normalizer
+        $normalizer = new StringNormalizer();
+        $comparator = new WordSimilarityComparator(
+            normalizer: $normalizer
         );
         $this->config = RelevanceScoringConfig::createDefault();

-        $this->stage = new RelevanceScoringStage($this->comparator, $this->config);
+        $this->stage = new RelevanceScoringStage($comparator, $this->config);
     }

     /**
@@ @@
     {
         // Arrange: Many results without explicit maxResults
         $results = [];
-        for ($i = 0; $i < 30; $i++) {
+        for ($i = 0; $i < 30; ++$i) {
             $results[] = $this->createSearchResult('Test ' . $i, 'Test ' . $i);
         }

         $context = $this->createSearchContext('test', $results);
-        $context->options = new SearchOptionsData(); // Uses default maxResults
+        $context->options = new SearchOptionsData();
+         // Uses default maxResults
         $next = $this->createNextCallback($context);

         // Act: Process without explicit limit
@@ @@
         $this->assertCount(1, $processedResults);

         $resultItem = $processedResults[0];
-        $this->assertEquals(85.5, $resultItem->score);
+        $this->assertEqualsWithDelta(85.5, $resultItem->score, PHP_FLOAT_EPSILON);
         $this->assertEquals('User', $resultItem->modelType);
         $this->assertEquals('name', $resultItem->matchedField);
         $this->assertEquals('John Doe', $resultItem->matchedValue);
@@ @@
                 $expected,
                 $result,
                 0.01,
-                "Failed for input: $input. Got: $result, Expected: $expected"
+                sprintf('Failed for input: %s. Got: %s, Expected: %s', $input, $result, $expected)
             );
         }
     }
@@ @@

         // Verify descending order by combined score
         $sorted = $combinedResults->values()->all();
-        for ($i = 0; $i < count($sorted) - 1; $i++) {
+        for ($i = 0; $i < count($sorted) - 1; ++$i) {
             $this->assertGreaterThanOrEqual($sorted[$i + 1]->combinedScore, $sorted[$i]->combinedScore);
         }
     }
@@ @@

     /**
      * Create a search context for testing.
+     * @param SearchResultData[] $results
      */
     private function createSearchContext(
         string $queryString,
@@ @@

     /**
      * Invoke a private method on an object.
+     * @param array<mixed[], mixed> $args
      */
     private function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
     {
@@ @@
         $reflection->setAccessible(true);

         return $reflection->invokeArgs($object, $args);
-    }
-
-    /**
-     * Set a private property value on an object.
-     */
-    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
-    {
-        $reflection = new ReflectionProperty($object, $propertyName);
-        $reflection->setAccessible(true);
-        $reflection->setValue($object, $value);
     }
 }
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * RemoveUnusedPrivateMethodRector
 * NarrowUnusedSetUpDefinedPropertyRector
 * AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


66) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/ScoringStageTest.php:17

    ---------- begin diff ----------
@@ @@
 use Fuzzy\ValueObjects\SearchQuery;
 use InvalidArgumentException;
 use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
-use ReflectionClass;
 use ReflectionMethod;
 use ReflectionProperty;
 use stdClass;
    ----------- end diff -----------

Applied rules:


67) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/SortAndLimitStageTest.php:5

    ---------- begin diff ----------
@@ @@
 namespace Fuzzy\Tests\Unit\Stages;

 use Fuzzy\Contracts\IndexRepositoryInterface;
-use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use Fuzzy\Tests\TestCase;
 use Fuzzy\Stages\SortAndLimitStage;
@@ @@

     /**
      * Create a search context for testing.
+     * @param array<mixed[], mixed> $results
      */
     private function createSearchContext(
         string $queryString,
    ----------- end diff -----------

Applied rules:
 * ClassMethodArrayDocblockParamFromLocalCallsRector


68) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/database/migrations/2024_01_01_000000_create_non_indexable_users_table.php:17

    ---------- begin diff ----------
@@ @@
      */
     public function up(): void
     {
-        Schema::create('non_indexable_users', function (Blueprint $table) {
+        Schema::create('non_indexable_users', function (Blueprint $table): void {
             $table->id();
             $table->string('name');
             $table->string('email')->unique();
    ----------- end diff -----------

Applied rules:
 * AddClosureVoidReturnTypeWhereNoReturnRector


69) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/database/migrations/2024_01_01_000000_create_non_searchable_models_table.php:17

    ---------- begin diff ----------
@@ @@
      */
     public function up(): void
     {
-        Schema::create('non_searchable_models', function (Blueprint $table) {
+        Schema::create('non_searchable_models', function (Blueprint $table): void {
             $table->id();
             $table->string('name');
             $table->string('email')->unique();
    ----------- end diff -----------

Applied rules:
 * AddClosureVoidReturnTypeWhereNoReturnRector


70) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/ResultFilterService.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

+use Fuzzy\Data\SearchResultData;
 use Fuzzy\Contracts\ResultFilterInterface;
 use Illuminate\Support\Collection;

@@ @@
     public function filterAndSort(Collection $results, float $minScore): Collection
     {
         return $results
-            ->filter(fn($result): bool => $result !== null && $result->score >= $minScore)
+            ->filter(fn($result): bool => $result instanceof SearchResultData && $result->score >= $minScore)
             ->sortByDesc('score')
             ->values();
     }
    ----------- end diff -----------

Applied rules:
 * FlipTypeControlToUseExclusiveTypeRector


71) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Scoring/ScoringEngine.php:113

    ---------- begin diff ----------
@@ @@

     /**
      * Check if the score has reached the maximum possible value.
-     *
-     * @param float $score
-     * @return bool
      */
     private function isPerfectScore(float $score): bool
     {
@@ @@

     /**
      * Check if no scoring strategy matched the index entry.
-     *
-     * @param float $score
-     * @return bool
      */
     private function hasNoMatch(float $score): bool
     {
@@ @@
      * Check if multi-word scoring can be performed.
      *
      * @param array<int, array<string, mixed>> $indexEntries
-     * @param SearchContextInterface $context
-     * @return bool
      */
     private function cannotCalculateMultiWordScore(array $indexEntries, SearchContextInterface $context): bool
     {
@@ @@
      * Find the best similarity score for each query word against all index entries.
      *
      * @param array<int, array<string, mixed>> $indexEntries
-     * @param SearchContextInterface $context
      * @param array<int, string> $queryWords
      * @return array<int, float>
      */
@@ @@
      * Find the best similarity score for a single query word against all index entries.
      *
      * @param array<int, array<string, mixed>> $indexEntries
-     * @param SearchContextInterface $context
-     * @param string $queryWord
-     * @return float
      */
     private function findBestMatchingScoreForWord(array $indexEntries, SearchContextInterface $context, string $queryWord): float
     {
@@ @@

     /**
      * Check if a similarity score meets or exceeds the configured threshold.
-     *
-     * @param float $similarity
-     * @param SearchContextInterface $context
-     * @return bool
      */
     private function isScoreAboveThreshold(float $similarity, SearchContextInterface $context): bool
     {
@@ @@

     /**
      * Check if a word was successfully matched (score > minimum).
-     *
-     * @param float $score
-     * @return bool
      */
     private function isWordMatched(float $score): bool
     {
@@ @@
      * Check if no query words were matched against any index entry.
      *
      * @param array<int, float> $matchedScores
-     * @return bool
      */
     private function noWordsMatched(array $matchedScores): bool
     {
@@ @@
      * Calculate the average score of all matched query words.
      *
      * @param array<int, float> $matchedScores
-     * @return float
      */
     private function calculateAverageScore(array $matchedScores): float
     {
@@ @@
      *
      * @param array<int, float> $matchedScores
      * @param array<int, string> $queryWords
-     * @return float
      */
     private function calculateCoverageRatio(array $matchedScores, array $queryWords): float
     {
@@ @@
      * Get coverage bonus based on the coverage ratio using configured thresholds.
      *
      * @param float $coverageRatio Value between 0 and 1
-     * @return float
      */
     private function getCoverageBonus(float $coverageRatio): float
     {
@@ @@

     /**
      * Compute the final weighted score using average, coverage, and bonus.
-     *
-     * @param float $averageScore
-     * @param float $coverageRatio
-     * @param float $coverageBonus
-     * @return float
      */
     private function computeWeightedScore(float $averageScore, float $coverageRatio, float $coverageBonus): float
     {
@@ @@
     /**
      * Calculate fallback score when no strategy matches the index entry.
      *
-     * @param SearchContextInterface $context
      * @param array<string, mixed> $indexEntry
-     * @return float
      */
     private function calculateFallbackScore(SearchContextInterface $context, array $indexEntry): float
     {
@@ @@
     /**
      * Apply field-specific weighting to the calculated score.
      *
-     * @param float $score
      * @param array<string, mixed> $match
-     * @return float
      */
     private function applyFieldWeighting(float $score, array $match): float
     {
@@ @@

     /**
      * Normalize score to ensure it stays within allowed bounds.
-     *
-     * @param float $score
-     * @return float
      */
     private function normalizeScore(float $score): float
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessParamTagRector
 * RemoveUselessReturnTagRector


72) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/SearchProcessorService.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

+use Fuzzy\Stages\NormalizeQueryStage;
+use Fuzzy\Stages\MatchDiscoveryStage;
+use Fuzzy\Stages\ScoringStage;
+use Fuzzy\Stages\RelevanceScoringStage;
+use Fuzzy\Stages\SortAndLimitStage;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
 use Fuzzy\Contracts\ResultFilterInterface;
@@ @@

     /**
      * {@inheritDoc}
+     * @param array<string, mixed> $options
      */
     public function searchInModel(string $modelClass, string $query, array $options = []): Collection
     {
@@ @@
     private function getPipelineStages(): array
     {
         return config('fuzzy.pipeline.stages', [
-            \Fuzzy\Stages\NormalizeQueryStage::class,
-            \Fuzzy\Stages\MatchDiscoveryStage::class,
-            \Fuzzy\Stages\ScoringStage::class,
-            \Fuzzy\Stages\RelevanceScoringStage::class,
-            \Fuzzy\Stages\SortAndLimitStage::class,
+            NormalizeQueryStage::class,
+            MatchDiscoveryStage::class,
+            ScoringStage::class,
+            RelevanceScoringStage::class,
+            SortAndLimitStage::class,
         ]);
     }
 }
    ----------- end diff -----------

Applied rules:
 * ClassMethodArrayDocblockParamFromLocalCallsRector


73) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/ServiceRegistrar.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

+use RuntimeException;
+use Illuminate\Contracts\Console\Kernel;
 use Fuzzy\Commands\ClearCacheCommand;
 use Fuzzy\Commands\ClearIndexCommand;
 use Fuzzy\Commands\IndexSearchCommand;
@@ @@
     /**
      * Register and load helper functions.
      *
-     * @throws \RuntimeException If helpers.php file is not found
+     * @throws RuntimeException If helpers.php file is not found
      */
     private function registerHelpers(): void
     {
         $helpersPath = __DIR__ . '/../helpers.php';
         if (!file_exists($helpersPath)) {
-            throw new \RuntimeException("helpers.php not found at: {$helpersPath}");
+            throw new RuntimeException('helpers.php not found at: ' . $helpersPath);
         }
+
         require_once $helpersPath;
     }

@@ @@
         $this->mergeConfigFrom(__DIR__ . '/../../config/fuzzy.php', 'fuzzy');

         // Register configuration classes as singletons
-        $this->app->singleton(SimilarityCalculatorConfig::class, fn() => SimilarityCalculatorConfig::createDefault());
-        $this->app->singleton(AdvancedScoringConfig::class, fn() => AdvancedScoringConfig::fromConfig());
+        $this->app->singleton(SimilarityCalculatorConfig::class, fn(): SimilarityCalculatorConfig => SimilarityCalculatorConfig::createDefault());
+        $this->app->singleton(AdvancedScoringConfig::class, fn(): AdvancedScoringConfig => AdvancedScoringConfig::fromConfig());

         // Register algorithm-specific configs with fromConfig() method
-        $this->app->singleton(LongestCommonSubstringConfig::class, fn() => LongestCommonSubstringConfig::fromConfig());
-        $this->app->singleton(LevenshteinAlgorithmConfig::class, fn() => LevenshteinAlgorithmConfig::fromConfig());
-        $this->app->singleton(PrefixAlgorithmConfig::class, fn() => PrefixAlgorithmConfig::fromConfig());
+        $this->app->singleton(LongestCommonSubstringConfig::class, fn(): LongestCommonSubstringConfig => LongestCommonSubstringConfig::fromConfig());
+        $this->app->singleton(LevenshteinAlgorithmConfig::class, fn(): LevenshteinAlgorithmConfig => LevenshteinAlgorithmConfig::fromConfig());
+        $this->app->singleton(PrefixAlgorithmConfig::class, fn(): PrefixAlgorithmConfig => PrefixAlgorithmConfig::fromConfig());

         // Register WordSimilarityComparator config
-        $this->app->singleton(WordSimilarityComparatorConfig::class, fn() => WordSimilarityComparatorConfig::fromConfig());
+        $this->app->singleton(WordSimilarityComparatorConfig::class, fn(): WordSimilarityComparatorConfig => WordSimilarityComparatorConfig::fromConfig());
     }

     /**
@@ @@
     private function registerCoreServices(): void
     {
         // Register normalizers as singletons
-        $this->app->singleton(ContextualNormalizerInterface::class, fn() => new StringNormalizer());
-        $this->app->singleton(StringNormalizer::class, fn() => new StringNormalizer());
+        $this->app->singleton(ContextualNormalizerInterface::class, fn(): StringNormalizer => new StringNormalizer());
+        $this->app->singleton(StringNormalizer::class, fn(): StringNormalizer => new StringNormalizer());

         // Register service managers
-        $this->app->singleton(ResultFilterService::class, fn() => new ResultFilterService());
-        $this->app->singleton(CacheManagerService::class, fn() => new CacheManagerService());
-        $this->app->singleton(ModelDiscoveryService::class, fn() => new ModelDiscoveryService());
+        $this->app->singleton(ResultFilterService::class, fn(): ResultFilterService => new ResultFilterService());
+        $this->app->singleton(CacheManagerService::class, fn(): CacheManagerService => new CacheManagerService());
+        $this->app->singleton(ModelDiscoveryService::class, fn(): ModelDiscoveryService => new ModelDiscoveryService());

         // PipelineStageManager as singleton
-        $this->app->singleton(PipelineStageManager::class, fn($app) => new PipelineStageManager($app));
+        $this->app->singleton(PipelineStageManager::class, fn($app): PipelineStageManager => new PipelineStageManager($app));

         // Index Builder - uses ContextualNormalizerInterface
-        $this->app->singleton(IndexBuilder::class, fn($app) => new IndexBuilder(
+        $this->app->singleton(IndexBuilder::class, fn($app): IndexBuilder => new IndexBuilder(
             $app->make(ContextualNormalizerInterface::class)
         ));

         // Index Manager
-        $this->app->singleton(IndexManagerService::class, fn($app) => new IndexManagerService(
+        $this->app->singleton(IndexManagerService::class, fn($app): IndexManagerService => new IndexManagerService(
             indexBuilder: $app->make(IndexBuilder::class),
             indexRepository: $app->make(IndexRepositoryInterface::class),
             modelDiscovery: $app->make(ModelDiscoveryInterface::class)
@@ @@
         ));

         // Similarity Calculator with algorithm registration
-        $this->app->singleton(SimilarityCalculator::class, function ($app) {
+        $this->app->singleton(SimilarityCalculator::class, function ($app): SimilarityCalculator {
             $calculator = new SimilarityCalculator($app->make(SimilarityCalculatorConfig::class));

             // Register all similarity algorithms with their specific configurations
@@ @@
         });

         // Advanced Scoring Calculator
-        $this->app->singleton(AdvancedScoringCalculator::class, fn($app) => new AdvancedScoringCalculator(
+        $this->app->singleton(AdvancedScoringCalculator::class, fn($app): AdvancedScoringCalculator => new AdvancedScoringCalculator(
             $app->make(AdvancedScoringConfig::class)
         ));

         // Pipeline Manager with configured stages
-        $this->app->singleton(PipelineManagerService::class, function ($app) {
+        $this->app->singleton(PipelineManagerService::class, function ($app): PipelineManagerService {
             $stageClasses = $this->stageManager->getMergedStages();
             $stages = $this->stageManager->createStageInstances($stageClasses);

@@ @@
         });

         // Search Processor - Core search orchestration
-        $this->app->singleton(SearchProcessorService::class, fn($app) => new SearchProcessorService(
+        $this->app->singleton(SearchProcessorService::class, fn($app): SearchProcessorService => new SearchProcessorService(
             pipeline: $app->make(Pipeline::class),
             normalizer: $app->make(StringNormalizer::class),
             similarityCalculator: $app->make(SimilarityCalculator::class),
@@ @@
         ));

         // Main Search Service - bound to interface for easy resolution
-        $this->app->singleton(SearchServiceInterface::class, fn($app) => new FuzzySearchService(
+        $this->app->singleton(SearchServiceInterface::class, fn($app): FuzzySearchService => new FuzzySearchService(
             cacheManager: $app->make(CacheManagerInterface::class),
             modelDiscovery: $app->make(ModelDiscoveryInterface::class),
             indexManager: $app->make(IndexManagerInterface::class),
@@ @@
      */
     private function registerAlgorithms(): void
     {
-        $this->app->singleton(WordSimilarityComparator::class, fn($app) => new WordSimilarityComparator(
+        $this->app->singleton(WordSimilarityComparator::class, fn($app): WordSimilarityComparator => new WordSimilarityComparator(
             normalizer: $app->make(StringNormalizer::class),
             config: $app->make(WordSimilarityComparatorConfig::class)
         ));
@@ @@
      */
     private function registerScoring(): void
     {
-        $this->app->singleton(ScoringEngineInterface::class, function ($app) {
+        $this->app->singleton(ScoringEngineInterface::class, function ($app): ScoringEngine {
             $calculator = $app->make(AdvancedScoringCalculator::class);

             return new ScoringEngine(
@@ @@
      *
      * Usage:
      * php artisan vendor:publish --tag=fuzzy-migrations
-     *
-     * @return void
      */
     private function publishMigrationsIfNotExists(): void
     {
@@ @@
         }

         // Display skipped files message if any migrations were skipped
-        if (!empty($skippedFiles) && $this->app->runningInConsole()) {
+        if ($skippedFiles !== [] && $this->app->runningInConsole()) {
             $this->outputSkippedMigrationsMessage($skippedFiles);
         }

         // Publish only the migrations that don't exist
-        if (!empty($filesToPublish)) {
+        if ($filesToPublish !== []) {
             $this->publishes($filesToPublish, 'fuzzy-migrations');
         } elseif ($this->app->runningInConsole()) {
             $this->outputAllMigrationsSkippedMessage();
@@ @@
      * Display a message showing which migration files were skipped.
      *
      * @param array<int, string> $skippedFiles List of skipped migration file names
-     * @return void
      */
     private function outputSkippedMigrationsMessage(array $skippedFiles): void
     {
@@ @@
             $count === 1 ? 's' : ''
         );

-        $this->app->make('Illuminate\Contracts\Console\Kernel')->getOutput()->writeln($message);
+        $this->app->make(Kernel::class)->getOutput()->writeln($message);

         foreach ($skippedFiles as $file) {
-            $this->app->make('Illuminate\Contracts\Console\Kernel')->getOutput()->writeln(
+            $this->app->make(Kernel::class)->getOutput()->writeln(
                 sprintf("     <fg=gray>→ %s</fg=gray>", $file)
             );
         }

-        $this->app->make('Illuminate\Contracts\Console\Kernel')->getOutput()->writeln(
+        $this->app->make(Kernel::class)->getOutput()->writeln(
             "     <fg=yellow;options=bold>💡 Skipped to preserve existing custom migrations. Use --force to overwrite.</>"
         );
-        $this->app->make('Illuminate\Contracts\Console\Kernel')->getOutput()->writeln('');
+        $this->app->make(Kernel::class)->getOutput()->writeln('');
     }

     /**
      * Display a message when all migrations were skipped.
-     *
-     * @return void
      */
     private function outputAllMigrationsSkippedMessage(): void
     {
-        $output = $this->app->make('Illuminate\Contracts\Console\Kernel')->getOutput();
+        $output = $this->app->make(Kernel::class)->getOutput();

         $output->writeln('');
         $output->writeln('  <fg=yellow;options=bold>📁 All migration files already exist and were preserved.</>');
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * EncapsedStringsToSprintfRector
 * NewlineAfterStatementRector
 * RemoveUselessReturnTagRector
 * StringClassNameToClassConstantRector
 * AddArrowFunctionReturnTypeRector
 * ClosureReturnTypeRector


74) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/SimilarityCalculator.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Services;

-use Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm;
-use Fuzzy\Services\Algorithms\LevenshteinSimilarityAlgorithm;
-use Fuzzy\Services\Algorithms\PrefixSimilarityAlgorithm;
 use Fuzzy\Contracts\SimilarityAlgorithmInterface;
 use Fuzzy\Config\SimilarityCalculatorConfig;

@@ @@
 {
     /** @var array<int, SimilarityAlgorithmInterface> */
     private array $algorithms = [];
+
     private SimilarityCalculatorConfig $config;

     public function __construct(?SimilarityCalculatorConfig $config = null)
@@ @@

     private function calculateCompositeWordSimilarity(string $firstWord, string $secondWord): float
     {
-        if (empty($this->algorithms)) {
+        if ($this->algorithms === []) {
             return FUZZY_SCORE_NONE;
         }

@@ @@
         $firstWords = $this->splitIntoWords($normalizedFirstString);
         $secondWords = $this->splitIntoWords($normalizedSecondString);

-        if (empty($firstWords) || empty($secondWords)) {
+        if ($firstWords === [] || $secondWords === []) {
             return FUZZY_SCORE_NONE;
         }
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * NewlineBetweenClassLikeStmtsRector


75) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/StringNormalizer.php:19

    ---------- begin diff ----------
@@ @@
 class StringNormalizer implements ContextualNormalizerInterface
 {
     private const STOP_WORD_REMOVAL_THRESHOLD = 3;
+
     private const MIN_KEYWORD_LENGTH = 3;
+
     private const DEFAULT_MAX_KEYWORDS = 10;
+
     private const REGEX_REMOVE_SPECIAL_CHARS = '/[^a-z0-9\s_-]/';
+
     private const REGEX_COLLAPSE_SPACES = '/\s+/';
+
     private const REGEX_WORD_SPLITTER = '/\s+/';
+
     private const WORD_SEPARATORS = ['_', '-'];
+
     private const EMPTY_STRING = '';
+
     private const ZERO_STRING = '0';

     /**
@@ @@

     /**
      * Current field being processed (for contextual normalization).
-     *
-     * @var string|null
      */
     private ?string $currentField = null;

@@ @@

     /**
      * Load stop words from internal language files based on application locale.
-     *
-     * @return void
      */
     private function loadStopWords(): void
     {
@@ @@
         if (function_exists('app') && method_exists(app(), 'getLocale')) {
             return app()->getLocale();
         }
+
         return $_ENV['APP_LOCALE'] ?? 'en';
     }

@@ @@
         if ($this->shouldRemoveStopWords()) {
             $filteredWords = array_filter(
                 $words,
-                fn($word): bool => !in_array($word, $this->stopWords, true)
+                fn(string $word): bool => !in_array($word, $this->stopWords, true)
             );
         } else {
             // Keep all words for protected fields (names, emails, etc.)
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * NewlineAfterStatementRector
 * RemoveUselessReturnTagRector
 * RemoveUselessVarTagRector
 * AddArrayFunctionClosureParamTypeRector


76) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/MatchDiscoveryStage/IndexOptimizer.php:13

    ---------- begin diff ----------
@@ @@
 class IndexOptimizer
 {
     private array $cachedOptimizedIndexes = [];
+
     private array $cacheTimestamps = [];
+
     private MatchDiscoveryConfig $config;

     public function __construct(?MatchDiscoveryConfig $config = null)
@@ @@

     /**
      * Build optimized index structures.
+     * @return array<string, mixed[]>
      */
     private function buildOptimizedIndexes(array $wordIndex): array
     {
@@ @@
             if (!isset($byLength[$wordLength])) {
                 $byLength[$wordLength] = [];
             }
+
             $byLength[$wordLength][$wordString] = $matches;

             $firstChar = $wordString[0];
@@ @@
             if (!isset($byFirstChar[$firstChar])) {
                 $byFirstChar[$firstChar] = [];
             }
+
             $byFirstChar[$firstChar][$wordString] = $matches;

             if ($wordLength >= $this->config->getMinTrigramLength()) {
@@ @@

     /**
      * Add word to trigram index.
+     * @param never[][] $trigramIndex
      */
     private function addToTrigramIndex(string $word, array $matches, array &$trigramIndex): void
     {
@@ @@
             if (!isset($trigramIndex[$trigram])) {
                 $trigramIndex[$trigram] = [];
             }
+
             $trigramIndex[$trigram][$word] = $matches;
         }
     }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * NewlineAfterStatementRector
 * AddParamArrayDocblockFromAssignsParamToParamReferenceRector
 * DocblockReturnArrayFromDirectArrayInstanceRector


77) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/MatchDiscoveryStage/MatchFinder.php:13

    ---------- begin diff ----------
@@ @@
 class MatchFinder
 {
     private MatchDiscoveryConfig $config;
+
     private IndexOptimizer $optimizer;

     public function __construct(?MatchDiscoveryConfig $config = null)
@@ @@

     /**
      * Simple fuzzy match discovery for small indexes.
+     * @param array<string, mixed> $wordIndex
      */
     private function discoverFuzzyMatchesSimple(SearchContextInterface $context, array $wordIndex): void
     {
@@ @@

     /**
      * Find words containing the query word.
+     * @param array<int, mixed> $byLength
      */
     private function findContainedMatches(
         string $queryWord,
@@ @@
         }

         return $queryWord[0] === $indexedWord[0];
-    }
-
-    /**
-     * Add all matches to context.
-     */
-    private function addAllMatches(SearchContextInterface $context, array $matches): void
-    {
-        foreach ($matches as $match) {
-            $context->addPotentialMatch($match);
-        }
     }
 }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * RemoveUnusedPrivateMethodRector
 * AddParamArrayDocblockFromDimFetchAccessRector


78) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/RelevanceScoringStage.php:83

    ---------- begin diff ----------
@@ @@
     private function calculateRelevanceScores(SearchContextInterface $context): Collection
     {
         return collect($context->results)
-            ->map(function (object $result) use ($context) {
+            ->map(function (object $result) use ($context): object {
                 $relevance = $this->calculateRelevanceForResult($result, $context);
                 $result->relevance = $relevance;
                 return $result;
@@ @@
             ->take($maxResults)
             ->values()
             ->all();
-    }
-
-    /**
-     * Combine relevance with original score for final ranking.
-     *
-     * @param Collection<int, object> $results Results with relevance scores
-     * @return Collection<int, object> Results with combined scores
-     */
-    private function combineScores(Collection $results): Collection
-    {
-        return $results
-            ->map(function (object $result) {
-                $normalizedRelevance = $this->normalizeRelevance($result->relevance);
-                $combinedScore = ($result->score * $this->config->getOriginalScoreWeight()) +
-                    ($normalizedRelevance * $this->config->getRelevanceScoreWeight());
-
-                $result->combinedScore = $combinedScore;
-                $result->originalScore = $result->score;
-                $result->relevanceScore = $normalizedRelevance;
-
-                return $result;
-            })
-            ->sortByDesc('combinedScore');
-    }
-
-    /**
-     * Normalize relevance score to 0-100 scale.
-     *
-     * @param float $relevance Relevance score from comparator
-     * @return float Normalized score (100 = perfect, 0 = poor)
-     */
-    private function normalizeRelevance(float $relevance): float
-    {
-        if ($relevance <= FUZZY_DISTANCE_IDENTICAL) {
-            return $this->config->getMaxNormalizedRelevance();
-        }
-
-        $normalized = max(
-            $this->config->getMinNormalizedRelevance(),
-            $this->config->getMaxNormalizedRelevance() - ($relevance * $this->config->getNormalizationFactor())
-        );
-        return min($this->config->getMaxNormalizedRelevance(), $normalized);
     }
 }
    ----------- end diff -----------

Applied rules:
 * RemoveUnusedPrivateMethodRector
 * ClosureReturnTypeRector


79) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/ScoringStage.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Stages;

+use InvalidArgumentException;
 use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Contracts\StageInterface;
 use Fuzzy\Enums\StageType;
@@ @@
      *
      * @param array<int, array> $matches Array of matches with field and value information
      * @return array{indexable_type: string, field: string, original_value: string} Match details
-     * @throws \InvalidArgumentException If matches array is empty
+     * @throws InvalidArgumentException If matches array is empty
      */
     private function extractBestMatchDetails(array $matches): array
     {
         if ($matches === []) {
-            throw new \InvalidArgumentException('Matches array cannot be empty');
+            throw new InvalidArgumentException('Matches array cannot be empty');
         }

         return $matches[0];
    ----------- end diff -----------

Applied rules:


80) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/CommandHelpers.php:20

    ---------- begin diff ----------
@@ @@
 {
     /**
      * Get the fuzzy search service from the container.
-     *
-     * @return FuzzySearchService
      */
     protected function getSearchService(): FuzzySearchService
     {
@@ @@
      * Display a success message with a checkmark prefix.
      *
      * @param string $message The success message to display
-     * @return void
      */
     protected function showSuccess(string $message): void
     {
-        $this->info("✓ {$message}");
+        $this->info('✓ ' . $message);
     }

     /**
@@ @@
      * Display a warning message.
      *
      * @param string $message The warning message to display
-     * @return void
      */
     protected function showWarning(string $message): void
     {
@@ @@
      * Display an error message.
      *
      * @param string $message The error message to display
-     * @return void
      */
     protected function showError(string $message): void
     {
@@ @@
      * Display an info message.
      *
      * @param string $message The info message to display
-     * @return void
      */
     protected function showInfo(string $message): void
     {
@@ @@
      * Display a section header with equals signs.
      *
      * @param string $title The section title
-     * @return void
      */
     protected function showHeader(string $title): void
     {
-        $this->info("=== {$title} ===");
+        $this->info(sprintf('=== %s ===', $title));
     }

     /**
      * Display a blank line.
-     *
-     * @return void
      */
     protected function showNewLine(): void
     {
    ----------- end diff -----------

Applied rules:
 * EncapsedStringsToSprintfRector
 * RemoveUselessReturnTagRector


81) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/FuzzySearchable.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Traits;

+use Fuzzy\Contracts\MustFuzzySearch;
 use Illuminate\Support\Collection;
 use Fuzzy\Services\FuzzySearchService;

@@ @@
      *
      * Registers model event listeners to automatically manage search index
      * during create, update, and delete operations.
-     *
-     * @return void
      */
     protected static function bootFuzzySearchable(): void
     {
@@ @@
             }
         });

-        static::deleted(static function ($model): void {
+        static::deleted(static function (MustFuzzySearch $model): void {
             app(FuzzySearchService::class)->getIndexManager()->removeModel($model);
         });
     }
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector
 * ParamTypeByMethodCallTypeRector


82) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/ServiceProviderHelper.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Traits;

+use ReflectionClass;
 use Illuminate\Support\ServiceProvider;

 /**
@@ @@
      */
     protected function mergeConfigFrom(string $path, string $key): void
     {
-        $reflection = new \ReflectionClass($this->provider);
+        $reflection = new ReflectionClass($this->provider);
         $method = $reflection->getMethod('mergeConfigFrom');
         $method->setAccessible(true);
         $method->invoke($this->provider, $path, $key);
@@ @@
      */
     protected function publishes(array $paths, string $group = null): void
     {
-        $reflection = new \ReflectionClass($this->provider);
+        $reflection = new ReflectionClass($this->provider);
         $method = $reflection->getMethod('publishes');
         $method->setAccessible(true);

@@ @@
      */
     protected function commands(array $commands): void
     {
-        $reflection = new \ReflectionClass($this->provider);
+        $reflection = new ReflectionClass($this->provider);
         $method = $reflection->getMethod('commands');
         $method->setAccessible(true);
         $method->invoke($this->provider, $commands);
    ----------- end diff -----------

Applied rules:


83) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/CommandsTest.php:453

    ---------- begin diff ----------
@@ @@

     /**
      * Create a user without triggering model events.
+     * @param array<string, string> $attributes
      */
     private function createUserWithoutEvents(array $attributes): User
     {
@@ @@

     /**
      * Create a product without triggering model events.
+     * @param array<string, string>|array<string, int> $attributes
      */
     private function createProductWithoutEvents(array $attributes): Product
     {
@@ @@
      */
     private function createIndexableUsers(int $count): void
     {
-        for ($i = 1; $i <= $count; $i++) {
+        for ($i = 1; $i <= $count; ++$i) {
             User::create([
-                'name' => "User {$i}",
-                'email' => "user{$i}@example.com",
+                'name' => 'User ' . $i,
+                'email' => sprintf('user%d@example.com', $i),
                 'type' => 'user',
             ]);
         }
@@ @@
      */
     private function createNonIndexableUsers(int $count): void
     {
-        for ($i = 1; $i <= $count; $i++) {
+        for ($i = 1; $i <= $count; ++$i) {
             User::create([
-                'name' => "Admin User {$i}",
-                'email' => "admin{$i}@example.com",
+                'name' => 'Admin User ' . $i,
+                'email' => sprintf('admin%d@example.com', $i),
                 'type' => 'admin',
             ]);
         }
    ----------- end diff -----------

Applied rules:
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


84) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/ConfigurationTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Feature;

+use ReflectionClass;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
 use Fuzzy\Contracts\SearchServiceInterface;
 use Fuzzy\Services\IndexBuilder;
@@ @@
      */
     private function getFieldWeightFromIndexBuilder(IndexBuilder $indexBuilder, string $field): float
     {
-        $reflection = new \ReflectionClass($indexBuilder);
+        $reflection = new ReflectionClass($indexBuilder);
         $calculateWeightMethod = $reflection->getMethod('calculateFieldWeight');
         $calculateWeightMethod->setAccessible(true);

@@ @@
     public function test_coverage_bonus_defaults_when_configuration_missing(): void
     {
         // Arrange: Remove coverage bonus configuration entirely
-        Config::set('fuzzy.scoring.coverage_bonus', null);
+        Config::set('fuzzy.scoring.coverage_bonus');

         // Clear config cache to ensure fresh load
         Config::offsetUnset('fuzzy.scoring.coverage_bonus');
    ----------- end diff -----------

Applied rules:
 * RemoveNullArgOnNullDefaultParamRector


85) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/FacadeTest.php:24

    ---------- begin diff ----------
@@ @@
 final class FacadeTest extends TestCase
 {
     private SearchServiceInterface $searchService;
+
     private StringNormalizer $normalizer;

     /**
@@ @@
         $normalized = $this->normalizer->normalize($inputString);

         // Assert: Verify normalization removes accents and special chars
-        $this->assertEquals($expectedOutput, $normalized);
+        $this->assertSame($expectedOutput, $normalized);
     }

     /**
@@ @@
         $words = $this->normalizer->splitIntoWords($inputString);

         // Assert: Verify correct word splitting
-        $this->assertEquals($expectedWords, $words);
+        $this->assertSame($expectedWords, $words);
     }

     /**
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * AssertEqualsToSameRector


 [OK] 85 files would have been changed (dry-run) by Rector                                                              

