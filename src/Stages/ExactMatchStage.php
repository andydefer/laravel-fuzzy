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
        // 1. Exact match for complete normalized query (phrase complète)
        if (!empty($context->normalizedQuery) && isset($context->wordIndex[$context->normalizedQuery])) {
            foreach ($context->wordIndex[$context->normalizedQuery] as $match) {
                $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

                if (!isset($context->seen[$resultKey])) {
                    $model = $context->getModelInstance($resultKey);

                    if ($model) {
                        $score = 1.0 * $match['weight'];

                        // NE PAS filtrer par minScore ici - laisser SortAndLimitStage le faire
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

        // 2. Also check if any indexed value matches the complete normalized query
        $this->checkPhraseMatch($context);

        // 3. Also check for exact matches of individual words
        foreach ($context->queryWords as $queryWord) {
            if (isset($context->wordIndex[$queryWord])) {
                foreach ($context->wordIndex[$queryWord] as $match) {
                    $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

                    if (!isset($context->seen[$resultKey])) {
                        $model = $context->getModelInstance($resultKey);

                        if ($model) {
                            $score = 0.9 * $match['weight']; // Slightly less than full query exact match

                            // NE PAS filtrer par minScore ici - laisser SortAndLimitStage le faire
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
        }

        return $next($context);
    }

    /**
     * Check if any indexed value matches the complete normalized query (phrase match)
     */
    private function checkPhraseMatch(SearchContext $context): void
    {
        if (empty($context->normalizedQuery) || count($context->queryWords) < 2) {
            return; // Pas une phrase ou phrase trop courte
        }

        // Parcourir toutes les entrées d'index
        foreach ($context->wordIndex as $word => $matches) {
            foreach ($matches as $match) {
                $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

                if (isset($context->seen[$resultKey])) {
                    continue; // Déjà traité
                }

                // Vérifier si la valeur originale correspond à la requête normalisée
                $originalValue = strtolower($match['original_value'] ?? '');
                $normalizedOriginal = $this->normalizeForComparison($originalValue);
                $normalizedQuery = $context->normalizedQuery;

                if ($normalizedOriginal === $normalizedQuery) {
                    // Correspondance exacte de phrase !
                    $model = $context->getModelInstance($resultKey);

                    if ($model) {
                        $score = 1.0 * $match['weight']; // Score parfait pour phrase exacte

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
    }

    /**
     * Normalize string for comparison (same as StringNormalizer)
     */
    private function normalizeForComparison(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        return (string) \Illuminate\Support\Str::of($str)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s_-]/', '')
            ->replaceMatches('/\s+/', ' ')
            ->toString();
    }
}
