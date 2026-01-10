<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;
use Illuminate\Support\Collection;

/**
 * Query normalization stage.
 *
 * This stage normalizes the user query, splits it into words,
 * and validates it before proceeding with the search pipeline.
 */
class NormalizeQueryStage
{
    /**
     * Processes the search context by normalizing the query.
     *
     * @param SearchContext $context Search context containing user query
     * @param Closure $next Next stage in the pipeline
     * @return mixed Empty collection if invalid query, otherwise continues pipeline
     */
    public function handle(SearchContext $context, Closure $next)
    {
        $context->normalizedQuery = $context->normalizer->normalizeQuery($context->query);

        if (empty($context->normalizedQuery) || strlen($context->normalizedQuery) < 1) {
            return collect();
        }

        $context->queryWords = $context->normalizer->splitIntoWords($context->normalizedQuery);
        $context->hasMultipleWords = count($context->queryWords) > 1;

        return $next($context);
    }
}
