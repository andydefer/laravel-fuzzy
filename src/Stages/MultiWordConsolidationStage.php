<?php

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

class MultiWordConsolidationStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        if (!$context->hasMultipleWords() || empty($context->results)) {
            return $next($context);
        }

        $advancedCalculator = app('laravel-fuzzy.advanced-scoring');

        foreach ($context->results as $key => $result) {
            $model = $context->getModelInstance($key);
            if (!$model) continue;

            $indexEntries = $context->getIndexEntriesForModel(
                $result->modelType,
                $model->getIndexableId()
            );

            if (!empty($indexEntries)) {
                // Calculer score multi-mots avancé
                $multiWordScore = $advancedCalculator->calculateMultiWordScore($indexEntries, $context);

                // Garder le meilleur score
                $result->score = max($result->score, $multiWordScore);
            }
        }

        return $next($context);
    }
}
