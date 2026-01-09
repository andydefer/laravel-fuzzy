<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class WordMatchStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        foreach ($context->queryWords as $queryWord) {
            if (strlen($queryWord) >= 2 && isset($context->wordIndex[$queryWord])) {
                $matches = $context->wordIndex[$queryWord];

                foreach ($matches as $match) {
                    $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

                    if (isset($context->seen[$resultKey])) {
                        continue;
                    }

                    $score = 0.9 * $match['weight']; // Score élevé pour correspondance exacte de mot

                    $model = $context->getModelInstance($resultKey);
                    if ($model) {
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
