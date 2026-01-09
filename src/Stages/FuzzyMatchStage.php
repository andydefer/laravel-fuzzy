<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class FuzzyMatchStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        if (!$context->options->fuzzy || empty($context->queryWords)) {
            return $next($context);
        }

        foreach ($context->queryWords as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            $this->findFuzzyMatches($context, $queryWord);
        }

        return $next($context);
    }

    private function findFuzzyMatches(SearchContext $context, string $queryWord): void
    {
        foreach ($context->wordIndex as $indexedWord => $matches) {
            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                (string) $indexedWord
            );

            // Utiliser le seuil défini dans les options
            if ($similarity >= $context->options->threshold) {
                $this->processMatches($context, $matches, $similarity);
            }
        }
    }

    private function processMatches(SearchContext $context, array $matches, float $similarity): void
    {
        foreach ($matches as $match) {
            $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

            if (isset($context->seen[$resultKey])) {
                continue;
            }

            $model = $context->getModelInstance($resultKey);
            if ($model) {
                // Score basé sur la similarité et le poids du champ
                $baseScore = $similarity * ($match['weight'] ?? 1.0);

                $context->results[$resultKey] = new SearchResultData(
                    item: $model,
                    score: $baseScore,
                    modelType: $match['indexable_type'],
                    matchedField: $match['field'],
                    matchedValue: $match['original_value']
                );
                $context->seen[$resultKey] = true;
            }
        }
    }
}
