<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;

/**
 * Results sorting and limiting stage.
 *
 * This stage filters, sorts, and limits results according to score and quantity parameters.
 */
class SortAndLimitStage
{
    /**
     * Sorts results by score and limits them according to configured options.
     *
     * @param SearchContext $context Context containing results to process
     * @return mixed Filtered, sorted, and limited results
     */
    public function handle(SearchContext $context)
    {
        $resultsCollection = collect($context->results)
            ->filter(fn($result): bool => $result !== null && $result->score >= $context->options->minScore);

        $sortedResults = $resultsCollection->sortByDesc('score');
        $limitedResults = $sortedResults->take($context->options->maxResults);

        $context->results = $limitedResults->values()->all();

        return $context->results;
    }
}
