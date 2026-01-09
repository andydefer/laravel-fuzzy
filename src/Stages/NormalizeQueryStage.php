<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

class NormalizeQueryStage
{
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
