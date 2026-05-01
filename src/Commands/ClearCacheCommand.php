<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Traits\CommandHelpers;
use Illuminate\Console\Command;

/**
 * Clear fuzzy search cache command.
 *
 * Provides functionality to clear cached search results and statistics.
 * Supports selective cache invalidation by model or cache type.
 *
 * @package Fuzzy\Commands
 */
class ClearCacheCommand extends Command
{
    use CommandHelpers;

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
     * @return void
     */
    public function handle(): void
    {
        $shouldSkipConfirmation = $this->option('force');
        $targetModel = $this->option('model');
        $shouldClearStatsOnly = $this->option('stats');

        if (!$this->confirmAction('Are you sure you want to clear fuzzy search cache?', $shouldSkipConfirmation)) {
            $this->showInfo('Cache clearing cancelled.');
            return;
        }

        if ($shouldClearStatsOnly) {
            $this->clearStatisticsCache();
        } elseif ($targetModel) {
            $this->clearCacheForSpecificModel($targetModel);
        } else {
            $this->clearEntireCache();
        }
    }

    /**
     * Clear only statistics cache.
     *
     * @return void
     */
    private function clearStatisticsCache(): void
    {
        $this->getSearchService()->getCacheManager()->invalidateStatsCache();
        $this->showSuccess('Statistics cache cleared successfully.');
    }

    /**
     * Clear cache for a specific model.
     *
     * @param string $modelClass The model class to clear cache for
     * @return void
     */
    private function clearCacheForSpecificModel(string $modelClass): void
    {
        $this->getSearchService()->getCacheManager()->invalidateForModel($modelClass);
        $this->showSuccess(sprintf('Cache cleared for model: %s', $modelClass));
    }

    /**
     * Clear entire fuzzy search cache.
     *
     * @return void
     */
    private function clearEntireCache(): void
    {
        $this->getSearchService()->getCacheManager()->invalidateAll();
        $this->showSuccess('All fuzzy search cache cleared successfully.');
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
}
