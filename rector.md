# Rector Refactoring Report
*Generated: mar. 16 juin 2026 09:25:41 WAT*


94 files with changes
=====================

1) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/WordSimilarityComparatorConfig.php:322

    ---------- begin diff ----------
@@ Line 322 @@
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


2) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/CacheManagerInterface.php:34

    ---------- begin diff ----------
@@ Line 34 @@
      *
      * Clears every cache key that was stored through the cache keys tracking
      * system. This is useful after bulk operations like reindexing all models.
-     *
-     * @return void
      */
     public function invalidateAll(): void;

@@ Line 46 @@
      * results for queries that included this model type.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      */
     public function invalidateForModel(string $modelClass): void;

@@ Line 56 @@
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


3) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/ConfigInterface.php:15

    ---------- begin diff ----------
@@ Line 15 @@
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


4) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/IndexManagerInterface.php:23

    ---------- begin diff ----------
@@ Line 23 @@
      * Should respect the model's `shouldBeIndexed()` method.
      *
      * @param MustFuzzySearch $model The model instance to index
-     * @return void
      */
     public function indexModel(MustFuzzySearch $model): void;

@@ Line 34 @@
      * with current data. Useful after model updates.
      *
      * @param MustFuzzySearch $model The model instance to update in the index
-     * @return void
      */
     public function updateModelIndex(MustFuzzySearch $model): void;

@@ Line 45 @@
      * Called automatically when a model is deleted.
      *
      * @param MustFuzzySearch $model The model instance to remove from the index
-     * @return void
      */
     public function removeModel(MustFuzzySearch $model): void;

@@ Line 54 @@
      *
      * Truncates the entire index and rebuilds it from scratch
      * for all models that implement MustFuzzySearch.
-     *
-     * @return void
      */
     public function reindexAll(): void;

@@ Line 66 @@
      * by iterating through all instances of that model.
      *
      * @param string $modelClass Fully qualified model class name
-     * @return void
      */
     public function reindexModel(string $modelClass): void;
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


5) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/IndexRepositoryInterface.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Contracts;

+use Illuminate\Database\Eloquent\Model;
 use Illuminate\Support\Collection;

 /**
@@ Line 33 @@
      *
      * @param string $modelClass Fully qualified model class name
      * @param array<int> $ids Array of model IDs to retrieve
-     * @return Collection<int, \Illuminate\Database\Eloquent\Model> Collection of retrieved models
+     * @return Collection<int, Model> Collection of retrieved models
      */
     public function getModelsBatch(string $modelClass, array $ids): Collection;

@@ Line 43 @@
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


6) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/ModelDiscoveryInterface.php:46

    ---------- begin diff ----------
@@ Line 46 @@
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


7) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/SearchContextInterface.php:120

    ---------- begin diff ----------
@@ Line 120 @@
      * before being processed by the ScoringStage.
      *
      * @param array<string, mixed> $match Raw match data containing index entry information
-     * @return void
      */
     public function addPotentialMatch(array $match): void;
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector


8) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Contracts/SearchServiceInterface.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Contracts;

+use Fuzzy\Data\SearchResultData;
 use Illuminate\Support\Collection;

 /**
@@ Line 23 @@
      * Get the cache manager instance.
      *
      * Provides access to cache operations for advanced cache management.
-     *
-     * @return CacheManagerInterface
      */
     public function getCacheManager(): CacheManagerInterface;

@@ Line 32 @@
      * Get the model discovery instance.
      *
      * Provides access to model discovery for advanced model operations.
-     *
-     * @return ModelDiscoveryInterface
      */
     public function getModelDiscovery(): ModelDiscoveryInterface;

@@ Line 41 @@
      * Get the index manager instance.
      *
      * Provides access to index operations for advanced index management.
-     *
-     * @return IndexManagerInterface
      */
     public function getIndexManager(): IndexManagerInterface;

@@ Line 50 @@
      * Get the search processor instance.
      *
      * Provides access to search processor for advanced search operations.
-     *
-     * @return SearchProcessorInterface
      */
     public function getSearchProcessor(): SearchProcessorInterface;

@@ Line 60 @@
      *
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     public function search(string $query, array $options = []): Collection;

@@ Line 70 @@
      * @param string $modelClass The fully qualified model class name
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     public function searchInModel(string $modelClass, string $query, array $options = []): Collection;

@@ Line 80 @@
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


9) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/CreateOnlyUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

-class CreateOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class CreateOnlyUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'create_only_users';
    ----------- end diff -----------

Applied rules:


10) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/CustomStage.php:76

    ---------- begin diff ----------
@@ Line 76 @@
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


11) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/CustomStage2.php:28

    ---------- begin diff ----------
@@ Line 28 @@

     public function handle(SearchContextInterface $context, Closure $next): mixed
     {
-        if (!isset($context->processedStages)) {
+        if (!property_exists($context, 'processedStages') || $context->processedStages === null) {
             $context->processedStages = [];
         }
    ----------- end diff -----------

Applied rules:
 * IssetOnPropertyObjectToPropertyExistsRector


12) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/DeleteOnlyUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

-class DeleteOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class DeleteOnlyUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'delete_only_users';
    ----------- end diff -----------

Applied rules:


13) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/NonIndexableCreateOnlyUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

@@ Line 14 @@
  * but shouldBeIndexed() returns false to prevent any indexing.
  * Used to test the priority relationship between IndexationLevel and shouldBeIndexed().
  */
-class NonIndexableCreateOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class NonIndexableCreateOnlyUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'non_indexable_create_only_users';
    ----------- end diff -----------

Applied rules:


14) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/NonIndexableUpdateOnlyUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

@@ Line 14 @@
  * but shouldBeIndexed() returns false to prevent any indexing.
  * Used to test the priority relationship between IndexationLevel and shouldBeIndexed().
  */
-class NonIndexableUpdateOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class NonIndexableUpdateOnlyUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'non_indexable_update_only_users';
    ----------- end diff -----------

Applied rules:


15) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/NonSearchableModel.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

-use Fuzzy\Data\FuzzySearchableData;
 use Illuminate\Database\Eloquent\Model;

 /**
    ----------- end diff -----------

Applied rules:


16) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/NoneUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

-class NoneUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class NoneUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'none_users';
    ----------- end diff -----------

Applied rules:


17) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/Product.php:46

    ---------- begin diff ----------
@@ Line 46 @@

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


18) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/UpdateOnlyUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

-class UpdateOnlyUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class UpdateOnlyUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'update_only_users';
    ----------- end diff -----------

Applied rules:


19) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/User.php:53

    ---------- begin diff ----------
@@ Line 53 @@

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


20) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/UserSearchData.php:19

    ---------- begin diff ----------
@@ Line 19 @@
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


21) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/TestCase.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests;

-use Fuzzy\Tests\Fixtures\User;
-use Fuzzy\Tests\Fixtures\Product;
 use Illuminate\Foundation\Application;
 use Illuminate\Support\Facades\Config;
 use Orchestra\Testbench\TestCase as Orchestra;
    ----------- end diff -----------

Applied rules:


22) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Cache/LaravelCacheStoreTest.php:113

    ---------- begin diff ----------
@@ Line 113 @@
         $executed = false;

         // Act: Remember value (cache miss)
-        $value = $this->cacheStore->remember('miss_key', 60, function () use (&$executed) {
+        $value = $this->cacheStore->remember('miss_key', 60, function () use (&$executed): string {
             $executed = true;
             return 'computed_value';
         });
@@ Line 135 @@
         $executed = false;

         // Act: Remember value (cache hit)
-        $value = $this->cacheStore->remember('hit_key', 60, function () use (&$executed) {
+        $value = $this->cacheStore->remember('hit_key', 60, function () use (&$executed): string {
             $executed = true;
             return 'computed_value';
         });
@@ Line 184 @@
         $newValue = $this->cacheStore->increment('counter', 5);

         // Assert: Should return new value
-        $this->assertEquals(15, $newValue);
+        $this->assertSame(15, $newValue);
         $this->assertEquals(15, $this->cacheStore->get('counter'));
     }

@@ Line 197 @@
         $newValue = $this->cacheStore->increment('new_counter', 5);

         // Assert: Should create and increment from 0
-        $this->assertEquals(5, $newValue);
+        $this->assertSame(5, $newValue);
         $this->assertEquals(5, $this->cacheStore->get('new_counter'));
     }

@@ Line 213 @@
         $newValue = $this->cacheStore->decrement('counter', 7);

         // Assert: Should return new value
-        $this->assertEquals(13, $newValue);
+        $this->assertSame(13, $newValue);
         $this->assertEquals(13, $this->cacheStore->get('counter'));
     }

@@ Line 226 @@
         $newValue = $this->cacheStore->decrement('new_counter', 5);

         // Assert: Should create and decrement from 0
-        $this->assertEquals(-5, $newValue);
+        $this->assertSame(-5, $newValue);
         $this->assertEquals(-5, $this->cacheStore->get('new_counter'));
     }

@@ Line 276 @@
     public function test_remember_with_zero_ttl_works(): void
     {
         // Act: Remember with TTL 0
-        $value = $this->cacheStore->remember('zero_ttl_key', 0, function () {
+        $value = $this->cacheStore->remember('zero_ttl_key', 0, function (): string {
             return 'zero_ttl_value';
         });
    ----------- end diff -----------

Applied rules:
 * AssertEqualsToSameRector
 * ClosureReturnTypeRector


23) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/ClearCacheCommandTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Commands;

-use Fuzzy\Commands\ClearCacheCommand;
+use Fuzzy\Tests\Fixtures\User;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\SearchServiceInterface;
 use Fuzzy\Tests\TestCase;
@@ Line 13 @@

 final class ClearCacheCommandTest extends TestCase
 {
-    private $searchService;
     private $cacheManager;

     protected function setUp(): void
@@ Line 21 @@
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
@@ Line 76 @@

     public function test_clear_cache_for_specific_model(): void
     {
-        $modelClass = 'Fuzzy\\Tests\\Fixtures\\User';
+        $modelClass = User::class;

         $this->cacheManager->shouldReceive('invalidateForModel')
             ->once()
@@ Line 90 @@
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


24) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/database/migrations/2024_01_01_000000_create_non_indexable_users_table.php:17

    ---------- begin diff ----------
@@ Line 17 @@
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


25) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/database/migrations/2024_01_01_000000_create_non_searchable_models_table.php:17

    ---------- begin diff ----------
@@ Line 17 @@
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


26) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/FuzzySearch.php:6

    ---------- begin diff ----------
@@ Line 6 @@

 use Illuminate\Support\Facades\Facade;
 use Illuminate\Support\Collection;
-use Fuzzy\Contracts\MustFuzzySearch;

 /**
  * Facade for the fuzzy search service
    ----------- end diff -----------

Applied rules:


27) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/FuzzySearchServiceProvider.php:4

    ---------- begin diff ----------
@@ Line 4 @@

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

@@ Line 59 @@
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


28) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Repositories/IndexRepository.php:98

    ---------- begin diff ----------
@@ Line 98 @@

     /**
      * {@inheritDoc}
+     * @return array<string, Model>
      */
     public function getPreloadedModelsMap(): array
     {
    ----------- end diff -----------

Applied rules:
 * DocblockGetterReturnArrayFromPropertyDocblockVarRector


29) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/ScoringStage.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Stages;

+use InvalidArgumentException;
 use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Contracts\StageInterface;
 use Fuzzy\Enums\StageType;
@@ Line 108 @@
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


30) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/CommandHelpers.php:20

    ---------- begin diff ----------
