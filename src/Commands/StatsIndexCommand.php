<?php

declare(strict_types=1);

namespace Fuzzy\Commands;

use Illuminate\Console\Command;

class StatsIndexCommand extends Command
{
    protected $signature = 'fuzzy:stats';

    protected $description = 'Show search index statistics';

    public function handle()
    {
        $searchService = app('laravel-fuzzy.search');
        $stats = $searchService->getStats();

        $this->info('=== Search Index Statistics ===');
        $this->info("Total entries: {$stats['total_entries']}");
        $this->newLine();

        $this->info('Per model statistics:');
        $this->newLine();

        $headers = ['Model', 'Entries', 'Fields'];
        $rows = [];

        foreach ($stats['models'] as $model => $modelStats) {
            $fields = implode(', ', array_map(
                fn($field, $count) => "{$field}: {$count}",
                array_keys($modelStats['fields']),
                array_values($modelStats['fields'])
            ));

            $rows[] = [
                $model,
                $modelStats['count'],
                $fields ?: 'No fields indexed',
            ];
        }

        if (!empty($rows)) {
            $this->table($headers, $rows);
        } else {
            $this->warn('No models indexed yet.');
        }
    }
}
