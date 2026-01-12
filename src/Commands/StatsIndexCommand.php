<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;

/**
 * Command to display search index statistics.
 *
 * Provides insights into indexed data including total entries
 * and per-model statistics.
 */
class StatsIndexCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected $signature = 'fuzzy:stats';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Show search index statistics';

    /**
     * Execute the console command.
     *
     * Displays comprehensive statistics about the search index,
     * including total entries and per-model breakdown with field counts.
     */
    public function handle(): void
    {
        $searchService = app('laravel-fuzzy.search');
        $stats = $searchService->getStats();

        $this->displayHeader();
        $this->displayTotalEntries($stats['total_entries']);
        $this->displayModelStatistics($stats['models']);
    }

    /**
     * Display the command header.
     */
    private function displayHeader(): void
    {
        $this->info('=== Search Index Statistics ===');
    }

    /**
     * Display total entries count.
     */
    private function displayTotalEntries(int $totalEntries): void
    {
        $this->info('Total entries: ' . $totalEntries);
        $this->newLine();
    }

    /**
     * Display statistics per model.
     */
    private function displayModelStatistics(array $modelsStats): void
    {
        $this->info('Per model statistics:');
        $this->newLine();

        if ($modelsStats === []) {
            $this->warn('No models indexed yet.');
            return;
        }

        $headers = ['Model', 'Entries', 'Fields'];
        $rows = $this->prepareModelRows($modelsStats);

        $this->table($headers, $rows);
    }

    /**
     * Prepare rows for the models statistics table.
     */
    private function prepareModelRows(array $modelsStats): array
    {
        $rows = [];

        foreach ($modelsStats as $model => $modelStats) {
            $fields = $this->formatFieldCounts($modelStats['fields']);

            $rows[] = [
                $model,
                $modelStats['count'],
                $fields ?: 'No fields indexed',
            ];
        }

        return $rows;
    }

    /**
     * Format field counts into a readable string.
     */
    private function formatFieldCounts(array $fields): string
    {
        return implode(', ', array_map(
            fn(string $field, int $count): string => sprintf('%s: %d', $field, $count),
            array_keys($fields),
            array_values($fields)
        ));
    }
}
