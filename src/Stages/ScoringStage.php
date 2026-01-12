<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use InvalidArgumentException;
use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

/**
 * Stage unique de scoring qui consolide tous les calculs de score.
 * Remplace UnifiedScoringStage, MultiWordConsolidationStage et les stratégies individuelles.
 */
class ScoringStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        // Traiter tous les matches potentiels
        foreach ($context->getAllPotentialMatches() as $key => $matches) {
            $model = $context->getModelInstance($key);
            if ($model === null) {
                continue;
            }

            // Calculer le meilleur score pour ce modèle
            $bestScore = $this->calculateBestScore($context, $matches);

            if ($bestScore >= $context->options->minScore) {
                $bestMatch = $this->findBestMatch($matches);

                $context->results[$key] = SearchResultData::create(
                    item: $model,
                    score: $bestScore,
                    modelType: $bestMatch['indexable_type'],
                    matchedField: $bestMatch['field'],
                    matchedValue: $bestMatch['original_value']
                );
            }
        }

        return $next($context);
    }

    /**
     * Calcule le meilleur score pour un ensemble de matches.
     */
    private function calculateBestScore(SearchContext $context, array $matches): float
    {
        $bestScore = 0.0;

        foreach ($matches as $match) {
            $score = $context->scoringEngine->calculateScore($context, $match);
            $bestScore = max($bestScore, $score);
        }

        // Appliquer les bonus multi-mots si nécessaire
        if ($context->hasMultipleWords() && count($matches) > 1) {
            $multiWordScore = $context->scoringEngine->calculateMultiWordScore($matches, $context);
            $bestScore = max($bestScore, $multiWordScore);
        }

        return min(max($bestScore, 0.0), 1.0);
    }

    /**
     * Trouve le meilleur match parmi une liste.
     * @param array<int, mixed> $matches
     */
    private function findBestMatch(array $matches): array
    {
        if ($matches === []) {
            throw new InvalidArgumentException('Matches array cannot be empty');
        }

        // Retourne le premier match (le détail du match spécifique
        // est moins important que le score global)
        return $matches[0];
    }
}
