<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Command to clear fuzzy search cache.
 */
class ClearCacheCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected $signature = 'fuzzy:clear-cache
                            {--force : Skip confirmation}
                            {--model= : Clear cache for specific model only}
                            {--stats : Clear only stats cache}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Clear fuzzy search cache';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $force = $this->option('force');
        $model = $this->option('model');
        $statsOnly = $this->option('stats');

        if (!$force && !$this->confirm('Clear fuzzy search cache?')) {
            return;
        }

        $searchService = app('laravel-fuzzy.search');

        if ($statsOnly) {
            $this->clearStatsCache($searchService);
        } elseif ($model) {
            $this->clearModelCache($searchService, $model);
        } else {
            $this->clearAllCache($searchService);
        }
    }

    /**
     * Clear only stats cache.
     */
    private function clearStatsCache($searchService): void
    {
        if (method_exists($searchService, 'getStats')) {
            // Invalider le cache stats
            $prefix = config('fuzzy.cache.prefix', 'fuzzy_search:');
            Cache::forget($prefix . 'stats');
            $this->info('✓ Stats cache cleared');
        } else {
            $this->warn('Stats cache clearing not available');
        }
    }

    /**
     * Clear cache for specific model.
     */
    private function clearModelCache($searchService, string $modelClass): void
    {
        if (method_exists($searchService, 'invalidateCacheForModel')) {
            $searchService->invalidateCacheForModel($modelClass);
            $this->info('✓ Cache cleared for model: ' . $modelClass);
        } else {
            $this->warn('Model-specific cache clearing not available');
        }
    }

    /**
     * Clear all fuzzy search cache.
     */
    private function clearAllCache($searchService): void
    {
        if (method_exists($searchService, 'invalidateAllCache')) {
            $searchService->invalidateAllCache();
            $this->info('✓ All fuzzy search cache cleared');
        } else {
            $this->warn('Cache service not available');
        }
    }
}