@@ Line 20 @@
 {
     /**
      * Get the fuzzy search service from the container.
-     *
-     * @return FuzzySearchService
      */
     protected function getSearchService(): FuzzySearchService
     {
@@ Line 66 @@
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
@@ Line 77 @@
      * Display a warning message.
      *
      * @param string $message The warning message to display
-     * @return void
      */
     protected function showWarning(string $message): void
     {
@@ Line 88 @@
      * Display an error message.
      *
      * @param string $message The error message to display
-     * @return void
      */
     protected function showError(string $message): void
     {
@@ Line 99 @@
      * Display an info message.
      *
      * @param string $message The info message to display
-     * @return void
      */
     protected function showInfo(string $message): void
     {
@@ Line 110 @@
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


31) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/FuzzySearchable.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Traits;

+use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Support\Collection;
 use Fuzzy\Services\FuzzySearchService;
@@ Line 29 @@
      * Registers model event listeners to automatically manage search index
      * during create, update, and delete operations. The events that are
      * actually registered depend on the model's getIndexationLevel() method.
-     *
-     * @return void
      */
     protected static function bootFuzzySearchable(): void
     {
@@ Line 53 @@
         }

         if ($indexationLevel->hasEvent('delete')) {
-            static::deleted(static function ($model): void {
+            static::deleted(static function (MustFuzzySearch $model): void {
                 app(FuzzySearchService::class)->getIndexManager()->removeModel($model);
             });
         }
    ----------- end diff -----------

Applied rules:
 * RemoveUselessReturnTagRector
 * ParamTypeByMethodCallTypeRector


32) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Traits/ServiceProviderHelper.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Traits;

+use ReflectionClass;
 use Illuminate\Support\ServiceProvider;

 /**
@@ Line 19 @@
      */
     protected function mergeConfigFrom(string $path, string $key): void
     {
-        $reflection = new \ReflectionClass($this->provider);
+        $reflection = new ReflectionClass($this->provider);
         $method = $reflection->getMethod('mergeConfigFrom');
         $method->setAccessible(true);
         $method->invoke($this->provider, $path, $key);
@@ Line 30 @@
      */
     protected function publishes(array $paths, string $group = null): void
     {
-        $reflection = new \ReflectionClass($this->provider);
+        $reflection = new ReflectionClass($this->provider);
         $method = $reflection->getMethod('publishes');
         $method->setAccessible(true);

@@ Line 46 @@
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


33) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/ConfigurationTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Feature;

+use ReflectionClass;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
 use Fuzzy\Contracts\SearchServiceInterface;
 use Fuzzy\Services\IndexBuilder;
@@ Line 114 @@
      */
     private function getFieldWeightFromIndexBuilder(IndexBuilder $indexBuilder, string $field): float
     {
-        $reflection = new \ReflectionClass($indexBuilder);
+        $reflection = new ReflectionClass($indexBuilder);
         $calculateWeightMethod = $reflection->getMethod('calculateFieldWeight');
         $calculateWeightMethod->setAccessible(true);
    ----------- end diff -----------

Applied rules:


34) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/FacadeTest.php:24

    ---------- begin diff ----------
@@ Line 24 @@
 final class FacadeTest extends TestCase
 {
     private SearchServiceInterface $searchService;
+
     private StringNormalizer $normalizer;

     /**
@@ Line 330 @@
         $normalized = $this->normalizer->normalize($inputString);

         // Assert: Verify normalization removes accents and special chars
-        $this->assertEquals($expectedOutput, $normalized);
+        $this->assertSame($expectedOutput, $normalized);
     }

     /**
@@ Line 347 @@
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


35) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/IntegrationTest.php:13

    ---------- begin diff ----------
@@ Line 13 @@
 use Fuzzy\Services\FuzzySearchService;
 use Illuminate\Support\Collection;
 use Fuzzy\Data\SearchResultData;
-use Illuminate\Support\Facades\Cache;

 /**
  * Integration tests for the complete fuzzy search system.
@@ Line 25 @@
 {
     /**
      * Set up test environment.
-     *
-     * @return void
      */
     protected function setUp(): void
     {
@@ Line 50 @@
      * - Searches return expected results
      * - Cache invalidation works correctly
      * - Statistics are accurate
-     *
-     * @return void
      */
     public function test_complete_search_workflow(): void
     {
@@ Line 68 @@
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
@@ Line 122 @@
         $exactMatch = $exactResults->first(function ($result): bool {
             return $result->item->name === 'John Smith';
         });
-        $this->assertNotNull($exactMatch, 'Should find John Smith in exact search');
+        $this->assertInstanceOf(SearchResultData::class, $exactMatch, 'Should find John Smith in exact search');
         $this->assertGreaterThan(0.9, $exactMatch->score, 'Exact match should have high score');

         // === ACT: Multi-word search ===
@@ Line 180 @@
      * Test automatic indexing via FuzzySearchable trait.
      *
      * Verifies that model events automatically trigger index updates.
-     *
-     * @return void
      */
     public function test_model_auto_indexing_via_trait(): void
     {
@@ Line 235 @@
      * Test custom shouldBeIndexed logic.
      *
      * Verifies that the shouldBeIndexed method controls which models are indexed.
-     *
-     * @return void
      */
     public function test_should_be_indexed_logic(): void
     {
@@ Line 276 @@
      * Test custom formatting in search results.
      *
      * Verifies that custom formatters are applied to search results.
-     *
-     * @return void
      */
     public function test_custom_formatting(): void
     {
@@ Line 305 @@
      * Test performance with large datasets.
      *
      * Verifies that indexing and search operations perform within acceptable limits.
-     *
-     * @return void
      */
     public function test_performance_with_large_dataset(): void
     {
@@ Line 351 @@
      * Test cache integration.
      *
      * Verifies that caching works correctly and is properly invalidated.
-     *
-     * @return void
      */
     public function test_cache_integration(): void
     {
@@ Line 400 @@
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


36) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Feature/ShouldBeIndexedTest.php:443

    ---------- begin diff ----------
@@ Line 443 @@
     public function test_should_be_indexed_has_higher_priority_than_indexation_level(): void
     {
         // Scenario 1: ALL level + shouldBeIndexed() = false -> NO indexing
-        $adminUser = NonIndexableUser::create([
+        NonIndexableUser::create([
             'name' => 'Admin',
             'email' => 'admin@example.com',
             'type' => 'admin',
@@ Line 461 @@
         $this->assertEquals(2, FuzzyIndex::where('indexable_type', NoneUser::class)->count());

         // Scenario 3: CREATE_ONLY level + shouldBeIndexed() = false -> NO auto-indexing
-        $createOnlyAdmin = NonIndexableCreateOnlyUser::create([
+        NonIndexableCreateOnlyUser::create([
             'name' => 'Create Only Admin',
             'email' => 'createonlyadmin@example.com',
             'type' => 'admin',
    ----------- end diff -----------

Applied rules:
 * RemoveUnusedVariableAssignRector


37) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Fixtures/CreateAndUpdateUser.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Fixtures;

+use Fuzzy\Contracts\MustFuzzySearch;
+use Fuzzy\Traits\FuzzySearchable;
 use Fuzzy\Enums\IndexationLevel;
 use Illuminate\Database\Eloquent\Model;

-class CreateAndUpdateUser extends Model implements \Fuzzy\Contracts\MustFuzzySearch
+class CreateAndUpdateUser extends Model implements MustFuzzySearch
 {
-    use \Fuzzy\Traits\FuzzySearchable;
+    use FuzzySearchable;

     protected $table = 'create_and_update_users';
    ----------- end diff -----------

Applied rules:


38) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/ClearCacheCommand.php:39

    ---------- begin diff ----------
@@ Line 39 @@

     /**
      * Execute the console command.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ Line 64 @@

     /**
      * Clear only statistics cache.
-     *
-     * @return void
      */
     private function clearStatisticsCache(): void
     {
@@ Line 77 @@
      * Clear cache for a specific model.
      *
      * @param string $modelClass The model class to clear cache for
-     * @return void
      */
     private function clearCacheForSpecificModel(string $modelClass): void
     {
@@ Line 87 @@

     /**
      * Clear entire fuzzy search cache.
-     *
-     * @return void
      */
     private function clearEntireCache(): void
     {
@@ Line 98 @@

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


39) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/ClearIndexCommand.php:41

    ---------- begin diff ----------
@@ Line 41 @@
      *
      * Routes to either clear a specific model's index or all indexes
      * based on provided arguments.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ Line 61 @@
      *
      * @param string $modelClass The fully qualified model class name
      * @param bool $shouldSkipConfirmation Whether to bypass user confirmation
-     * @return void
      */
     protected function clearModelIndex(string $modelClass, bool $shouldSkipConfirmation): void
     {
@@ Line 78 @@
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


40) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/IndexSearchCommand.php:23

    ---------- begin diff ----------
@@ Line 23 @@
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
@@ Line 99 @@
         $modelDiscovery = $this->getModelDiscovery();

         if (!$modelDiscovery->isValidModel($modelClass)) {
-            $this->showError("Model {$modelClass} must implement " . MustFuzzySearch::class);
+            $this->showError(sprintf('Model %s must implement ', $modelClass) . MustFuzzySearch::class);
             return;
         }

-        $this->showInfo("Processing model: {$modelClass}");
+        $this->showInfo('Processing model: ' . $modelClass);

         if ($shouldForceReindex) {
-            $this->showWarning("Clearing existing index for {$modelClass}...");
+            $this->showWarning(sprintf('Clearing existing index for %s...', $modelClass));
             $this->getSearchService()->getIndexManager()->reindexModel($modelClass);
         } else {
             $this->performIncrementalIndexing($modelClass, $chunkSize);
@@ Line 128 @@
         $modelDiscovery = $this->getModelDiscovery();
         $models = $modelDiscovery->getSearchableModels();

-        if (empty($models)) {
+        if ($models === []) {
             $this->displayNoModelsWarning();
             return;
         }
@@ Line 158 @@
      */
     private function performIncrementalIndexing(string $modelClass, int $chunkSize): void
     {
-        $this->showInfo("Indexing model: {$modelClass}");
+        $this->showInfo('Indexing model: ' . $modelClass);

         /** @var Model&MustFuzzySearch $modelClass */
         $totalRecords = $modelClass::count();

         if ($totalRecords === 0) {
-            $this->showWarning("No records found for {$modelClass}");
+            $this->showWarning('No records found for ' . $modelClass);
             return;
         }

@@ Line 177 @@
                 if (get_class($model) === $modelClass && $model->shouldBeIndexed()) {
                     $this->getSearchService()->getIndexManager()->indexModel($model);
                 }
+
                 $progressBar->advance();
             }
         });
@@ Line 196 @@
         $this->showHeader('Discoverable Models');
         $this->showInfo('Models that implement ' . MustFuzzySearch::class . ':');

-        if (empty($models)) {
+        if ($models === []) {
             $this->showWarning('No discoverable models found.');
         } else {
             foreach ($models as $model) {
-                $this->line("  ✓ {$model}");
+                $this->line('  ✓ ' . $model);
             }
         }

@@ Line 216 @@
     {
         $stats = $this->calculatePreciseModelStatistics($modelClass);

-        $this->showSuccess("Indexed {$stats['indexed_entries']} entries for {$modelClass}");
+        $this->showSuccess(sprintf('Indexed %d entries for %s', $stats['indexed_entries'], $modelClass));

         if ($stats['indexed_models'] > 0) {
             $coveragePercentage = $stats['total_records'] > 0
@@ Line 223 @@
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
@@ Line 261 @@
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
@@ Line 300 @@
         $this->showInfo('Found ' . count($models) . ' searchable model(s):');

         foreach ($models as $model) {
-            $this->line("  - {$model}");
+            $this->line('  - ' . $model);
         }

         $this->showNewLine();
@@ Line 328 @@
         $this->showInfo('Total entries: ' . $stats['total_entries']);

         foreach ($stats['models'] as $model => $modelStats) {
-            $this->line("  {$model}: {$modelStats['count']} entries");
+            $this->line(sprintf('  %s: %s entries', $model, $modelStats['count']));
         }
     }

@@ Line 347 @@

     /**
      * Get the search service instance from the container.
-     *
-     * @return SearchServiceInterface
      */
     private function getSearchService(): SearchServiceInterface
     {
@@ Line 357 @@

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
 * NewlineAfterStatementRector
 * RemoveUnusedPrivateClassConstantRector
 * RemoveUselessReturnTagRector
 * AddClosureVoidReturnTypeWhereNoReturnRector


41) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Commands/StatsIndexCommand.php:39

    ---------- begin diff ----------
@@ Line 39 @@
      *
      * Retrieves and displays comprehensive statistics about the search index,
      * including total entries and per-model breakdown with field counts.
-     *
-     * @return void
      */
     public function handle(): void
     {
@@ Line 54 @@

     /**
      * Get the search service from the container.
-     *
-     * @return SearchServiceInterface
      */
     private function getSearchService(): SearchServiceInterface
     {
@@ Line 64 @@

     /**
      * Display the command header.
-     *
-     * @return void
      */
     private function displayHeader(): void
     {
@@ Line 76 @@
      * Display the total number of indexed entries.
      *
      * @param int $totalEntries The total number of indexed entries
-     * @return void
      */
     private function displayTotalEntries(int $totalEntries): void
     {
@@ Line 90 @@
      * Shows entry counts and field distributions per model in a tabular format.
      *
      * @param array<string, array{count: int, fields: array<string, int>}> $modelsStats
-     * @return void
      */
     private function displayModelStatistics(array $modelsStats): void
     {
@@ Line 97 @@
         $this->showInfo('Per model statistics:');
         $this->showNewLine();

-        if (empty($modelsStats)) {
+        if ($modelsStats === []) {
             $this->showWarning('No models indexed yet.');
             return;
         }
@@ Line 138 @@
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

@@ Line 149 @@
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


42) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/CoverageBonusConfig.php:57

    ---------- begin diff ----------
@@ Line 57 @@

     /**
      * Get the full coverage threshold value.
-     *
-     * @return float
      */
     public function getFullCoverageThreshold(): float
     {
@@ Line 67 @@

     /**
      * Get the high coverage threshold value.
-     *
-     * @return float
      */
     public function getHighCoverageThreshold(): float
     {
@@ Line 77 @@

     /**
      * Get the full coverage bonus value.
-     *
-     * @return float
      */
     public function getFullCoverageBonus(): float
     {
@@ Line 87 @@

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


43) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/LevenshteinAlgorithmConfig.php:36

    ---------- begin diff ----------
@@ Line 36 @@
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


44) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/LongestCommonSubstringConfig.php:24

    ---------- begin diff ----------
@@ Line 24 @@
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


45) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/PrefixAlgorithmConfig.php:27

    ---------- begin diff ----------
@@ Line 27 @@
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


46) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Config/SimilarityCalculatorConfig.php:143

    ---------- begin diff ----------
@@ Line 143 @@
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


47) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/SearchContext.php:102

    ---------- begin diff ----------
@@ Line 102 @@

     /**
      * Preload all required models for efficient access.
-     *
-     * @return void
      */
     private function preloadModels(): void
     {
@@ Line 232 @@

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


48) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/LevenshteinSimilarityAlgorithm.php:138

    ---------- begin diff ----------
@@ Line 138 @@
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


49) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarity/LetterDistanceCalculator.php:71

    ---------- begin diff ----------
@@ Line 71 @@

     /**
      * Find matching letters between two sets with position windows.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function findLetterMatches(array $lettersA, array $lettersB): array
     {
@@ Line 98 @@

     /**
      * Find the best matching letter in the target string.
+     * @param array<int, mixed> $searchLetters
      */
     private function findBestLetterMatch(
         string $targetLetter,
@@ Line 114 @@
         $startSearch = max($startIndex, $currentPosition - $searchWindow);
         $endSearch = min(count($searchLetters), $currentPosition + $searchWindow + $baseIncrement);

-        for ($searchPosition = $startSearch; $searchPosition < $endSearch; $searchPosition++) {
+        for ($searchPosition = $startSearch; $searchPosition < $endSearch; ++$searchPosition) {
             if (in_array($searchPosition, $usedPositions, true)) {
                 continue;
             }
@@ Line 138 @@

     /**
      * Calculate total distance from matched letter pairs.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function calculateTotalMatchedDistance(array $matchedPairs, array $lettersA, array $lettersB): float
     {
@@ Line 164 @@
             );

             if (!$pair['isExact']) {
-                $imperfectMatchCount++;
+                ++$imperfectMatchCount;
             }
         }

@@ Line 174 @@
         $unmatchedMultiplier = $this->config->getUnmatchedLetterMultiplier();
         $totalDistance += ($unmatchedCountA + $unmatchedCountB) * $unmatchedPenaltyBase * $unmatchedMultiplier;

-        $totalDistance += $imperfectMatchCount * $this->config->getImperfectMatchPenalty();
-
-        return $totalDistance;
+        return $totalDistance + $imperfectMatchCount * $this->config->getImperfectMatchPenalty();
     }

     /**
@@ Line 261 @@

     /**
      * Count common letters between two letter sets.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function countCommonLetters(array $lettersA, array $lettersB): int
     {
@@ Line 269 @@

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


50) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarity/WordMatchScorer.php:12

    ---------- begin diff ----------
@@ Line 12 @@
 class WordMatchScorer
 {
     private WordSimilarityComparatorConfig $config;
+
     private WordSimilarityCalculator $wordSimilarityCalculator;

     public function __construct(WordSimilarityComparatorConfig $config)
@@ Line 33 @@
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

@@ Line 187 @@

         foreach ($scores as $score) {
             if ($score > $threshold) {
-                $badMatchCount++;
+                ++$badMatchCount;
             }
         }

@@ Line 194 @@
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


51) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarity/WordSimilarityCalculator.php:12

    ---------- begin diff ----------
@@ Line 12 @@
 class WordSimilarityCalculator
 {
     private WordSimilarityComparatorConfig $config;
+
     private LetterDistanceCalculator $letterDistanceCalculator;

     public function __construct(WordSimilarityComparatorConfig $config)
@@ Line 167 @@

     /**
      * Count common letters between two letter sets.
+     * @param string[] $lettersA
+     * @param string[] $lettersB
      */
     private function countCommonLetters(array $lettersA, array $lettersB): int
     {
@@ Line 175 @@

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


52) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Algorithms/WordSimilarityComparator.php:7

    ---------- begin diff ----------
@@ Line 7 @@
 use Fuzzy\Contracts\StringNormalizerInterface;
 use Fuzzy\Config\WordSimilarityComparatorConfig;
 use Fuzzy\Services\Algorithms\WordSimilarity\WordMatchScorer;
-use Fuzzy\Services\Algorithms\WordSimilarity\LetterDistanceCalculator;

 /**
  * Advanced lexical similarity comparator for strings.
@@ Line 27 @@
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
@@ Line 44 @@
         $this->normalizer = $normalizer;
         $this->config = $config ?? WordSimilarityComparatorConfig::createDefault();
         $this->wordMatchScorer = new WordMatchScorer($this->config);
-        $this->letterDistanceCalculator = new LetterDistanceCalculator($this->config);
     }

     /**
@@ Line 73 @@
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
@@ Line 85 @@

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


53) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/CacheManagerService.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Services;

+use Carbon\Carbon;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\CacheStoreInterface;
 use Fuzzy\Config\CacheConfig;
@@ Line 11 @@
 class CacheManagerService implements CacheManagerInterface
 {
     private const MIN_CACHE_KEY_LENGTH_FOR_HASH = 250;
+
     private const STATS_CACHE_TYPE = 'stats';

     private CacheConfig $config;
+
     private CacheStoreInterface $cache;

     public function __construct(CacheStoreInterface $cache, ?CacheConfig $config = null)
@@ Line 94 @@
         return $key;
     }

+    /**
+     * @param array<int, mixed> $parameters
+     */
     private function extractModelClassFromParameters(array $parameters): ?string
     {
         if (isset($parameters[0]) && is_string($parameters[0]) && class_exists($parameters[0])) {
@@ Line 100 @@
             return $parameters[0];
         }

-        if (isset($parameters[0]) && is_array($parameters[0])) {
-            return null;
-        }
-
         return null;
     }

@@ Line 120 @@

         $keyData = [
             'key' => $key,
-            'created_at' => time(),
+            'created_at' => Carbon::now()
+                ->getTimestamp(),
         ];

         if ($modelClass !== null) {
@@ Line 133 @@
                 $keyExists = true;
                 break;
             }
+
             if (is_string($existingKeyData) && $existingKeyData === $key) {
                 $keyExists = true;
                 break;
@@ Line 200 @@
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
 * RemoveDeadConditionAboveReturnRector
 * AddParamArrayDocblockFromDimFetchAccessRector


54) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/FuzzySearchService.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Services;

+use Fuzzy\Data\SearchResultData;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\IndexManagerInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
@@ Line 76 @@
     {
         return $this->cacheManager->remember(
             type: 'search',
-            callback: fn() => $this->executeSearch($query, $options),
+            callback: fn(): Collection => $this->executeSearch($query, $options),
             parameters: [$query, $options]
         );
     }
@@ Line 86 @@
      *
      * @param string $query The search query string
      * @param array<string, mixed> $options Search options
-     * @return Collection<int, \Fuzzy\Data\SearchResultData> Collection of search results
+     * @return Collection<int, SearchResultData> Collection of search results
      */
     private function executeSearch(string $query, array $options = []): Collection
     {
@@ Line 101 @@
     {
         return $this->cacheManager->remember(
             type: 'search_in_model',
-            callback: fn() => $this->searchProcessor->searchInModel($modelClass, $query, $options),
+            callback: fn(): Collection => $this->searchProcessor->searchInModel($modelClass, $query, $options),
             parameters: [$modelClass, $query, $options]
         );
     }
@@ Line 113 @@
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


55) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/IndexBuilder.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Services;

+use ArrayAccess;
 use Fuzzy\Contracts\ContextualNormalizerInterface;
 use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Models\FuzzyIndex;
@@ Line 43 @@
      * from the model to preserve stop words where appropriate.
      *
      * @param MustFuzzySearch $model The searchable model instance to index
-     * @return void
      */
     public function indexModel(MustFuzzySearch $model): void
     {
@@ Line 86 @@
     {
         // Try Eloquent's getAttribute method
         if (method_exists($model, 'getAttribute')) {
-            /** @var \Illuminate\Database\Eloquent\Model $model */
+            /** @var Model $model */
             return $model->getAttribute($field);
         }

@@ Line 109 @@
         }

         // Try array access if model implements ArrayAccess
-        if ($model instanceof \ArrayAccess && isset($model[$field])) {
+        if ($model instanceof ArrayAccess && isset($model[$field])) {
             return $model[$field];
         }

@@ Line 127 @@
      * @param mixed $modelId The model's primary key value
      * @param string $field The field name being indexed
      * @param string $value The field value to index
-     * @return void
      */
     public function indexField(string $modelType, mixed $modelId, string $field, string $value): void
     {
@@ Line 140 @@

         $words = $this->normalizer->splitIntoWords($normalizedValue);

-        if (empty($words)) {
+        if ($words === []) {
             return;
         }

@@ Line 190 @@
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


56) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/IndexManagerService.php:303

    ---------- begin diff ----------
@@ Line 303 @@

             foreach ($models as $model) {
                 if ($model instanceof MustFuzzySearch && $model->shouldBeIndexed()) {
-                    $indexableRecords++;
+                    ++$indexableRecords;
                 }
             }
         });
    ----------- end diff -----------

Applied rules:
 * PostIncDecToPreIncDecRector


57) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/ModelDiscoveryService.php:21

    ---------- begin diff ----------
@@ Line 21 @@
 class ModelDiscoveryService implements ModelDiscoveryInterface
 {
     private const EXTRACT_NAMESPACE_REGEX = '/namespace\s+(.+?);/s';
+
     private const EXTRACT_CLASS_REGEX = '/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/';

     /**
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector


58) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/PipelineStageManager.php:40

    ---------- begin diff ----------
@@ Line 40 @@
         $customStages = config('fuzzy.pipeline', []);

         // Validate each custom stage
-        foreach ($customStages as $index => $stage) {
+        foreach ($customStages as $stage) {
             $this->validateStage($stage);
         }

@@ Line 99 @@
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


59) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/Scoring/ScoringEngine.php:113

    ---------- begin diff ----------
@@ Line 113 @@

     /**
      * Check if the score has reached the maximum possible value.
-     *
-     * @param float $score
-     * @return bool
      */
     private function isPerfectScore(float $score): bool
     {
@@ Line 124 @@

     /**
      * Check if no scoring strategy matched the index entry.
-     *
-     * @param float $score
-     * @return bool
      */
     private function hasNoMatch(float $score): bool
     {
@@ Line 137 @@
      * Check if multi-word scoring can be performed.
      *
      * @param array<int, array<string, mixed>> $indexEntries
-     * @param SearchContextInterface $context
-     * @return bool
      */
     private function cannotCalculateMultiWordScore(array $indexEntries, SearchContextInterface $context): bool
     {
@@ Line 149 @@
      * Find the best similarity score for each query word against all index entries.
      *
      * @param array<int, array<string, mixed>> $indexEntries
-     * @param SearchContextInterface $context
      * @param array<int, string> $queryWords
      * @return array<int, float>
      */
@@ Line 172 @@
      * Find the best similarity score for a single query word against all index entries.
      *
      * @param array<int, array<string, mixed>> $indexEntries
-     * @param SearchContextInterface $context
-     * @param string $queryWord
-     * @return float
      */
     private function findBestMatchingScoreForWord(array $indexEntries, SearchContextInterface $context, string $queryWord): float
     {
@@ Line 200 @@

     /**
      * Check if a similarity score meets or exceeds the configured threshold.
-     *
-     * @param float $similarity
-     * @param SearchContextInterface $context
-     * @return bool
      */
     private function isScoreAboveThreshold(float $similarity, SearchContextInterface $context): bool
     {
@@ Line 212 @@

     /**
      * Check if a word was successfully matched (score > minimum).
-     *
-     * @param float $score
-     * @return bool
      */
     private function isWordMatched(float $score): bool
     {
@@ Line 225 @@
      * Check if no query words were matched against any index entry.
      *
      * @param array<int, float> $matchedScores
-     * @return bool
      */
     private function noWordsMatched(array $matchedScores): bool
     {
@@ Line 236 @@
      * Calculate the average score of all matched query words.
      *
      * @param array<int, float> $matchedScores
-     * @return float
      */
     private function calculateAverageScore(array $matchedScores): float
     {
@@ Line 248 @@
      *
      * @param array<int, float> $matchedScores
      * @param array<int, string> $queryWords
-     * @return float
      */
     private function calculateCoverageRatio(array $matchedScores, array $queryWords): float
     {
@@ Line 259 @@
      * Get coverage bonus based on the coverage ratio using configured thresholds.
      *
      * @param float $coverageRatio Value between 0 and 1
-     * @return float
      */
     private function getCoverageBonus(float $coverageRatio): float
     {
@@ Line 278 @@

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
@@ Line 292 @@
     /**
      * Calculate fallback score when no strategy matches the index entry.
      *
-     * @param SearchContextInterface $context
      * @param array<string, mixed> $indexEntry
-     * @return float
      */
     private function calculateFallbackScore(SearchContextInterface $context, array $indexEntry): float
     {
@@ Line 311 @@
     /**
      * Apply field-specific weighting to the calculated score.
      *
-     * @param float $score
      * @param array<string, mixed> $match
-     * @return float
      */
     private function applyFieldWeighting(float $score, array $match): float
     {
@@ Line 344 @@

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


60) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/SearchProcessorService.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Services;

+use Fuzzy\Stages\NormalizeQueryStage;
+use Fuzzy\Stages\MatchDiscoveryStage;
+use Fuzzy\Stages\ScoringStage;
+use Fuzzy\Stages\RelevanceScoringStage;
+use Fuzzy\Stages\SortAndLimitStage;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
 use Fuzzy\Contracts\ResultFilterInterface;
@@ Line 66 @@

     /**
      * {@inheritDoc}
+     * @param array<string, mixed> $options
      */
     public function searchInModel(string $modelClass, string $query, array $options = []): Collection
     {
@@ Line 152 @@
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


61) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/ServiceRegistrar.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Services;

+use RuntimeException;
 use Fuzzy\Cache\LaravelCacheStore;
 use Fuzzy\Commands\ClearCacheCommand;
 use Fuzzy\Commands\ClearIndexCommand;
@@ Line 74 @@
     {
         $helpersPath = __DIR__ . '/../helpers.php';
         if (!file_exists($helpersPath)) {
-            throw new \RuntimeException("helpers.php not found at: {$helpersPath}");
+            throw new RuntimeException('helpers.php not found at: ' . $helpersPath);
         }
+
         require_once $helpersPath;
     }

@@ Line 83 @@
     {
         $this->mergeConfigFrom(__DIR__ . '/../../config/fuzzy.php', 'fuzzy');

-        $this->app->singleton(SimilarityCalculatorConfig::class, fn() => SimilarityCalculatorConfig::createDefault());
-        $this->app->singleton(AdvancedScoringConfig::class, fn() => AdvancedScoringConfig::fromConfig());
+        $this->app->singleton(SimilarityCalculatorConfig::class, fn(): SimilarityCalculatorConfig => SimilarityCalculatorConfig::createDefault());
+        $this->app->singleton(AdvancedScoringConfig::class, fn(): AdvancedScoringConfig => AdvancedScoringConfig::fromConfig());

-        $this->app->singleton(LongestCommonSubstringConfig::class, fn() => LongestCommonSubstringConfig::fromConfig());
-        $this->app->singleton(LevenshteinAlgorithmConfig::class, fn() => LevenshteinAlgorithmConfig::fromConfig());
-        $this->app->singleton(PrefixAlgorithmConfig::class, fn() => PrefixAlgorithmConfig::fromConfig());
+        $this->app->singleton(LongestCommonSubstringConfig::class, fn(): LongestCommonSubstringConfig => LongestCommonSubstringConfig::fromConfig());
+        $this->app->singleton(LevenshteinAlgorithmConfig::class, fn(): LevenshteinAlgorithmConfig => LevenshteinAlgorithmConfig::fromConfig());
+        $this->app->singleton(PrefixAlgorithmConfig::class, fn(): PrefixAlgorithmConfig => PrefixAlgorithmConfig::fromConfig());

-        $this->app->singleton(WordSimilarityComparatorConfig::class, fn() => WordSimilarityComparatorConfig::fromConfig());
+        $this->app->singleton(WordSimilarityComparatorConfig::class, fn(): WordSimilarityComparatorConfig => WordSimilarityComparatorConfig::fromConfig());
     }

     private function registerContracts(): void
@@ Line 119 @@

     private function registerCoreServices(): void
     {
-        $this->app->singleton(ContextualNormalizerInterface::class, fn() => new StringNormalizer());
-        $this->app->singleton(StringNormalizer::class, fn() => new StringNormalizer());
+        $this->app->singleton(ContextualNormalizerInterface::class, fn(): StringNormalizer => new StringNormalizer());
+        $this->app->singleton(StringNormalizer::class, fn(): StringNormalizer => new StringNormalizer());

-        $this->app->singleton(ResultFilterService::class, fn() => new ResultFilterService());
+        $this->app->singleton(ResultFilterService::class, fn(): ResultFilterService => new ResultFilterService());

         // CacheManagerService with CacheStoreInterface injection
-        $this->app->singleton(CacheManagerService::class, function ($app) {
+        $this->app->singleton(CacheManagerService::class, function ($app): CacheManagerService {
             return new CacheManagerService(
                 cache: $app->make(CacheStoreInterface::class)
             );
         });

-        $this->app->singleton(ModelDiscoveryService::class, fn() => new ModelDiscoveryService());
+        $this->app->singleton(ModelDiscoveryService::class, fn(): ModelDiscoveryService => new ModelDiscoveryService());

-        $this->app->singleton(PipelineStageManager::class, fn($app) => new PipelineStageManager($app));
+        $this->app->singleton(PipelineStageManager::class, fn($app): PipelineStageManager => new PipelineStageManager($app));

-        $this->app->singleton(IndexBuilder::class, fn($app) => new IndexBuilder(
+        $this->app->singleton(IndexBuilder::class, fn($app): IndexBuilder => new IndexBuilder(
             $app->make(ContextualNormalizerInterface::class)
         ));

-        $this->app->singleton(IndexManagerService::class, fn($app) => new IndexManagerService(
+        $this->app->singleton(IndexManagerService::class, fn($app): IndexManagerService => new IndexManagerService(
             indexBuilder: $app->make(IndexBuilder::class),
             indexRepository: $app->make(IndexRepositoryInterface::class),
             modelDiscovery: $app->make(ModelDiscoveryInterface::class)
         ));

-        $this->app->singleton(SimilarityCalculator::class, function ($app) {
+        $this->app->singleton(SimilarityCalculator::class, function ($app): SimilarityCalculator {
             $calculator = new SimilarityCalculator($app->make(SimilarityCalculatorConfig::class));

             $calculator->addAlgorithm(new LongestCommonSubstringAlgorithm($app->make(LongestCommonSubstringConfig::class)));
@@ Line 155 @@
             return $calculator;
         });

-        $this->app->singleton(AdvancedScoringCalculator::class, fn($app) => new AdvancedScoringCalculator(
+        $this->app->singleton(AdvancedScoringCalculator::class, fn($app): AdvancedScoringCalculator => new AdvancedScoringCalculator(
             $app->make(AdvancedScoringConfig::class)
         ));

-        $this->app->singleton(PipelineManagerService::class, function ($app) {
+        $this->app->singleton(PipelineManagerService::class, function ($app): PipelineManagerService {
             $stageClasses = $this->stageManager->getMergedStages();
             $stages = $this->stageManager->createStageInstances($stageClasses);

@@ Line 169 @@
             );
         });

-        $this->app->singleton(SearchProcessorService::class, fn($app) => new SearchProcessorService(
+        $this->app->singleton(SearchProcessorService::class, fn($app): SearchProcessorService => new SearchProcessorService(
             pipeline: $app->make(Pipeline::class),
             normalizer: $app->make(StringNormalizer::class),
             similarityCalculator: $app->make(SimilarityCalculator::class),
@@ Line 179 @@
             resultFilter: $app->make(ResultFilterInterface::class)
         ));

-        $this->app->singleton(SearchServiceInterface::class, fn($app) => new FuzzySearchService(
+        $this->app->singleton(SearchServiceInterface::class, fn($app): FuzzySearchService => new FuzzySearchService(
             cacheManager: $app->make(CacheManagerInterface::class),
             modelDiscovery: $app->make(ModelDiscoveryInterface::class),
             indexManager: $app->make(IndexManagerInterface::class),
@@ Line 192 @@

     private function registerAlgorithms(): void
     {
-        $this->app->singleton(WordSimilarityComparator::class, fn($app) => new WordSimilarityComparator(
+        $this->app->singleton(WordSimilarityComparator::class, fn($app): WordSimilarityComparator => new WordSimilarityComparator(
             normalizer: $app->make(StringNormalizer::class),
             config: $app->make(WordSimilarityComparatorConfig::class)
         ));
@@ Line 200 @@

     private function registerScoring(): void
     {
-        $this->app->singleton(ScoringEngineInterface::class, function ($app) {
+        $this->app->singleton(ScoringEngineInterface::class, function ($app): ScoringEngine {
             $calculator = $app->make(AdvancedScoringCalculator::class);

             return new ScoringEngine(
@@ Line 250 @@

         $migrationFiles = glob($sourceMigrationsPath . '/*.php');

-        if (empty($migrationFiles)) {
+        if ($migrationFiles === [] || $migrationFiles === false) {
             return;
         }
    ----------- end diff -----------

Applied rules:
 * EncapsedStringsToSprintfRector
 * NewlineAfterStatementRector
 * DisallowedEmptyRuleFixerRector
 * AddArrowFunctionReturnTypeRector
 * ClosureReturnTypeRector


62) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/SimilarityCalculator.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Services;

-use Fuzzy\Services\Algorithms\LongestCommonSubstringAlgorithm;
-use Fuzzy\Services\Algorithms\LevenshteinSimilarityAlgorithm;
-use Fuzzy\Services\Algorithms\PrefixSimilarityAlgorithm;
 use Fuzzy\Contracts\SimilarityAlgorithmInterface;
 use Fuzzy\Config\SimilarityCalculatorConfig;

@@ Line 14 @@
 {
     /** @var array<int, SimilarityAlgorithmInterface> */
     private array $algorithms = [];
+
     private SimilarityCalculatorConfig $config;

     public function __construct(?SimilarityCalculatorConfig $config = null)
@@ Line 62 @@

     private function calculateCompositeWordSimilarity(string $firstWord, string $secondWord): float
     {
-        if (empty($this->algorithms)) {
+        if ($this->algorithms === []) {
             return FUZZY_SCORE_NONE;
         }

@@ Line 124 @@
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


63) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Services/StringNormalizer.php:19

    ---------- begin diff ----------
@@ Line 19 @@
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
@@ Line 45 @@

     /**
      * Current field being processed (for contextual normalization).
-     *
-     * @var string|null
      */
     private ?string $currentField = null;

@@ Line 63 @@

     /**
      * Load stop words from internal language files based on application locale.
-     *
-     * @return void
      */
     private function loadStopWords(): void
     {
@@ Line 92 @@
         if (function_exists('app') && method_exists(app(), 'getLocale')) {
             return app()->getLocale();
         }
+
         return $_ENV['APP_LOCALE'] ?? 'en';
     }

@@ Line 244 @@
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


64) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/MatchDiscoveryStage/IndexOptimizer.php:13

    ---------- begin diff ----------
@@ Line 13 @@
 class IndexOptimizer
 {
     private array $cachedOptimizedIndexes = [];
+
     private array $cacheTimestamps = [];
+
     private MatchDiscoveryConfig $config;

     public function __construct(?MatchDiscoveryConfig $config = null)
@@ Line 45 @@

     /**
      * Build optimized index structures.
+     * @return array<string, mixed[]>
      */
     private function buildOptimizedIndexes(array $wordIndex): array
     {
@@ Line 63 @@
             if (!isset($byLength[$wordLength])) {
                 $byLength[$wordLength] = [];
             }
+
             $byLength[$wordLength][$wordString] = $matches;

             $firstChar = $wordString[0];
@@ Line 69 @@
             if (!isset($byFirstChar[$firstChar])) {
                 $byFirstChar[$firstChar] = [];
             }
+
             $byFirstChar[$firstChar][$wordString] = $matches;

             if ($wordLength >= $this->config->getMinTrigramLength()) {
@@ Line 85 @@

     /**
      * Add word to trigram index.
+     * @param never[][] $trigramIndex
      */
     private function addToTrigramIndex(string $word, array $matches, array &$trigramIndex): void
     {
@@ Line 94 @@
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


65) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/MatchDiscoveryStage/MatchFinder.php:13

    ---------- begin diff ----------
@@ Line 13 @@
 class MatchFinder
 {
     private MatchDiscoveryConfig $config;
+
     private IndexOptimizer $optimizer;

     public function __construct(?MatchDiscoveryConfig $config = null)
@@ Line 109 @@

     /**
      * Simple fuzzy match discovery for small indexes.
+     * @param array<string, mixed> $wordIndex
      */
     private function discoverFuzzyMatchesSimple(SearchContextInterface $context, array $wordIndex): void
     {
@@ Line 144 @@

     /**
      * Find words containing the query word.
+     * @param array<int, mixed> $byLength
      */
     private function findContainedMatches(
         string $queryWord,
@@ Line 385 @@
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


66) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/Stages/RelevanceScoringStage.php:83

    ---------- begin diff ----------
@@ Line 83 @@
     private function calculateRelevanceScores(SearchContextInterface $context): Collection
     {
         return collect($context->results)
-            ->map(function (object $result) use ($context) {
+            ->map(function (object $result) use ($context): object {
                 $relevance = $this->calculateRelevanceForResult($result, $context);
                 $result->relevance = $relevance;
                 return $result;
@@ Line 135 @@
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


67) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/ClearIndexCommandTest.php:78

    ---------- begin diff ----------
@@ Line 78 @@

         // Act: Execute command with force flag
         $this->artisan('fuzzy:clear', ['--force' => true])
-            ->expectsOutput("✓ Cleared all indexes ({$initialCount} entries)")
+            ->expectsOutput(sprintf('✓ Cleared all indexes (%s entries)', $initialCount))
             ->assertExitCode(0);

         // Assert: All indexes should be removed
@@ Line 107 @@

         // Assert: Command should succeed and display correct message
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("✓ Cleared all indexes ({$initialCount} entries)", $output);
+        $this->assertStringContainsString(sprintf('✓ Cleared all indexes (%s entries)', $initialCount), $output);

         // Assert: Database should be empty after clearing
         $finalCount = FuzzyIndex::count();
@@ Line 140 @@

         // Assert: Command should succeed and display correct model-specific message
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("✓ Cleared {$initialUserEntries} entries for " . User::class, $output);
+        $this->assertStringContainsString(sprintf('✓ Cleared %s entries for ', $initialUserEntries) . User::class, $output);

         // Assert: User entries should be removed, Product entries should remain
         $userEntries = FuzzyIndex::where('indexable_type', User::class)->count();
@@ Line 185 @@
             'model' => User::class,
             '--force' => true,
         ])
-            ->expectsOutput("✓ Cleared {$initialEntries} entries for " . User::class)
+            ->expectsOutput(sprintf('✓ Cleared %s entries for ', $initialEntries) . User::class)
             ->assertExitCode(0);

         // Assert: User entries should be removed
@@ Line 225 @@
         // Act: Execute command and accept confirmation
         $this->artisan('fuzzy:clear')
             ->expectsConfirmation('Clear ALL search indexes?', 'yes')
-            ->expectsOutput("✓ Cleared all indexes ({$initialCount} entries)")
+            ->expectsOutput(sprintf('✓ Cleared all indexes (%s entries)', $initialCount))
             ->assertExitCode(0);

         // Assert: All indexes should be removed
@@ Line 282 @@
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
@@ Line 301 @@
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
@@ Line 355 @@

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


68) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/IndexSearchCommandTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Commands;

+use Fuzzy\Services\IndexManagerService;
 use Fuzzy\Commands\IndexSearchCommand;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Tests\Fixtures\NoneUser;
@@ Line 12 @@
 use Fuzzy\Tests\Fixtures\User;
 use Fuzzy\Tests\TestCase;
 use Illuminate\Support\Facades\Artisan;
-use Illuminate\Support\Facades\Config;
 use ReflectionClass;

 /**
@@ Line 61 @@
     public function test_incremental_indexing_does_not_clear_existing_indexes(): void
     {
         // Arrange: Create initial user and index it
-        $user = User::create(['name' => 'Original Name', 'email' => 'original@example.com', 'type' => 'user']);
+        User::create(['name' => 'Original Name', 'email' => 'original@example.com', 'type' => 'user']);
         Artisan::call('fuzzy:index');

         $initialCount = FuzzyIndex::count();
@@ Line 120 @@
     public function test_incremental_indexing_for_specific_model_does_not_clear(): void
     {
         // Arrange: Create user and index it
-        $user = User::create(['name' => 'Original Name', 'email' => 'test@example.com', 'type' => 'user']);
+        User::create(['name' => 'Original Name', 'email' => 'test@example.com', 'type' => 'user']);
         Artisan::call('fuzzy:index', ['model' => User::class]);

         $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();
@@ Line 149 @@
     {
         // Arrange: Create user and product, index both
         $user = User::create(['name' => 'User Name', 'email' => 'user@example.com', 'type' => 'user']);
-        $product = Product::create(['name' => 'Product Name', 'description' => 'Test', 'price' => 100]);
+        Product::create(['name' => 'Product Name', 'description' => 'Test', 'price' => 100]);
         Artisan::call('fuzzy:index');

         // Modify user
@@ Line 187 @@
     public function test_incremental_indexing_adds_new_records_only(): void
     {
         // Arrange: Create and index initial user
-        $user1 = User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
+        User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
         Artisan::call('fuzzy:index', ['model' => User::class]);

         $initialCount = FuzzyIndex::where('indexable_type', User::class)->count();
@@ Line 194 @@
         $this->assertEquals(2, $initialCount);

         // Create a second user
-        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com', 'type' => 'user']);
+        User::create(['name' => 'User Two', 'email' => 'user2@example.com', 'type' => 'user']);

         // Act: Run incremental indexing (no --force)
         $exitCode = Artisan::call('fuzzy:index', ['model' => User::class]);
-        $output = Artisan::output();
+        Artisan::output();

         // Assert: Only the new user should be indexed (no reindex of existing)
         $this->assertEquals(0, $exitCode);
@@ Line 234 @@
         // Arrange: Create test data without auto-indexing
         FuzzyIndex::query()->truncate();

-        $user = User::withoutEvents(function () {
+        User::withoutEvents(function () {
             return User::create(['name' => 'User One', 'email' => 'user1@example.com', 'type' => 'user']);
         });

-        $product = Product::withoutEvents(function () {
+        Product::withoutEvents(function () {
             return Product::create(['name' => 'Product One', 'description' => 'Test', 'price' => 100]);
         });

@@ Line 269 @@
     public function test_index_command_with_custom_chunk_size(): void
     {
         // Arrange: Create 150 users to test chunking
-        for ($i = 1; $i <= 150; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 150; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }

         // Act: Index with chunk size of 50 (should process in 3 batches)
@@ Line 335 @@
     public function test_index_command_displays_statistics_correctly(): void
     {
         // Arrange: Create 5 indexable users
-        for ($i = 1; $i <= 5; $i++) {
-            User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com", 'type' => 'user']);
+        for ($i = 1; $i <= 5; ++$i) {
+            User::create(['name' => 'User ' . $i, 'email' => sprintf('user%d@example.com', $i), 'type' => 'user']);
         }

         // Act: Index the User model
@@ Line 358 @@
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
@@ Line 398 @@
         $this->assertStringContainsString('No records found', $output);

         // Check that the total entries is 0
-        $stats = app(\Fuzzy\Services\IndexManagerService::class)->getStats();
+        $stats = app(IndexManagerService::class)->getStats();
         $this->assertEquals(0, $stats['total_entries']);
     }

@@ Line 431 @@
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
@@ Line 452 @@
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
@@ Line 508 @@
     public function test_index_command_uses_auto_discovery(): void
     {
         // Arrange: Create test data
-        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);
+        User::create(['name' => 'Test User', 'email' => 'test@example.com', 'type' => 'user']);

         // Act: Execute index command (no configuration, uses auto-discovery)
         $exitCode = Artisan::call('fuzzy:index');
@@ Line 634 @@
     public function test_index_command_ignores_indexation_level(): void
     {
         // Arrange: Create a model with NONE indexation level (no auto-indexing)
-        $user = NoneUser::create([
+        NoneUser::create([
             'name' => 'None User',
             'email' => 'none@example.com',
             'type' => 'user',
@@ Line 641 @@
         ]);

         // Verify no auto-indexing occurred on create (NONE level respects events)
-        $statsBefore = app(\Fuzzy\Services\IndexManagerService::class)->getStats();
+        $statsBefore = app(IndexManagerService::class)->getStats();
         $this->assertArrayNotHasKey(NoneUser::class, $statsBefore['models']);

         // Act: Run the index command (manual indexing)
@@ Line 651 @@
         // because the command uses direct manual calls, not events
         $this->assertEquals(0, $exitCode);

-        $statsAfter = app(\Fuzzy\Services\IndexManagerService::class)->getStats();
+        $statsAfter = app(IndexManagerService::class)->getStats();
         $this->assertArrayHasKey(NoneUser::class, $statsAfter['models']);
         $this->assertEquals(2, $statsAfter['models'][NoneUser::class]['count']);
     }
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * EncapsedStringsToSprintfRector
 * PostIncDecToPreIncDecRector
 * RemoveUnusedVariableAssignRector
 * StringCastAssertStringContainsStringRector


69) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Commands/StatsIndexCommandTest.php:103

    ---------- begin diff ----------
@@ Line 103 @@

         // Assert: Total entries should match the database count
         $this->assertEquals(0, $exitCode);
-        $this->assertStringContainsString("Total entries: {$expectedTotal}", $output);
+        $this->assertStringContainsString('Total entries: ' . $expectedTotal, $output);
     }

     /**
@@ Line 198 @@
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
@@ Line 246 @@
         $reflection = new ReflectionClass($command);
         $signatureProperty = $reflection->getProperty('signature');
         $signatureProperty->setAccessible(true);
+
         $signature = $signatureProperty->getValue($command);

         // Assert: Signature should be 'fuzzy:stats'
@@ Line 264 @@
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
@@ Line 337 @@
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


70) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/FuzzySearchServiceProviderTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

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
@@ Line 58 @@
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
@@ Line 133 @@
         $this->provider->register();

         // Assert: Custom values should be preserved (not overwritten by defaults)
-        $this->assertEquals(0.5, config('fuzzy.default_options.min_score'));
+        $this->assertEqualsWithDelta(0.5, config('fuzzy.default_options.min_score'), PHP_FLOAT_EPSILON);
         $this->assertEquals(100, config('fuzzy.default_options.max_results'));
         $this->assertFalse(config('fuzzy.default_options.fuzzy'));
         $this->assertFalse(config('fuzzy.cache.enabled'));
@@ Line 165 @@
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


71) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/FuzzySearchServiceTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Services;

+use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Contracts\CacheManagerInterface;
 use Fuzzy\Contracts\IndexManagerInterface;
 use Fuzzy\Contracts\ModelDiscoveryInterface;
@@ Line 25 @@
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
@@ Line 93 @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ Line 114 @@
         // Assert: Verify exact match results
         $this->assertInstanceOf(Collection::class, $results);
         $this->assertGreaterThan(0, $results->count());
-        $this->assertEquals(0.95, $results->first()->score);
+        $this->assertEqualsWithDelta(0.95, $results->first()->score, PHP_FLOAT_EPSILON);
     }

     /**
@@ Line 133 @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ Line 210 @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ Line 253 @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ Line 291 @@
         $this->cacheManager
             ->shouldReceive('remember')
             ->once()
-            ->andReturnUsing(function ($type, $callback, $params) use ($mockResults) {
+            ->andReturnUsing(function ($type, $callback, $params) {
                 return $callback();
             });

@@ Line 428 @@
     public function test_index_model_via_index_manager(): void
     {
         // Arrange: Create mock model and expect index call
-        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
+        $model = Mockery::mock(MustFuzzySearch::class);

         $this->indexManager
             ->shouldReceive('indexModel')
@@ Line 448 @@
     public function test_remove_model_via_index_manager(): void
     {
         // Arrange: Create mock model and expect removal call
-        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
+        $model = Mockery::mock(MustFuzzySearch::class);

         $this->indexManager
             ->shouldReceive('removeModel')
@@ Line 468 @@
     public function test_update_model_index_via_index_manager(): void
     {
         // Arrange: Create mock model and expect update call
-        $model = Mockery::mock(\Fuzzy\Contracts\MustFuzzySearch::class);
+        $model = Mockery::mock(MustFuzzySearch::class);

         $this->indexManager
             ->shouldReceive('updateModelIndex')
@@ Line 536 @@
         $stats = $this->service->getIndexManager()->getStats();

         // Assert: Verify statistics are returned correctly
-        $this->assertEquals($expectedStats, $stats);
+        $this->assertSame($expectedStats, $stats);
     }

     /**
@@ Line 558 @@
         $stats = $this->service->getIndexManager()->getPreciseModelStats($modelClass);

         // Assert: Verify model statistics are returned correctly
-        $this->assertEquals($expectedStats, $stats);
+        $this->assertSame($expectedStats, $stats);
     }

     /**
@@ Line 632 @@
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


72) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Models/FuzzyIndexTest.php:350

    ---------- begin diff ----------
@@ Line 350 @@
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


73) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Repositories/IndexRepositoryTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Repositories;

+use PHPUnit\Framework\Attributes\Test;
 use Fuzzy\Data\SearchOptionsData;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Repositories\IndexRepository;
@@ Line 15 @@
 use Fuzzy\Tests\Fixtures\Product;
 use Fuzzy\Tests\Fixtures\User;
 use Fuzzy\Tests\TestCase;
-use Fuzzy\ValueObjects\IndexData;
 use Fuzzy\ValueObjects\SearchQuery;
 use Illuminate\Support\Collection;

@@ Line 36 @@
         $this->repository = new IndexRepository();
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_empty_index_data_for_model_with_empty_database(): void
     {
         // Arrange : Prepare repository with empty database
@@ Line 57 @@
         $this->assertEmpty($data['modelIndex']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_index_data_for_model_with_existing_data(): void
     {
         // Arrange : Create a user and its index entries
@@ Line 99 @@
         $this->assertArrayHasKey($userKey, $data['modelIndex']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_filters_index_data_by_specific_model_ids(): void
     {
         // Arrange : Create two users with index entries
@@ Line 135 @@
         $this->assertArrayNotHasKey($user2Key, $data['itemMap']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_skips_short_words_when_building_index(): void
     {
         // Arrange : Create a user with short words in name field
@@ Line 174 @@
         $this->assertArrayHasKey('ef', $data['wordIndex']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_empty_collection_for_empty_ids_batch(): void
     {
         // Arrange : Prepare repository with empty ID list
@@ Line 189 @@
         $this->assertTrue($models->isEmpty());
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_specific_models_for_given_ids_batch(): void
     {
         // Arrange : Create three users in database
@@ Line 207 @@
         $this->assertEquals([$user1->id, $user2->id], $models->pluck('id')->sort()->values()->toArray());
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_preloads_models_into_context_cache(): void
     {
         // Arrange : Create two users with index entries and prepare search context
@@ Line 251 @@
         $this->assertInstanceOf(User::class, $modelsMap[$user2Key]);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_handles_preload_with_empty_search_context(): void
     {
         // Arrange : Create search context with empty index data
@@ Line 267 @@
         $this->assertEmpty($modelsMap);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_empty_statistics_with_no_data(): void
     {
         // Arrange : Prepare empty database
@@ Line 282 @@
         $this->assertEmpty($stats['models']);
     }

-    /**
-     * @test
-     */
+    #[Test]
     public function test_returns_statistics_with_indexed_data(): void
     {
         // Arrange : Create users and products with multiple index entries
@@ Line 338 @@

     /**
      * Helper method to create index entries for user fields.
+     * @param string[] $words
      */
     private function createIndexEntryForUserField(int $userId, string $field, string $originalValue, string $normalizedValue, array $words): void
     {
@@ Line 363 @@

     /**
      * Helper method to create index entries for product fields.
+     * @param string[] $words
      */
     private function createIndexEntryForProductField(int $productId, string $field, string $originalValue, string $normalizedValue, array $words): void
     {
@@ Line 383 @@

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


74) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/AdvancedScoringCalculatorTest.php:27

    ---------- begin diff ----------
@@ Line 27 @@
 final class AdvancedScoringCalculatorTest extends TestCase
 {
     private AdvancedScoringCalculator $calculator;
+
     private SearchContext $context;

     protected function setUp(): void
@@ Line 46 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: $similarityCalculator,
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );
     }
@@ Line 193 @@
             options: new SearchOptionsData(),
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 228 @@
             options: new SearchOptionsData(),
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 263 @@
             options: new SearchOptionsData(),
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * CreateStubOverCreateMockArgRector


75) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/Algorithms/WordSimilarity/LetterDistanceCalculatorTest.php:21

    ---------- begin diff ----------
@@ Line 21 @@
     public function test_calculate_letter_distance_identical_strings(): void
     {
         $distance = $this->calculator->calculateLetterDistance('hello', 'hello');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distance);
     }

     public function test_calculate_letter_distance_similar_strings(): void
@@ Line 73 @@
         $distanceSame = $this->calculator->calculateLetterDistance('a', 'a');
         $distanceDiff = $this->calculator->calculateLetterDistance('a', 'b');

-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distanceSame);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $distanceSame);
         $this->assertGreaterThan(0, $distanceDiff);
     }

@@ Line 99 @@
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
@@ Line 120 @@
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


76) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/Algorithms/WordSimilarity/WordSimilarityCalculatorTest.php:21

    ---------- begin diff ----------
@@ Line 21 @@
     public function test_calculate_word_similarity_exact_match(): void
     {
         $score = $this->calculator->calculateWordSimilarity('hello', 'hello');
-        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);
+        $this->assertSame(FUZZY_DISTANCE_IDENTICAL, $score);
     }

     public function test_calculate_word_similarity_contained_word(): void
@@ Line 45 @@
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
@@ Line 77 @@
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
@@ Line 98 @@
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


77) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/CacheManagerServiceTest.php:5

    ---------- begin diff ----------
@@ Line 5 @@
 namespace Fuzzy\Tests\Unit\Services;

 use Fuzzy\Cache\LaravelCacheStore;
-use Fuzzy\Config\CacheConfig;
 use Fuzzy\Services\CacheManagerService;
 use Fuzzy\Tests\Fixtures\Product;
 use Fuzzy\Tests\Fixtures\User;
@@ Line 15 @@
 final class CacheManagerServiceTest extends TestCase
 {
     private CacheManagerService $cacheManager;
+
     private LaravelCacheStore $cacheStore;

     protected function setUp(): void
@@ Line 62 @@
         $this->cacheManager = new CacheManagerService($this->cacheStore);

         $executed = false;
-        $result = $this->cacheManager->remember('test', function () use (&$executed) {
+        $result = $this->cacheManager->remember('test', function () use (&$executed): string {
             $executed = true;
             return 'callback_result';
         }, []);
@@ Line 78 @@

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
@@ Line 100 @@

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

     public function test_invalidate_all_clears_cache(): void
@@ Line 118 @@
         config(['fuzzy.cache.enabled' => true]);
         $this->cacheManager = new CacheManagerService($this->cacheStore);

-        $this->cacheManager->remember('test', fn() => 'value', []);
+        $this->cacheManager->remember('test', fn(): string => 'value', []);
         $this->cacheManager->invalidateAll();

         $callbackExecuted = false;
-        $result = $this->cacheManager->remember('test', function () use (&$callbackExecuted) {
+        $result = $this->cacheManager->remember('test', function () use (&$callbackExecuted): string {
             $callbackExecuted = true;
             return 'new_value';
         }, []);
@@ Line 139 @@
         $userParams = [User::class, 'john', []];
         $productParams = [Product::class, 'laptop', []];

-        $this->cacheManager->remember('search_in_model', fn() => 'user_result', $userParams);
-        $this->cacheManager->remember('search_in_model', fn() => 'product_result', $productParams);
+        $this->cacheManager->remember('search_in_model', fn(): string => 'user_result', $userParams);
+        $this->cacheManager->remember('search_in_model', fn(): string => 'product_result', $productParams);

         // Invalider uniquement le cache User
         $this->cacheManager->invalidateForModel(User::class);
@@ Line 149 @@
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
@@ Line 195 @@
         $longString = str_repeat('a', 300);
         $params = [$longString];

-        $result = $this->cacheManager->remember('test', fn() => 'value', $params);
+        $result = $this->cacheManager->remember('test', fn(): string => 'value', $params);

         // Devrait retourner la valeur, pas la clé
         $this->assertEquals('value', $result);
@@ Line 208 @@

         // First call should execute callback
         $executed = false;
-        $result1 = $this->cacheManager->remember('stats', function () use (&$executed) {
+        $result1 = $this->cacheManager->remember('stats', function () use (&$executed): string {
             $executed = true;
             return 'stats_value';
         }, []);
@@ Line 218 @@

         // Second call should use cache
         $executed = false;
-        $result2 = $this->cacheManager->remember('stats', function () use (&$executed) {
+        $result2 = $this->cacheManager->remember('stats', function () use (&$executed): string {
             $executed = true;
             return 'stats_value';
         }, []);
@@ Line 231 @@

         // Third call should execute callback again
         $executed = false;
-        $result3 = $this->cacheManager->remember('stats', function () use (&$executed) {
+        $result3 = $this->cacheManager->remember('stats', function () use (&$executed): string {
             $executed = true;
             return 'new_stats_value';
         }, []);
@@ Line 238 @@

         $this->assertTrue($executed);
         $this->assertEquals('new_stats_value', $result3);
-    }
-
-    private function getCacheKeysStorageKey(): string
-    {
-        $config = CacheConfig::fromConfig();
-        return $config->getCacheKeysStorageKey();
     }
 }
    ----------- end diff -----------

Applied rules:
 * NewlineBetweenClassLikeStmtsRector
 * PostIncDecToPreIncDecRector
 * RemoveUnusedPrivateMethodRector
 * AssertEqualsToSameRector
 * AddArrowFunctionReturnTypeRector
 * ClosureReturnTypeRector


78) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/IndexBuilderTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Services;

+use PHPUnit\Framework\Attributes\CoversClass;
+use Carbon\Carbon;
 use Fuzzy\Models\FuzzyIndex;
 use Fuzzy\Services\IndexBuilder;
 use Fuzzy\Services\StringNormalizer;
@@ Line 15 @@

 /**
  * Test suite for the IndexBuilder service.
- *
- * @covers \Fuzzy\Services\IndexBuilder
  */
+#[CoversClass(\Fuzzy\Services\IndexBuilder::class)]
 final class IndexBuilderTest extends TestCase
 {
     private IndexBuilder $builder;
@@ Line 245 @@
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


79) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/IndexManagerServiceTest.php:32

    ---------- begin diff ----------
@@ Line 32 @@
     use RefreshDatabase;

     private IndexManagerService $indexManager;
-    private IndexBuilder $indexBuilder;
-    private IndexRepositoryInterface $indexRepository;
-    private ModelDiscoveryInterface $modelDiscovery;

     protected function setUp(): void
     {
@@ Line 41 @@
         parent::setUp();

         // Arrange: Use real implementations from the container
-        $this->indexBuilder = app(IndexBuilder::class);
-        $this->indexRepository = app(IndexRepositoryInterface::class);
-        $this->modelDiscovery = app(ModelDiscoveryInterface::class);
+        $indexBuilder = app(IndexBuilder::class);
+        $indexRepository = app(IndexRepositoryInterface::class);
+        $modelDiscovery = app(ModelDiscoveryInterface::class);

         $this->indexManager = new IndexManagerService(
-            indexBuilder: $this->indexBuilder,
-            indexRepository: $this->indexRepository,
-            modelDiscovery: $this->modelDiscovery
+            indexBuilder: $indexBuilder,
+            indexRepository: $indexRepository,
+            modelDiscovery: $modelDiscovery
         );
     }

@@ Line 287 @@
         // Arrange: Create and index multiple models
         $user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'type' => 'user']);
         $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'type' => 'user']);
-        $nonIndexableUser = NonIndexableUser::create([
+        NonIndexableUser::create([
             'name' => 'Admin User',
             'email' => 'admin@example.com',
             'type' => 'admin',
@@ Line 366 @@
     public function test_reindex_model_validates_each_model_before_indexing(): void
     {
         // Arrange: Create valid searchable models
-        $validUser = User::create(['name' => 'Valid User', 'email' => 'valid@example.com', 'type' => 'user']);
-        $validUser2 = User::create(['name' => 'Valid User 2', 'email' => 'valid2@example.com', 'type' => 'user']);
+        User::create(['name' => 'Valid User', 'email' => 'valid@example.com', 'type' => 'user']);
+        User::create(['name' => 'Valid User 2', 'email' => 'valid2@example.com', 'type' => 'user']);

         // Act: Reindex all users
         $this->indexManager->reindexModel(User::class);
@@ Line 657 @@
     {
         // Assert: NONE returns empty array
         $events = IndexationLevel::NONE->toEventsArray();
-        $this->assertEquals([], $events);
+        $this->assertSame([], $events);
         $this->assertFalse(IndexationLevel::NONE->hasEvent('create'));
         $this->assertFalse(IndexationLevel::NONE->hasEvent('update'));
         $this->assertFalse(IndexationLevel::NONE->hasEvent('delete'));
@@ Line 664 @@

         // Assert: CREATE_ONLY returns only create event
         $events = IndexationLevel::CREATE_ONLY->toEventsArray();
-        $this->assertEquals(['create'], $events);
+        $this->assertSame(['create'], $events);
         $this->assertTrue(IndexationLevel::CREATE_ONLY->hasEvent('create'));
         $this->assertFalse(IndexationLevel::CREATE_ONLY->hasEvent('update'));
         $this->assertFalse(IndexationLevel::CREATE_ONLY->hasEvent('delete'));
@@ Line 671 @@

         // Assert: ALL returns all three events
         $events = IndexationLevel::ALL->toEventsArray();
-        $this->assertEquals(['create', 'update', 'delete'], $events);
+        $this->assertSame(['create', 'update', 'delete'], $events);
         $this->assertTrue(IndexationLevel::ALL->hasEvent('create'));
         $this->assertTrue(IndexationLevel::ALL->hasEvent('update'));
         $this->assertTrue(IndexationLevel::ALL->hasEvent('delete'));
@@ Line 678 @@

         // Assert: CREATE_AND_UPDATE returns create and update
         $events = IndexationLevel::CREATE_AND_UPDATE->toEventsArray();
-        $this->assertEquals(['create', 'update'], $events);
+        $this->assertSame(['create', 'update'], $events);
         $this->assertTrue(IndexationLevel::CREATE_AND_UPDATE->hasEvent('create'));
         $this->assertTrue(IndexationLevel::CREATE_AND_UPDATE->hasEvent('update'));
         $this->assertFalse(IndexationLevel::CREATE_AND_UPDATE->hasEvent('delete'));
@@ Line 685 @@

         // Assert: UPDATE_AND_DELETE returns update and delete
         $events = IndexationLevel::UPDATE_AND_DELETE->toEventsArray();
-        $this->assertEquals(['update', 'delete'], $events);
+        $this->assertSame(['update', 'delete'], $events);

         // Assert: CREATE_AND_DELETE returns create and delete
         $events = IndexationLevel::CREATE_AND_DELETE->toEventsArray();
-        $this->assertEquals(['create', 'delete'], $events);
+        $this->assertSame(['create', 'delete'], $events);
     }

     /**
@@ Line 785 @@
         // Manual update should also work
         $user->name = 'Updated Manual';
         $user->save();
+
         $this->indexManager->updateModelIndex($user);

         // Manual removal should work
         $this->indexManager->removeModel($user);
+
         $statsAfterRemove = $this->indexManager->getStats();
         $this->assertArrayNotHasKey(NoneUser::class, $statsAfterRemove['models']);
     }
    ----------- end diff -----------

Applied rules:
 * NewlineBeforeNewAssignSetRector
 * RemoveUnusedVariableAssignRector
 * NarrowUnusedSetUpDefinedPropertyRector
 * AssertEqualsToSameRector


80) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ModelDiscoveryServiceTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Services;

-use Fuzzy\Contracts\MustFuzzySearch;
 use Fuzzy\Exceptions\ModelNotSearchableException;
 use Fuzzy\Services\ModelDiscoveryService;
 use Fuzzy\Tests\Fixtures\NonSearchableModel;
    ----------- end diff -----------

Applied rules:


81) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/PipelineManagerServiceTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Services;

+use stdClass;
+use ReflectionClass;
 use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Contracts\StageInterface;
 use Fuzzy\Services\PipelineManagerService;
@@ Line 17 @@
 final class PipelineManagerServiceTest extends TestCase
 {
     private PipelineManagerService $pipelineManager;
+
     private $pipeline;
-    private $stages;

     protected function setUp(): void
     {
@@ Line 33 @@
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

@@ Line 65 @@

         $invalidStages = [
             Mockery::mock(StageInterface::class)->shouldReceive('getPriority')->andReturn(50)->getMock(),
-            new \stdClass(), // Invalid stage
+            new stdClass(), // Invalid stage
         ];

         new PipelineManagerService($this->pipeline, $invalidStages);
@@ Line 92 @@

         $results = $this->pipelineManager->process($context);

-        $this->assertEquals($expectedResults, $results);
+        $this->assertSame($expectedResults, $results);
     }

     public function test_process_returns_empty_array_when_pipeline_returns_empty(): void
@@ Line 121 @@
             {
                 return null;
             }
+
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
             public function getWordIndex(): array
             {
                 return [];
             }
+
             public function getItemMap(): array
             {
                 return [];
             }
+
             public function getModelIndex(): array
             {
                 return [];
             }
+
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
             public function getPotentialMatchesForModel(string $key): array
             {
                 return [];
             }
+
             public function getAllPotentialMatches(): array
             {
                 return [];
             }
+
             public function hasPotentialMatches(string $key): bool
             {
                 return false;
@@ Line 208 @@
         $service->process($mockContext);

         // Verify the execution order was set by our mock
-        $this->assertEquals([1, 2], $executionOrder);
+        $this->assertSame([1, 2], $executionOrder);
     }

     public function test_process_passes_context_through_pipeline(): void
@@ Line 215 @@
     {
         // Use a simple anonymous class instead of Mockery for context
         $mockContext = new class implements SearchContextInterface {
+            /**
+             * @var string[]
+             */
             public array $results = ['final_result'];

             public function getModelInstance(string $key): ?object
@@ Line 221 @@
             {
                 return null;
             }
+
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
             public function getWordIndex(): array
             {
                 return [];
             }
+
             public function getItemMap(): array
             {
                 return [];
             }
+
             public function getModelIndex(): array
             {
                 return [];
             }
+
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
             public function getPotentialMatchesForModel(string $key): array
             {
                 return [];
             }
+
             public function getAllPotentialMatches(): array
             {
                 return [];
             }
+
             public function hasPotentialMatches(string $key): bool
             {
                 return false;
@@ Line 280 @@

         $results = $this->pipelineManager->process($mockContext);

-        $this->assertEquals(['final_result'], $results);
+        $this->assertSame(['final_result'], $results);
     }

     public function test_process_sorts_stages_by_priority_descending(): void
@@ Line 297 @@
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
 * DocblockVarArrayFromPropertyDefaultsRector


82) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/PipelineStageManagerTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Services;

-use Fuzzy\Contracts\StageInterface;
 use Fuzzy\Exceptions\DuplicateStageException;
 use Fuzzy\Services\PipelineStageManager;
 use Fuzzy\Stages\MatchDiscoveryStage;
@@ Line 45 @@
             SortAndLimitStage::class,
         ];

-        $this->assertEquals($expected, $stages);
+        $this->assertSame($expected, $stages);
     }

     /**
    ----------- end diff -----------

Applied rules:
 * AssertEqualsToSameRector


83) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ResultFilterServiceTest.php:40

    ---------- begin diff ----------
@@ Line 40 @@
         $filtered = $this->resultFilter->filterAndSort($results, 0.5);

         $this->assertCount(2, $filtered);
-        $this->assertEquals(0.8, $filtered[0]->score);
-        $this->assertEquals(0.6, $filtered[1]->score);
+        $this->assertEqualsWithDelta(0.8, $filtered[0]->score, PHP_FLOAT_EPSILON);
+        $this->assertEqualsWithDelta(0.6, $filtered[1]->score, PHP_FLOAT_EPSILON);
     }

     public function test_filter_and_sort_sorts_by_score_descending(): void
@@ Line 54 @@

         $filtered = $this->resultFilter->filterAndSort($results, 0.0);

-        $this->assertEquals(0.9, $filtered[0]->score);
-        $this->assertEquals(0.7, $filtered[1]->score);
-        $this->assertEquals(0.6, $filtered[2]->score);
+        $this->assertEqualsWithDelta(0.9, $filtered[0]->score, PHP_FLOAT_EPSILON);
+        $this->assertEqualsWithDelta(0.7, $filtered[1]->score, PHP_FLOAT_EPSILON);
+        $this->assertEqualsWithDelta(0.6, $filtered[2]->score, PHP_FLOAT_EPSILON);
     }

     public function test_filter_and_sort_removes_null_results(): void
@@ Line 92 @@

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


84) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ScoringEngineTest.php:21

    ---------- begin diff ----------
@@ Line 21 @@
 final class ScoringEngineTest extends TestCase
 {
     private ScoringEngineInterface $scoringEngine;
+
     private SearchContext $searchContext;

     protected function setUp(): void
@@ Line 65 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: $similarityCalculator,
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $this->scoringEngine,
             indexDataArray: []
         );
@@ Line 74 @@

     /**
      * Creates a test index entry.
+     * @return array<string, string|string[]|float>
      */
     private function createTestIndexEntry(string $field = 'name'): array
     {
@@ Line 193 @@
             options: new SearchOptionsData(),
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $this->scoringEngine,
             indexDataArray: []
         );
@@ Line 242 @@
             options: new SearchOptionsData(),
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $this->scoringEngine,
             indexDataArray: []
         );
@@ Line 312 @@
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
 * CreateStubOverCreateMockArgRector
 * DocblockReturnArrayFromDirectArrayInstanceRector


85) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/SearchProcessorServiceTest.php:19

    ---------- begin diff ----------
@@ Line 19 @@
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
@@ Line 33 @@

         $this->pipeline = Mockery::mock(Pipeline::class);
         $this->normalizer = Mockery::mock(StringNormalizer::class);
-        $this->similarityCalculator = Mockery::mock(SimilarityCalculator::class);
+        $similarityCalculator = Mockery::mock(SimilarityCalculator::class);
         $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
-        $this->scoringEngine = Mockery::mock(ScoringEngine::class);
+        $scoringEngine = Mockery::mock(ScoringEngine::class);
         $this->modelDiscovery = Mockery::mock(ModelDiscoveryInterface::class);
         $this->resultFilter = Mockery::mock(ResultFilterInterface::class);

@@ Line 42 @@
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
@@ Line 293 @@

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


86) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/ServiceRegistrarTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Services;

+use ReflectionClass;
+use Fuzzy\Tests\Fixtures\CustomStage;
+use RuntimeException;
 use Fuzzy\Cache\LaravelCacheStore;
 use Fuzzy\Commands\ClearCacheCommand;
 use Fuzzy\Commands\ClearIndexCommand;
@@ Line 37 @@
 use Fuzzy\Services\SimilarityCalculator;
 use Fuzzy\Services\StringNormalizer;
 use Fuzzy\Tests\TestCase;
-use Illuminate\Support\Facades\File;
 use Illuminate\Support\ServiceProvider;

 /**
@@ Line 49 @@
 final class ServiceRegistrarTest extends TestCase
 {
     private ServiceRegistrar $registrar;
+
     private ServiceProvider $provider;

     protected function setUp(): void
@@ Line 62 @@
             provider: $this->provider
         );

-        $this->app->detectEnvironment(fn() => 'testing');
+        $this->app->detectEnvironment(fn(): string => 'testing');
     }

     protected function tearDown(): void
@@ Line 268 @@
         $calculator = $this->app->make(SimilarityCalculator::class);

         // Assert: Verify algorithms are registered using reflection
-        $reflection = new \ReflectionClass($calculator);
+        $reflection = new ReflectionClass($calculator);
         $algorithmsProperty = $reflection->getProperty('algorithms');
         $algorithmsProperty->setAccessible(true);
+
         $algorithms = $algorithmsProperty->getValue($calculator);

         $this->assertCount(3, $algorithms);
@@ Line 289 @@
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
@@ Line 369 @@
     public function test_register_all_handles_custom_pipeline_stages(): void
     {
         // Arrange: Set custom pipeline stages configuration
-        config(['fuzzy.pipeline' => [\Fuzzy\Tests\Fixtures\CustomStage::class]]);
+        config(['fuzzy.pipeline' => [CustomStage::class]]);

         // Act: Register all services
         $this->registrar->registerAll();
@@ Line 394 @@

         try {
             // Act & Assert: Expect exception when helpers file is missing
-            $this->expectException(\RuntimeException::class);
+            $this->expectException(RuntimeException::class);
             $this->expectExceptionMessage('helpers.php not found at');

             $registrar = new ServiceRegistrar(
@@ Line 435 @@
         $indexBuilder = $this->app->make(IndexBuilder::class);

         // Assert: Verify normalizer dependency is properly injected
-        $reflection = new \ReflectionClass($indexBuilder);
+        $reflection = new ReflectionClass($indexBuilder);
         $property = $reflection->getProperty('normalizer');
         $property->setAccessible(true);
+
         $normalizer = $property->getValue($indexBuilder);

         $this->assertInstanceOf(ContextualNormalizerInterface::class, $normalizer);
@@ Line 460 @@
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
 * AddArrowFunctionReturnTypeRector


87) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/StringNormalizerTest.php:158

    ---------- begin diff ----------
@@ Line 158 @@
         $this->normalizer->setCurrentField('name');
         $result = $this->normalizer->normalizeQuery($input);

-        $this->assertEquals('jean de la fontaine', $result);
+        $this->assertSame('jean de la fontaine', $result);

         $this->normalizer->setCurrentField(null);
         $this->normalizer->setProtectedFields([]);
@@ Line 179 @@
         $result = $this->normalizer->normalizeQuery($input);

         // 'the', 'and', 'are', 'in' sont supprimés, reste 'cat dog house'
-        $this->assertEquals('cat dog house', $result);
+        $this->assertSame('cat dog house', $result);

         $this->normalizer->setCurrentField(null);
         $this->normalizer->setProtectedFields([]);
@@ Line 196 @@

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
@@ Line 227 @@
         $email = 'john.doe+test@example.com';
         $result = $this->normalizer->normalize($email);
         // Les caractères spéciaux sont supprimés par normalize()
-        $this->assertEquals('johndoetestexamplecom', $result);
+        $this->assertSame('johndoetestexamplecom', $result);
     }

     public function test_name_with_multiple_stop_words(): void
@@ Line 238 @@
         $this->normalizer->setCurrentField('name');
         $result = $this->normalizer->normalizeQuery($name);

-        $this->assertEquals('charles de gaulle et jean de la fontaine', $result);
+        $this->assertSame('charles de gaulle et jean de la fontaine', $result);

         $this->normalizer->setCurrentField(null);
         $this->normalizer->setProtectedFields([]);
@@ Line 269 @@
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


88) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Services/WordSimilarityComparatorTest.php:14

    ---------- begin diff ----------
@@ Line 14 @@
 final class WordSimilarityComparatorTest extends TestCase
 {
     private WordSimilarityComparator $comparator;
-    private StringNormalizer $normalizer;

     /**
      * Set up test dependencies.
@@ Line 22 @@
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

@@ Line 46 @@
                 $expectedScore,
                 $score,
                 0.01,
-                "Failed for: '$inputA' vs '$inputB'. Got: $score, Expected: $expectedScore"
+                sprintf("Failed for: '%s' vs '%s'. Got: %s, Expected: %s", $inputA, $inputB, $score, $expectedScore)
             );
         }
     }
@@ Line 70 @@
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
@@ Line 98 @@
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
@@ Line 125 @@
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
@@ Line 153 @@
             $this->assertGreaterThanOrEqual(
                 $expectedMinPenalty,
                 $score,
-                "Score too low for: '$inputA' vs '$inputB'. Got: $score, Min: $expectedMinPenalty"
+                sprintf("Score too low for: '%s' vs '%s'. Got: %s, Min: %s", $inputA, $inputB, $score, $expectedMinPenalty)
             );
         }
     }
@@ Line 173 @@
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

@@ Line 262 @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
         }
     }
@@ Line 307 @@
                 0.0,
                 $score,
                 0.01,
-                "Exact match should return 0 for: '$inputA' vs '$inputB'. Got: $score"
+                sprintf("Exact match should return 0 for: '%s' vs '%s'. Got: %s", $inputA, $inputB, $score)
             );
         }
     }
@@ Line 330 @@
             $this->assertLessThanOrEqual(
                 $maxScore,
                 $score,
-                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
+                sprintf("Score too high for: '%s' vs '%s'. Got: %s, Max: %s", $inputA, $inputB, $score, $maxScore)
             );
             $this->assertGreaterThan(0.0, $score);
         }
@@ Line 369 @@
         $difference1 = $scoreSigma1 - $scoreSigma05;
         $difference2 = $scoreSigma2 - $scoreSigma1;

-        $this->assertGreaterThan(0.02, $difference1, "Sigma should have noticeable effect (diff1: $difference1)");
-        $this->assertGreaterThan(0.02, $difference2, "Sigma should have noticeable effect (diff2: $difference2)");
+        $this->assertGreaterThan(0.02, $difference1, sprintf('Sigma should have noticeable effect (diff1: %s)', $difference1));
+        $this->assertGreaterThan(0.02, $difference2, sprintf('Sigma should have noticeable effect (diff2: %s)', $difference2));
     }

     /**
@@ Line 391 @@
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


89) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/MatchDiscoveryStage/MatchFinderTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Stages\MatchDiscoveryStage;

+use ReflectionProperty;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use Fuzzy\Stages\MatchDiscoveryStage\MatchFinder;
@@ Line 23 @@
 final class MatchFinderTest extends TestCase
 {
     private MatchFinder $finder;
+
     private StringNormalizer $normalizer;
+
     private SimilarityCalculator&MockObject $similarityCalculator;

     protected function setUp(): void
@@ Line 34 @@
         $this->similarityCalculator = $this->createMock(SimilarityCalculator::class);
     }

+    /**
+     * @param array<array<string, array<int, array<string, mixed>>>, mixed> $wordIndex
+     */
     private function createContext(
         string $query,
         SearchOptionsData $options,
@@ Line 47 @@
             options: $options,
             normalizer: $this->normalizer,
             similarityCalculator: $this->similarityCalculator,
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

-        $reflection = new \ReflectionProperty($context, 'indexData');
+        $reflection = new ReflectionProperty($context, 'indexData');
         $reflection->setAccessible(true);
         $reflection->setValue($context, $indexData);

@@ Line 193 @@
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

@@ Line 213 @@
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
 * CreateStubOverCreateMockArgRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


90) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/MatchDiscoveryStageTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Stages;

+use PHPUnit\Framework\MockObject\Stub;
+use Fuzzy\Enums\StageType;
 use Fuzzy\Contracts\IndexRepositoryInterface;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use ReflectionProperty;
@@ Line 26 @@
 final class MatchDiscoveryStageTest extends TestCase
 {
     private MatchDiscoveryStage $stage;
+
     private StringNormalizer $normalizer;
-    private SimilarityCalculator&MockObject $similarityCalculator;
+
+    private SimilarityCalculator&Stub $similarityCalculator;
+
     private MatchFinder&MockObject $matchFinder;

     protected function setUp(): void
@@ Line 34 @@
     {
         parent::setUp();
         $this->normalizer = new StringNormalizer();
-        $this->similarityCalculator = $this->createMock(SimilarityCalculator::class);
+        $this->similarityCalculator = $this->createStub(SimilarityCalculator::class);
         $this->matchFinder = $this->createMock(MatchFinder::class);

         $this->stage = new MatchDiscoveryStage(
@@ Line 43 @@
         );
     }

+    /**
+     * @param array<array<string, array<int, array<string, mixed>>>, mixed> $wordIndex
+     */
     private function createContext(
         string $query,
         SearchOptionsData $options,
@@ Line 56 @@
             options: $options,
             normalizer: $this->normalizer,
             similarityCalculator: $this->similarityCalculator,
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 126 @@
             ->method('discoverVeryCloseMatches')
             ->with($context, 'test', $wordIndex);

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     /**
@@ Line 158 @@
             ->method('discoverMultiWordMatches')
             ->with($context);

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_skips_fuzzy_when_disabled(): void
@@ Line 179 @@
         $this->matchFinder->expects($this->once())
             ->method('discoverMultiWordMatches');

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_skips_multi_word_when_single_word(): void
@@ Line 204 @@
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
@@ Line 227 @@
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
@@ Line 250 @@
             ->method('discoverCloseMatchesOptimized')
             ->with($context, 'test');

-        $this->stage->handle($context, fn() => 'next');
+        $this->stage->handle($context, fn(): string => 'next');
     }

     public function test_handle_calls_next_with_results(): void
@@ Line 257 @@
     {
         $context = $this->createContext('test', new SearchOptionsData(), []);

-        $next = function (SearchContext $ctx) {
+        $next = function (SearchContext $ctx): string {
             return 'processed';
         };

@@ Line 281 @@

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
 * CreateStubOverCreateMockArgRector
 * PropertyCreateMockToCreateStubRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector
 * AddArrowFunctionReturnTypeRector
 * ClosureReturnTypeRector


91) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/NormalizeQueryStageTest.php:46

    ---------- begin diff ----------
@@ Line 46 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 75 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 110 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 144 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );
    ----------- end diff -----------

Applied rules:
 * CreateStubOverCreateMockArgRector


92) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/RelevanceScoringStageTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Stages;

+use Illuminate\Support\Collection;
+use stdClass;
 use Fuzzy\Contracts\IndexRepositoryInterface;
-use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Config\RelevanceScoringConfig;
 use Fuzzy\Data\SearchOptionsData;
 use Fuzzy\Data\SearchResultData;
@@ Line 20 @@
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
@@ Line 37 @@
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
@@ Line 158 @@
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
@@ Line 281 @@
         $this->assertCount(1, $processedResults);

         $resultItem = $processedResults[0];
-        $this->assertEquals(85.5, $resultItem->score);
+        $this->assertEqualsWithDelta(85.5, $resultItem->score, PHP_FLOAT_EPSILON);
         $this->assertEquals('User', $resultItem->modelType);
         $this->assertEquals('name', $resultItem->matchedField);
         $this->assertEquals('John Doe', $resultItem->matchedValue);
@@ Line 407 @@
                 $expected,
                 $result,
                 0.01,
-                "Failed for input: $input. Got: $result, Expected: $expected"
+                sprintf('Failed for input: %s. Got: %s, Expected: %s', $input, $result, $expected)
             );
         }
     }
@@ Line 444 @@

         // Verify descending order by combined score
         $sorted = $combinedResults->values()->all();
-        for ($i = 0; $i < count($sorted) - 1; $i++) {
+        for ($i = 0; $i < count($sorted) - 1; ++$i) {
             $this->assertGreaterThanOrEqual($sorted[$i + 1]->combinedScore, $sorted[$i]->combinedScore);
         }
     }
@@ Line 451 @@

     /**
      * Create a search context for testing.
+     * @param SearchResultData[] $results
      */
     private function createSearchContext(
         string $queryString,
@@ Line 466 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 511 @@

     /**
      * Invoke a private method on an object.
+     * @param SearchResultData[]|SearchContext[]|float[]|Collection<int, (object{score: float, relevance: float} & stdClass)>[] $args
      */
     private function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
     {
@@ Line 518 @@
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
 * CreateStubOverCreateMockArgRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


93) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/ScoringStageTest.php:17

    ---------- begin diff ----------
@@ Line 17 @@
 use Fuzzy\ValueObjects\SearchQuery;
 use InvalidArgumentException;
 use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
-use ReflectionClass;
 use ReflectionMethod;
 use ReflectionProperty;
 use stdClass;
@@ Line 45 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 86 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $scoringEngine,
             indexDataArray: []
         );
@@ Line 142 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $scoringEngine,
             indexDataArray: []
         );
@@ Line 177 @@
         $query = SearchQuery::create(query: 'test', normalizer: $normalizer);
         $options = new SearchOptionsData();

-        $scoringEngine = $this->createMock(ScoringEngine::class);
+        $scoringEngine = $this->createStub(ScoringEngine::class);

         $context = new SearchContext(
             query: $query,
@@ Line 184 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $scoringEngine,
             indexDataArray: []
         );
@@ Line 229 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
             scoringEngine: $scoringEngine,
             indexDataArray: []
         );
    ----------- end diff -----------

Applied rules:
 * CreateStubOverCreateMockArgRector


94) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Stages/SortAndLimitStageTest.php:4

    ---------- begin diff ----------
@@ Line 4 @@

 namespace Fuzzy\Tests\Unit\Stages;

+use PHPUnit\Framework\MockObject\MockObject;
 use Fuzzy\Contracts\IndexRepositoryInterface;
-use Fuzzy\Contracts\SearchContextInterface;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use Fuzzy\Tests\TestCase;
 use Fuzzy\Stages\SortAndLimitStage;
@@ Line 42 @@

     /**
      * Create a search context for testing.
+     * @param SearchResultData[]|null[]|(SearchResultData & MockObject)[] $results
      */
     private function createSearchContext(
         string $queryString,
@@ Line 61 @@
             options: $options,
             normalizer: $normalizer,
             similarityCalculator: new SimilarityCalculator(),
-            indexBuilder: $this->createMock(IndexBuilder::class),
-            indexRepository: $this->createMock(IndexRepositoryInterface::class),
-            scoringEngine: $this->createMock(ScoringEngine::class),
+            indexBuilder: $this->createStub(IndexBuilder::class),
+            indexRepository: $this->createStub(IndexRepositoryInterface::class),
+            scoringEngine: $this->createStub(ScoringEngine::class),
             indexDataArray: []
         );

@@ Line 271 @@

     public function test_handle_maintains_scores_in_original_objects(): void
     {
-        $result1 = $this->createMock(SearchResultData::class);
+        $result1 = $this->createStub(SearchResultData::class);
         $result1->score = 0.7;
         $result1->matchedField = 'name';
         $result1->matchedValue = 'John Doe';

-        $result2 = $this->createMock(SearchResultData::class);
+        $result2 = $this->createStub(SearchResultData::class);
         $result2->score = 0.9;
         $result2->matchedField = 'email';
         $result2->matchedValue = 'john@example.com';
    ----------- end diff -----------

Applied rules:
 * CreateStubOverCreateMockArgRector
 * ClassMethodArrayDocblockParamFromLocalCallsRector


 [OK] 94 files would have been changed (dry-run) by Rector                                                              

