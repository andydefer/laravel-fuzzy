<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class ExactMatchStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        // Exact match for complete query
        if (isset($context->wordIndex[$context->normalizedQuery])) {
            foreach ($context->wordIndex[$context->normalizedQuery] as $match) {
                $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

                if (!isset($context->seen[$resultKey])) {
                    $model = $context->getModelInstance($resultKey);

                    if ($model) {
                        $score = 1.0 * $match['weight'];

                        $context->results[$resultKey] = new SearchResultData(
                            item: $model,
                            score: $score,
                            modelType: $match['indexable_type'],
                            matchedField: $match['field'],
                            matchedValue: $match['original_value']
                        );
                        $context->seen[$resultKey] = true;
                    }
                }
            }
        }

        return $next($context);
    }
}
