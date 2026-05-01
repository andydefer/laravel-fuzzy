<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Traits\CommandHelpers;
use Illuminate\Console\Command;

/**
 * Displays comprehensive statistics about the fuzzy search index.
 *
 * Provides insights into indexed data including total entries,
 * per-model statistics, and field distribution.
 *
 * @package Fuzzy\Commands
 */
class StatsIndexCommand extends Command
{
    use CommandHelpers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show search index statistics';

    /**
     * Execute the console command.
     *
     * Retrieves and displays comprehensive statistics about the search index,
     * including total entries and per-model breakdown with field counts.
     *
     * @return void
     */
    public function handle(): void
    {
        $searchService = $this->getSearchService();
        $statistics = $searchService->getIndexManager()->getStats();

        $this->displayHeader();
        $this->displayTotalEntries($statistics['total_entries']);
        $this->displayModelStatistics($statistics['models']);
    }

    /**
     * Get the search service from the container.
     *
     * @return SearchServiceInterface
     */
    private function getSearchService(): SearchServiceInterface
    {
        return app(SearchServiceInterface::class);
    }

    /**
     * Display the command header.
     *
     * @return void
     */
    private function displayHeader(): void
    {
        $this->showHeader('Search Index Statistics');
    }

    /**
     * Display the total number of indexed entries.
     *
     * @param int $totalEntries The total number of indexed entries
     * @return void
     */
    private function displayTotalEntries(int $totalEntries): void
    {
        $this->showInfo('Total entries: ' . $totalEntries);
        $this->showNewLine();
    }

    /**
     * Display detailed statistics for each indexed model.
     *
     * Shows entry counts and field distributions per model in a tabular format.
     *
     * @param array<string, array{count: int, fields: array<string, int>}> $modelsStats
     * @return void
     */
    private function displayModelStatistics(array $modelsStats): void
    {
        $this->showInfo('Per model statistics:');
        $this->showNewLine();

        if (empty($modelsStats)) {
            $this->showWarning('No models indexed yet.');
            return;
        }

        $headers = ['Model', 'Entries', 'Fields'];
        $rows = $this->prepareModelRows($modelsStats);

        $this->table($headers, $rows);
    }

    /**
     * Prepare data rows for the models statistics table.
     *
     * @param array<string, array{count: int, fields: array<string, int>}> $modelsStats
     * @return array<array{string, int, string}>
     */
    private function prepareModelRows(array $modelsStats): array
    {
        $rows = [];

        foreach ($modelsStats as $modelClass => $modelData) {
            $formattedFields = $this->formatFieldCounts($modelData['fields']);

            $rows[] = [
                $modelClass,
                $modelData['count'],
                $formattedFields ?: 'No fields indexed',
            ];
        }

        return $rows;
    }

    /**
     * Format field counts into a readable string representation.
     *
     * Converts field statistics from array to human-readable format.
     * Example: ['name' => 100, 'email' => 50] becomes "name: 100, email: 50"
     *
     * @param array<string, int> $fieldCounts
     * @return string
     */
    private function formatFieldCounts(array $fieldCounts): string
    {
        if (empty($fieldCounts)) {
            return '';
        }

        $formattedParts = [];

        foreach ($fieldCounts as $fieldName => $count) {
            $formattedParts[] = "{$fieldName}: {$count}";
        }

        return implode(', ', $formattedParts);
    }
}
