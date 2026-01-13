# Rector Refactoring Report
*Generated: mar. 13 janv. 2026 01:25:02 WAT*


29 files with changes
=====================

1) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/IntegrationTest.php:130

    ---------- begin diff ----------
@@ @@
         // Act: Update model and reindex
         $user1->name = 'Jonathan Smith';
         $user1->save();
+
         $searchService->updateModelIndex($user1);

         // Assert: Verify updated data is searchable
@@ @@
         // Act: Change user to active and reindex
         $user->type = 'active';
         $user->save();
+
         $searchService->indexModel($user);

         // Assert: Verify active user was indexed
@@ @@
      * Check if collection contains result with specific name.
      *
      * @param Collection<array-key, mixed> $results
-     * @param string $searchName
-     * @return bool
      */
     private function containsResultWithName(Collection $results, string $searchName): bool
     {
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * RemoveUselessParamTagRector
 * RemoveUselessReturnTagRector


2) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/ShouldBeIndexedTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Feature;

+use DateTimeInterface;
 use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Tests\Fixtures\User;
@@ @@
         $searchService->indexModel($product);

         $inactiveEntry = $this->getProductEntry($product);
-        $this->assertNull($inactiveEntry);
+        $this->assertNotInstanceOf(FuzzyIndex::class, $inactiveEntry);

         // Test 2: Active product without stock
         FuzzyIndex::query()->truncate();
@@ @@
         $searchService->indexModel($product);

         $outOfStockEntry = $this->getProductEntry($product);
-        $this->assertNull($outOfStockEntry);
+        $this->assertNotInstanceOf(FuzzyIndex::class, $outOfStockEntry);

         // Test 3: Active product with stock
         FuzzyIndex::query()->truncate();
@@ @@
         $searchService->indexModel($product);

         $availableEntry = $this->getProductEntry($product);
-        $this->assertNotNull($availableEntry);
+        $this->assertInstanceOf(FuzzyIndex::class, $availableEntry);
     }

     /**
@@ @@
         $searchService->indexModel($article);

         $draftEntry = $this->getArticleEntry($article);
-        $this->assertNull($draftEntry);
+        $this->assertNotInstanceOf(FuzzyIndex::class, $draftEntry);

         // Test 2: Published article with future date
         FuzzyIndex::query()->truncate();
@@ @@
         $searchService->indexModel($article);

         $futureEntry = $this->getArticleEntry($article);
-        $this->assertNull($futureEntry);
+        $this->assertNotInstanceOf(FuzzyIndex::class, $futureEntry);

         // Test 3: Published article with past date
         FuzzyIndex::query()->truncate();
@@ @@
         $searchService->indexModel($article);

         $publishedEntry = $this->getArticleEntry($article);
-        $this->assertNotNull($publishedEntry);
+        $this->assertInstanceOf(FuzzyIndex::class, $publishedEntry);
     }

     /**
      * Create a user instance with given attributes.
+     * @param array<string, string>|array<string, int> $attributes
      */
     private function createUser(array $attributes): User
     {
@@ @@
             use FuzzySearchable;

             protected $table = 'users';
+
             protected $fillable = ['name', 'email', 'status'];

             /** @var string[] */
@@ @@
             use FuzzySearchable;

             protected $table = 'products';
+
             protected $fillable = ['name', 'price', 'stock', 'is_active'];

             /** @var string[] */
@@ @@
             public array $searchableFields = ['name'];

             public $stock = 0;
+
             public $is_active = false;

             /**
@@ @@
             use FuzzySearchable;

             protected $table = 'products';
+
             protected $fillable = ['name', 'published_at', 'status'];

             /** @var string[] */
@@ @@
             public array $searchableFields = ['name'];

             public $status = 'draft';
+
             public $published_at;

             /**
@@ @@
     /**
      * Set article state for testing.
      */
-    private function setArticleState(Model $article, string $status, \DateTimeInterface $publishedAt): void
+    private function setArticleState(Model $article, string $status, DateTimeInterface $publishedAt): void
     {
         $article->status = $status;
         $article->published_at = $publishedAt;
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * AssertEmptyNullableObjectToAssertInstanceofRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


3) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/Product.php:43

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


4) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/User.php:39

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


5) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/CacheTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit;

+use PHPUnit\Framework\Attributes\CoversMethod;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Services\FuzzySearchService;
 use Fuzzy\Tests\Fixtures\Product;
@@ @@

 /**
  * Unit tests for fuzzy search caching functionality.
- *
- * @covers \Fuzzy\Services\FuzzySearchService::search
- * @covers \Fuzzy\Services\FuzzySearchService::searchInModel
- * @covers \Fuzzy\Services\FuzzySearchService::getStats
- * @covers \Fuzzy\Services\FuzzySearchService::invalidateCacheForModel
- * @covers \Fuzzy\Services\FuzzySearchService::invalidateAllCache
  */
+#[CoversMethod(\Fuzzy\Services\FuzzySearchService::class, 'search')]
+#[CoversMethod(\Fuzzy\Services\FuzzySearchService::class, 'searchInModel')]
+#[CoversMethod(\Fuzzy\Services\FuzzySearchService::class, 'getStats')]
+#[CoversMethod(\Fuzzy\Services\FuzzySearchService::class, 'invalidateCacheForModel')]
+#[CoversMethod(\Fuzzy\Services\FuzzySearchService::class, 'invalidateAllCache')]
 final class CacheTest extends TestCase
 {
     /**
      * Setup test environment.
-     *
-     * @return void
      */
     protected function setUp(): void
     {
@@ @@

     /**
      * Create minimal test data and reindex.
-     *
-     * @return void
      */
     protected function createTestData(): void
     {
@@ @@

     /**
      * Test that search results are properly cached.
-     *
-     * @return void
      */
     public function test_search_results_are_cached(): void
     {
@@ @@

     /**
      * Test that cache invalidation does not affect other application caches.
-     *
-     * @return void
      */
     public function test_cache_does_not_flush_entire_application_cache(): void
     {
@@ @@

     /**
      * Test that cache is invalidated after indexing new data.
-     *
-     * @return void
      */
     public function test_cache_is_invalidated_after_indexing(): void
     {
@@ @@

     /**
      * Test that search works correctly when caching is disabled.
-     *
-     * @return void
      */
     public function test_cache_disabled_works(): void
     {
@@ @@

     /**
      * Test that stats cache has short TTL and expires correctly.
-     *
-     * @return void
      */
     public function test_stats_cache_has_short_ttl(): void
     {
@@ @@

     /**
      * Test that model-specific cache invalidation works correctly.
-     *
-     * @return void
      */
     public function test_model_specific_cache_invalidation(): void
     {
@@ @@

     /**
      * Test that cache can be invalidated for specific models only.
-     *
-     * @return void
      */
     public function test_invalidate_cache_for_specific_model(): void
     {
@@ @@

     /**
      * Test that cache keys are properly managed and cleaned up.
-     *
-     * @return void
      */
     public function test_cache_keys_are_properly_managed(): void
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector
 * CoversAnnotationWithValueToAttributeRector


6) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Models/FuzzyIndexTest.php:350

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


7) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Repositories/IndexRepositoryTest.php:4

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
+     * @param array<string, array<int|string, array<int|string, mixed>>> $indexData
      */
     private function createSearchContext(array $indexData): SearchContext
     {
    ----------- end diff -----------

Applied rules:
 * AnnotationToAttributeRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


8) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/AdvancedScoringCalculatorTest.php:27

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


9) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/IndexBuilderTest.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Tests\Unit\Services;

+use PHPUnit\Framework\Attributes\CoversClass;
 use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Services\IndexBuilder;
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
    ----------- end diff -----------

Applied rules:
 * CoversAnnotationWithValueToAttributeRector


10) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ScoringEngineTest.php:20

    ---------- begin diff ----------
@@ @@
 final class ScoringEngineTest extends TestCase
 {
     private ScoringEngine $scoringEngine;
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
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * DocblockReturnArrayFromDirectArrayInstanceRector


11) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/MatchDiscoveryStageTest.php:301

    ---------- begin diff ----------
@@ @@

     /**
      * Sets a private property value using reflection.
-     *
-     * @param object $object
-     * @param string $propertyName
-     * @param mixed $value
      */
     private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessParamTagRector


12) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/ScoringStageTest.php:17

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


13) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/database/migrations/2024_01_01_000000_create_non_indexable_users_table.php:17

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


14) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/ClearCacheCommand.php:37

    ---------- begin diff ----------
@@ @@
      *
      * Determines the cache clearing strategy based on provided options
      * and delegates to appropriate handler methods.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ @@

     /**
      * Request confirmation from user before clearing cache.
-     *
-     * @return bool
      */
     private function confirmClearCacheRequest(): bool
     {
@@ @@
      * Clear only statistics cache.
      *
      * Invalidates cache containing search statistics and usage metrics.
-     *
-     * @param mixed $searchService
-     * @return void
      */
     private function clearStatisticsCache(mixed $searchService): void
     {
@@ @@
      * Clear cache for a specific model.
      *
      * Invalidates all cached search results for the given model class.
-     *
-     * @param mixed $searchService
-     * @param string $modelClass
-     * @return void
      */
     private function clearCacheForSpecificModel(mixed $searchService, string $modelClass): void
     {
@@ @@
      * Clear entire fuzzy search cache.
      *
      * Invalidates all cached search results and statistics.
-     *
-     * @param mixed $searchService
-     * @return void
      */
     private function clearEntireCache(mixed $searchService): void
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessParamTagRector
 * RemoveUselessReturnTagRector


15) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/ClearIndexCommand.php:36

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


16) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/IndexSearchCommand.php:40

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
      * Index a specific model class.
-     *
-     * @param string $modelClass
-     * @param FuzzySearchService $searchService
-     * @param bool $shouldForceReindex
-     * @param int $chunkSize
-     * @return void
      */
     protected function indexSpecificModel(
         string $modelClass,
@@ @@
         int $chunkSize
     ): void {
         if (!$this->isValidSearchableModel($modelClass)) {
-            $this->error("Model {$modelClass} must implement " . MustFuzzySearch::class);
+            $this->error(sprintf('Model %s must implement ', $modelClass) . MustFuzzySearch::class);
             return;
         }

-        $this->info("Indexing model: {$modelClass}");
+        $this->info('Indexing model: ' . $modelClass);

         if ($shouldForceReindex) {
-            $this->warn("Clearing existing index for {$modelClass}...");
+            $this->warn(sprintf('Clearing existing index for %s...', $modelClass));
             $searchService->reindexModel($modelClass);
         } else {
             $this->performBatchIndexing($modelClass, $searchService, $chunkSize);
@@ @@

     /**
      * Index all searchable models.
-     *
-     * @param FuzzySearchService $searchService
-     * @param bool $shouldForceReindex
-     * @param int $chunkSize
-     * @return void
      */
     protected function indexAllModels(
         FuzzySearchService $searchService,
@@ @@
     ): void {
         $models = $this->getAllSearchableModels();

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

     /**
      * Perform batch indexing for a model.
-     *
-     * @param string $modelClass
-     * @param FuzzySearchService $searchService
-     * @param int $chunkSize
-     * @return void
      */
     private function performBatchIndexing(string $modelClass, FuzzySearchService $searchService, int $chunkSize): void
     {
@@ @@

     /**
      * Display indexing statistics for a specific model.
-     *
-     * @param string $modelClass
-     * @param FuzzySearchService $searchService
-     * @return void
      */
     private function displayModelIndexingStatistics(string $modelClass, FuzzySearchService $searchService): void
     {
         $stats = $this->calculatePreciseModelStatistics($modelClass, $searchService);

-        $this->info("✓ Indexed {$stats['indexed_entries']} entries for {$modelClass}");
+        $this->info(sprintf('✓ Indexed %d entries for %s', $stats['indexed_entries'], $modelClass));

         if ($stats['indexed_models'] > 0) {
             $coveragePercentage = $stats['total_records'] > 0
@@ @@
                 ? round(($stats['indexed_models'] / $stats['total_records']) * 100, 1)
                 : 0;

-            $this->line("  Indexed models: {$stats['indexed_models']} out of {$stats['total_records']} total records ({$coveragePercentage}%)");
+            $this->line(sprintf('  Indexed models: %d out of %d total records (%s%%)', $stats['indexed_models'], $stats['total_records'], $coveragePercentage));

             if ($stats['indexed_models'] < $stats['total_records'] && $stats['skipped_records'] > 0) {
                 $skippedPercentage = round(($stats['skipped_records'] / $stats['total_records']) * 100, 1);
-                $this->line("  Skipped records: {$stats['skipped_records']} ({$skippedPercentage}% - due to shouldBeIndexed())");
+                $this->line(sprintf('  Skipped records: %d (%s%% - due to shouldBeIndexed())', $stats['skipped_records'], $skippedPercentage));
             }
         } else {
             $this->warn("  No models were indexed - check shouldBeIndexed() method");
@@ @@
     /**
      * Calculate precise statistics for model indexing.
      *
-     * @param string $modelClass
-     * @param FuzzySearchService $searchService
      * @return array{
      *     total_records: int,
      *     indexed_models: int,
@@ @@
         $skippedRecords = 0;

         /** @var Model&MustFuzzySearch $modelClass */
-        $modelClass::chunk(1000, function ($models) use (&$totalRecords, &$indexedModels, &$skippedRecords) {
+        $modelClass::chunk(1000, function ($models) use (&$totalRecords, &$indexedModels, &$skippedRecords): void {
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
      * @param array<int, string> $models
-     * @return void
      */
     private function displayModelsForIndexing(array $models): void
     {
@@ @@

         foreach ($models as $model) {
             $source = in_array($model, $configuredModels) ? 'config' : 'auto-discovered';
-            $this->info("  - {$model} ({$source})");
+            $this->info(sprintf('  - %s (%s)', $model, $source));
         }

         $this->newLine();
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
-     * @param FuzzySearchService $searchService
-     * @return void
      */
     private function displayFinalStatistics(FuzzySearchService $searchService): void
     {
@@ @@
         $this->info('Total entries: ' . $stats['total_entries']);

         foreach ($stats['models'] as $model => $modelStats) {
-            $this->info("  {$model}: {$modelStats['count']} entries");
+            $this->info(sprintf('  %s: %s entries', $model, $modelStats['count']));
         }
     }

@@ @@
      * Display models configured in the configuration file.
      *
      * @param array<int, string> $configuredModels
-     * @return void
      */
     private function displayConfigurationModels(array $configuredModels): void
     {
-        if (empty($configuredModels)) {
+        if ($configuredModels === []) {
             $this->warn('No models configured in config/fuzzy.php');
             return;
         }
@@ @@
         foreach ($configuredModels as $model) {
             $classExists = class_exists($model) ? '✓' : '✗';
             $isSearchable = $this->isValidSearchableModel($model) ? '✓' : '✗';
-            $this->info("  {$classExists}{$isSearchable} {$model}");
+            $this->info(sprintf('  %s%s %s', $classExists, $isSearchable, $model));
         }
     }

     /**
      * Display models discovered through auto-discovery.
-     *
-     * @return void
      */
     private function displayAutoDiscoveredModels(): void
     {
@@ @@
         $this->info('Auto-discovered models:');
         $discoveredModels = $this->discoverSearchableModels();

-        if (empty($discoveredModels)) {
+        if ($discoveredModels === []) {
             $this->warn('No models found via auto-discovery');
             return;
         }

         foreach ($discoveredModels as $model) {
-            $this->info("  ✓ {$model}");
+            $this->info('  ✓ ' . $model);
         }
     }

     /**
      * Display summary of valid searchable models.
-     *
-     * @return void
      */
     private function displayValidModelsSummary(): void
     {
@@ @@

         $models = $this->getAllSearchableModels();

-        if (empty($models)) {
+        if ($models === []) {
             $this->error('No valid searchable models found!');
             return;
         }
@@ @@

         foreach ($models as $model) {
             $source = in_array($model, $configuredModels) ? 'config' : 'auto';
-            $this->info("  ✓ {$model} ({$source})");
+            $this->info(sprintf('  ✓ %s (%s)', $model, $source));
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
      * Extract fully qualified class name from a PHP file.
-     *
-     * @param string $filePath
-     * @return string|null
      */
     private function extractClassNameFromFile(string $filePath): ?string
     {
@@ @@

     /**
      * Validate if a class is a searchable model.
-     *
-     * @param string $modelClass
-     * @return bool
      */
     private function isValidSearchableModel(string $modelClass): bool
     {
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * RemoveUselessParamTagRector
 * RemoveUselessReturnTagRector
 * AddClosureVoidReturnTypeWhereNoReturnRector


17) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/StatsIndexCommand.php:33

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
         $this->info('Per model statistics:');
         $this->newLine();

-        if (empty($modelsStats)) {
+        if ($modelsStats === []) {
             $this->warn('No models indexed yet.');
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


18) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/IndexRepositoryInterface.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Contracts;

+use Illuminate\Database\Eloquent\Model;
 use Fuzzy\SearchContext;
 use Illuminate\Support\Collection;

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


19) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Repositories/IndexRepository.php:183

    ---------- begin diff ----------
@@ @@
     /**
      * Create match data structure from index entry.
      *
-     * @param FuzzyIndex $entry
      * @return array<string, mixed>
      */
     private function buildMatchData(FuzzyIndex $entry): array
@@ @@
     /**
      * Update model index with match data.
      *
-     * @param string $modelKey
      * @param array<string, mixed> $matchData
      * @param array<string, array<int, array<string, mixed>>> $modelIndex Reference to model index
      */
@@ @@
      * Cache loaded models in internal map for O(1) access.
      *
      * @param Collection<int, Model> $models
-     * @param string $modelClass
      */
     private function cacheModels(Collection $models, string $modelClass): void
     {
@@ @@
     /**
      * Generate a consistent key for model identification.
      *
-     * @param string $modelClass
      * @param int|string $modelId
-     * @return string
      */
     private function buildModelKey(string $modelClass, $modelId): string
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessParamTagRector
 * RemoveUselessReturnTagRector


20) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/SearchContext.php:101

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
      * Add a potential match before scoring.
      *
      * @param array<string, mixed> $match Raw match data
-     * @return void
      */
     public function addPotentialMatch(array $match): void
     {
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


21) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/LevenshteinSimilarityAlgorithm.php:86

    ---------- begin diff ----------
@@ @@
     private function applyCloseMatchBonus(int $distance, int $maxLength, float $currentSimilarity): float
     {
         if ($distance <= 2 && $maxLength >= 4) {
-            $currentSimilarity = min($currentSimilarity + 0.1, 1.0);
+            return min($currentSimilarity + 0.1, 1.0);
         }

         return $currentSimilarity;
    ----------- end diff -----------

Applied rules:
 * CompleteMissingIfElseBracketRector
 * ReturnEarlyIfVariableRector


22) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/PrefixSimilarityAlgorithm.php:108

    ---------- begin diff ----------
@@ @@

         $baseScore = 0.4;
         $variableScore = $prefixRatio * 0.3;
-        $cappedScore = min(0.6, $baseScore + $variableScore);

-        return $cappedScore;
+        return min(0.6, $baseScore + $variableScore);
     }
 }
    ----------- end diff -----------

Applied rules:
 * SimplifyUselessVariableRector


23) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/FuzzySearchService.php:58

    ---------- begin diff ----------
@@ @@
     {
         return $this->executeWithCache(
             cacheType: 'search',
-            callback: fn() => $this->executeSearch($query, $options),
+            callback: fn(): Collection => $this->executeSearch($query, $options),
             parameters: [$query, $options]
         );
     }
@@ @@
     {
         return $this->executeWithCache(
             cacheType: 'search_in_model',
-            callback: fn() => $this->searchInModelWithoutCache($modelClass, $query, $options),
+            callback: fn(): Collection => $this->searchInModelWithoutCache($modelClass, $query, $options),
             parameters: [$modelClass, $query, $options]
         );
     }
@@ @@
     {
         return $this->executeWithCache(
             cacheType: 'search_in_models',
-            callback: fn() => $this->searchInModelsWithoutCache($modelClasses, $query, $options),
+            callback: fn(): Collection => $this->searchInModelsWithoutCache($modelClasses, $query, $options),
             parameters: [$modelClasses, $query, $options]
         );
     }
@@ @@
      * Index a specific model instance for search.
      *
      * @param MustFuzzySearch $model The model instance to index
-     * @return void
      */
     public function indexModel(MustFuzzySearch $model): void
     {
@@ @@
      * Update the search index for a model instance.
      *
      * @param MustFuzzySearch $model The model instance to update
-     * @return void
      */
     public function updateModelIndex(MustFuzzySearch $model): void
     {
@@ @@
      * Remove a model instance from the search index.
      *
      * @param MustFuzzySearch $model The model instance to remove
-     * @return void
      */
     public function removeModelFromIndex(MustFuzzySearch $model): void
     {
@@ @@

     /**
      * Reindex all searchable models.
-     *
-     * @return void
      */
     public function reindexAll(): void
     {
@@ @@
      * Reindex all instances of a specific model.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
      */
     public function reindexModel(string $modelClass): void
@@ @@
         $totalRecords = 0;
         $indexableRecords = 0;

-        $modelClass::chunk(1000, function ($models) use (&$totalRecords, &$indexableRecords) {
+        $modelClass::chunk(1000, function ($models) use (&$totalRecords, &$indexableRecords): void {
             $totalRecords += count($models);

-            /** @var \Fuzzy\Contracts\MustFuzzySearch $model */
+            /** @var MustFuzzySearch $model */
             foreach ($models as $model) {
                 if ($model->shouldBeIndexed()) {
-                    $indexableRecords++;
+                    ++$indexableRecords;
                 }
             }
         });
@@ @@
     {
         return $this->executeWithCache(
             cacheType: 'stats',
-            callback: fn() => $this->indexRepository->getStats(),
+            callback: fn(): array => $this->indexRepository->getStats(),
             parameters: []
         );
     }
@@ @@

     /**
      * Invalidate all search cache.
-     *
-     * @return void
      */
     public function invalidateAllCache(): void
     {
@@ @@
      * Invalidate cache for a specific model.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      */
     public function invalidateCacheForModel(string $modelClass): void
     {
@@ @@
     {
         return $this->executeWithCache(
             cacheType: 'searchable_models',
-            callback: fn() => $this->fetchSearchableModels(),
+            callback: fn(): array => $this->fetchSearchableModels(),
             parameters: []
         );
     }
@@ @@
      *
      * @param string $modelClass Fully qualified model class name
      * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
-     * @return void
      */
     protected function validateModel(string $modelClass): void
     {
@@ @@
         }

         $cacheKey = $this->generateCacheKey($cacheType, ...$parameters);
-        $ttl = config("fuzzy.cache.ttl.{$cacheType}", 3600);
+        $ttl = config('fuzzy.cache.ttl.' . $cacheType, 3600);

         return $this->cacheRemember($cacheKey, $ttl, $callback);
     }
@@ @@
      * Store a cache key for future invalidation.
      *
      * @param string $key Cache key to store
-     * @return void
      */
     private function storeCacheKey(string $key): void
     {
@@ @@

     /**
      * Delete all stored cache keys.
-     *
-     * @return void
      */
     private function deleteStoredCacheKeys(): void
     {
@@ @@
      * Delete cache keys for a specific model.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      */
     private function deleteCacheKeysForModel(string $modelClass): void
     {
    ----------- end diff -----------

Applied rules:
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * RemoveUselessReturnTagRector
 * AddArrowFunctionReturnTypeRector
 * AddClosureVoidReturnTypeWhereNoReturnRector


24) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/IndexBuilder.php:29

    ---------- begin diff ----------
@@ @@
      * corresponding index entries.
      *
      * @param MustFuzzySearch $model The searchable model instance to index
-     * @return void
      */
     public function indexModel(MustFuzzySearch $model): void
     {
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


25) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/SimilarityCalculator.php:164

    ---------- begin diff ----------
@@ @@
         $firstWords = $this->splitIntoWords($normalizedFirstString);
         $secondWords = $this->splitIntoWords($normalizedSecondString);

-        if (empty($firstWords) || empty($secondWords)) {
+        if ($firstWords === [] || $secondWords === []) {
             return 0.0;
         }
    ----------- end diff -----------

Applied rules:
 * SimplifyEmptyCheckOnEmptyArrayRector


26) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/MatchDiscoveryStage.php:13

    ---------- begin diff ----------
@@ @@
     private const CACHE_TTL = 300;

     private static array $cachedOptimizedIndexes = [];
+
     private static array $cacheTimestamps = [];

     /**
@@ @@
      *
      * @param SearchContext $context Search context
      * @param string $queryWord Normalized query word
-     * @param array $wordIndex Current word index
+     * @param array<string, mixed> $wordIndex Current word index
      */
     private function discoverVeryCloseMatches(SearchContext $context, string $queryWord, array $wordIndex): void
     {
@@ @@
      * Simple fuzzy match discovery for small indexes
      *
      * @param SearchContext $context Search context
-     * @param array $wordIndex Current word index
+     * @param array<string, mixed> $wordIndex Current word index
      */
     private function discoverFuzzyMatchesSimple(SearchContext $context, array $wordIndex): void
     {
@@ @@
      * - byFirstChar: Words grouped by first character
      * - trigramIndex: Words indexed by 3-character sequences
      *
-     * @param array $wordIndex Original word index
+     * @param array<string, mixed> $wordIndex Original word index
      * @return array<string, array> Optimized index structures
      */
     private function getOrBuildOptimizedIndexes(array $wordIndex): array
@@ @@
     /**
      * Build optimized index structures
      *
-     * @param array $wordIndex Original word index
+     * @param array<string, mixed> $wordIndex Original word index
      * @return array<string, array> Optimized index structures
      */
     private function buildOptimizedIndexes(array $wordIndex): array
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

             if ($wordLength >= 3) {
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
 * AddParamArrayDocblockFromDimFetchAccessRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


27) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/ScoringStage.php:4

    ---------- begin diff ----------
@@ @@

 namespace Fuzzy\Stages;

+use InvalidArgumentException;
 use Fuzzy\SearchContext;
 use Fuzzy\Data\SearchResultData;
 use Closure;
@@ @@
     private function extractBestMatchDetails(array $matches): array
     {
         if ($matches === []) {
-            throw new \InvalidArgumentException('Matches array cannot be empty');
+            throw new InvalidArgumentException('Matches array cannot be empty');
         }

         return $matches[0];
    ----------- end diff -----------

Applied rules:


28) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/FuzzySearchable.php:62

    ---------- begin diff ----------
@@ @@
     public function getSearchableFields(): array
     {
         if (property_exists($this, 'searchableFields')) {
-            /** @var array<int, string> */
             return $this->searchableFields;
         }

@@ @@
     public function getFuzzyFormat(): ?string
     {
         if (property_exists($this, 'fuzzyFormat')) {
-            /** @var class-string|null */
             return $this->fuzzyFormat;
         }
    ----------- end diff -----------

Applied rules:
 * RemoveNonExistingVarAnnotationRector


29) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/CommandsTest.php:477

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


 [OK] 29 files would have been changed (dry-run) by Rector                                                              

