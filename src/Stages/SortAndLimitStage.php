<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

class SortAndLimitStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        // Filter by minScore first, en s'assurant que le résultat n'est pas null
        $filteredResults = $context->finalResults
            ->filter(function ($result) use ($context) {
                return $result !== null && $result->score >= $context->options->minScore;
            });

        // Then sort by score descending
        $sortedResults = $filteredResults->sortByDesc('score')->values();

        // Finally limit to maxResults
        $context->finalResults = $sortedResults->take($context->options->maxResults);

        return $context->finalResults;
    }
}
