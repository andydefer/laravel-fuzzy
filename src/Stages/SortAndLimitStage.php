<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;
use Illuminate\Support\Collection;

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
     * @param Closure $next Next stage in pipeline (not used in this terminal stage)
     * @return mixed Filtered, sorted, and limited results
     */
    public function handle(SearchContext $context, Closure $next)
    {
        $resultsCollection = collect($context->results)
            ->filter(fn($result) => $result !== null && $result->score >= $context->options->minScore);

        $sortedResults = $resultsCollection->sortByDesc('score');
        $limitedResults = $sortedResults->take($context->options->maxResults);

        $context->results = $limitedResults->values()->all();

        return $context->results;
    }
}
