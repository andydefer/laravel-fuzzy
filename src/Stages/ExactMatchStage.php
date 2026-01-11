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
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        // Match de la requête complète
        if (isset($wordIndex[$normalizedQuery])) {
            $this->processMatches($context, $normalizedQuery, 1.0);
        }

        // Match des mots individuels
        foreach ($context->getQueryWords() as $queryWord) {
            if (isset($wordIndex[$queryWord])) {
                $this->processMatches($context, $queryWord, 0.95);
            }
        }

        return $next($context);
    }

    private function processMatches(SearchContext $context, string $word, float $baseMultiplier): void
    {
        foreach ($context->getWordIndex()[$word] as $match) {
            $key = $match['indexable_type'] . '_' . $match['indexable_id'];

            if (isset($context->seen[$key])) {
                continue;
            }

            $model = $context->getModelInstance($key);
            if (!$model) {
                continue;
            }

            // SCORE DE BASE SEULEMENT - le scoring avancé se fera dans UnifiedScoringStage
            $baseScore = $baseMultiplier * ($match['weight'] ?? 1.0);

            $context->results[$key] = SearchResultData::create(
                item: $model,
                score: $baseScore, // Score brut
                modelType: $match['indexable_type'],
                matchedField: $match['field'],
                matchedValue: $match['original_value']
            );
            $context->seen[$key] = true;
        }
    }
}
