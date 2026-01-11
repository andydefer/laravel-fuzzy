<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Services\Scoring\UnifiedScoringOrchestrator;
use Closure;

/**
 * Stage unique de scoring qui remplace tous les autres stages de scoring
 */
class UnifiedScoringStage
{
    public function __construct(
        private UnifiedScoringOrchestrator $scoringOrchestrator
    ) {}

    public function handle(SearchContext $context, Closure $next)
    {
        // 1. Améliorer les scores des résultats existants
        $this->enhanceExistingResults($context);

        // 2. Découvrir de nouveaux résultats (multi-mots, correspondances partielles)
        $this->discoverNewResults($context);

        // 3. Filtrer par score minimum
        $this->filterByMinScore($context);

        return $next($context);
    }

    /**
     * Améliore les scores des résultats déjà trouvés
     */
    private function enhanceExistingResults(SearchContext $context): void
    {
        foreach ($context->results as $key => $result) {
            $model = $context->getModelInstance($key);
            if (!$model) {
                unset($context->results[$key]);
                continue;
            }

            $indexEntries = $context->getIndexEntriesForModel(
                $result->modelType,
                $model->getIndexableId()
            );

            if (empty($indexEntries)) {
                continue;
            }

            // Calculer le meilleur score possible pour ce modèle
            $bestScore = $result->score;

            foreach ($indexEntries as $indexEntry) {
                $score = $this->scoringOrchestrator->calculateOptimalScore($context, $indexEntry);
                $bestScore = max($bestScore, $score);
            }

            // Mettre à jour avec le score optimal
            $result->score = $bestScore;
        }
    }

    /**
     * Découvre de nouveaux résultats non trouvés par les stages précédents
     */
    private function discoverNewResults(SearchContext $context): void
    {
        // Seulement pour les requêtes multi-mots
        if (!$context->hasMultipleWords()) {
            return;
        }

        $wordIndex = $context->getWordIndex();
        $queryWords = $context->getQueryWords();

        // Pour chaque mot de la requête
        foreach ($queryWords as $queryWord) {
            if (!isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex[$queryWord] as $match) {
                $key = $match['indexable_type'] . '_' . $match['indexable_id'];

                // Ignorer si déjà traité
                if (isset($context->seen[$key]) || isset($context->results[$key])) {
                    continue;
                }

                $model = $context->getModelInstance($key);
                if (!$model) {
                    continue;
                }

                // Récupérer TOUTES les entrées d'index pour ce modèle
                $indexEntries = $context->getIndexEntriesForModel(
                    $match['indexable_type'],
                    $match['indexable_id']
                );

                if (empty($indexEntries)) {
                    continue;
                }

                // Calculer le score multi-mots
                $score = $this->scoringOrchestrator->calculateMultiWordScore($indexEntries, $context);

                if ($score >= $context->options->minScore) {
                    $context->results[$key] = SearchResultData::create(
                        item: $model,
                        score: $score,
                        modelType: $match['indexable_type'],
                        matchedField: $match['field'],
                        matchedValue: $match['original_value']
                    );
                    $context->seen[$key] = true;
                }
            }
        }
    }

    /**
     * Filtre les résultats par score minimum
     */
    private function filterByMinScore(SearchContext $context): void
    {
        $context->results = array_filter(
            $context->results,
            fn($result) => $result->score >= $context->options->minScore
        );
    }
}
