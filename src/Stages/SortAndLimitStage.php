<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

class SortAndLimitStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        $sortedResults = $context->finalResults->sortByDesc('score')->values();
        $context->finalResults = $sortedResults->take($context->options->maxResults);

        return $context->finalResults;
    }
}
