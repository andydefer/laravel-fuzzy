<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

class SortAndLimitStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        // Convertir les résultats en collection pour le tri
        $resultsCollection = collect($context->results)
            ->filter(function ($result) use ($context) {
                return $result !== null && $result->score >= $context->options->minScore;
            });

        // Trier par score décroissant
        $sortedResults = $resultsCollection->sortByDesc('score');

        // Limiter au nombre maximum de résultats
        $limitedResults = $sortedResults->take($context->options->maxResults);

        // Réinitialiser les clés
        $context->results = $limitedResults->values()->all();

        return $context->results;
    }
}
