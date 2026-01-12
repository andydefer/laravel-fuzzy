<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Illuminate\Support\Collection;
use Closure;

/**
 * Query normalization stage in the fuzzy search pipeline.
 *
 * This stage validates that the search query is not empty after normalization.
 * The actual query normalization is handled by the SearchQuery value object.
 */
class NormalizeQueryStage
{
    /**
     * Process the search context by validating the normalized query.
     *
     * @param SearchContext $context The search context containing user query and configuration
     * @param Closure $next The next stage in the pipeline
     *
     * @return Collection Empty collection if query is invalid, otherwise proceeds to next stage
     */
    public function handle(SearchContext $context, Closure $next)
    {
        if ($context->query->isEmpty()) {
            return new Collection();
        }

        return $next($context);
    }
}
