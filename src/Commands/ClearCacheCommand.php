<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Clear fuzzy search cache command.
 *
 * Provides functionality to clear cached search results and statistics.
 * Supports selective cache invalidation by model or cache type.
 */
class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:clear-cache
                            {--force : Skip confirmation prompt}
                            {--model= : Clear cache for specific model only}
                            {--stats : Clear only statistics cache}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Clear fuzzy search cache';

    /**
     * Execute the console command.
     *
     * Determines the cache clearing strategy based on provided options
     * and delegates to appropriate handler methods.
     *
     * @return void
     */
    public function handle(): void
    {
        $shouldSkipConfirmation = $this->option('force');
        $targetModel = $this->option('model');
        $shouldClearStatsOnly = $this->option('stats');

        if (!$shouldSkipConfirmation && !$this->confirmClearCacheRequest()) {
            $this->info('Cache clearing cancelled.');
            return;
        }

        $searchService = app('laravel-fuzzy.search');

        if ($shouldClearStatsOnly) {
            $this->clearStatisticsCache($searchService);
        } elseif ($targetModel) {
            $this->clearCacheForSpecificModel($searchService, $targetModel);
        } else {
            $this->clearEntireCache($searchService);
        }
    }

    /**
     * Request confirmation from user before clearing cache.
     *
     * @return bool
     */
    private function confirmClearCacheRequest(): bool
    {
        return $this->confirm('Are you sure you want to clear fuzzy search cache?');
    }

    /**
     * Clear only statistics cache.
     *
     * Invalidates cache containing search statistics and usage metrics.
     *
     * @param mixed $searchService
     * @return void
     */
    private function clearStatisticsCache(mixed $searchService): void
    {
        if (method_exists($searchService, 'getStats')) {
            $cachePrefix = config('fuzzy.cache.prefix', 'fuzzy_search:');
            Cache::forget($cachePrefix . 'stats');

            $this->info('Statistics cache cleared successfully.');
        } else {
            $this->warn('Statistics cache clearing is not available.');
        }
    }

    /**
     * Clear cache for a specific model.
     *
     * Invalidates all cached search results for the given model class.
     *
     * @param mixed $searchService
     * @param string $modelClass
     * @return void
     */
    private function clearCacheForSpecificModel(mixed $searchService, string $modelClass): void
    {
        if (method_exists($searchService, 'invalidateCacheForModel')) {
            $searchService->invalidateCacheForModel($modelClass);

            $this->info(sprintf('Cache cleared for model: %s', $modelClass));
        } else {
            $this->warn('Model-specific cache clearing is not available.');
        }
    }

    /**
     * Clear entire fuzzy search cache.
     *
     * Invalidates all cached search results and statistics.
     *
     * @param mixed $searchService
     * @return void
     */
    private function clearEntireCache(mixed $searchService): void
    {
        if (method_exists($searchService, 'invalidateAllCache')) {
            $searchService->invalidateAllCache();

            $this->info('All fuzzy search cache cleared successfully.');
        } else {
            $this->warn('Cache service is not available.');
        }
    }
}
