<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

/**
 * Stage de découverte de matches qui fusionne les 4 anciens stages :
 * - ExactMatchStage
 * - WordMatchStage
 * - FuzzyMatchStage
 * - La logique de découverte de UnifiedScoringStage
 */
class MatchDiscoveryStage
{
    public function handle(SearchContext $context, Closure $next)
    {
        if ($context->query->isEmpty()) {
            return $next($context);
        }

        // 1. Recherche exacte (mot complet)
        $this->discoverExactMatches($context);

        // 2. Recherche par mot
        $this->discoverWordMatches($context);

        // 3. Recherche floue (si activée)
        if ($context->options->fuzzy) {
            $this->discoverFuzzyMatches($context);
        }

        // 4. Découverte multi-mots (si nécessaire)
        if ($context->hasMultipleWords()) {
            $this->discoverMultiWordMatches($context);
        }

        return $next($context);
    }

    /**
     * Découvre les matches exacts (requête complète).
     */
    private function discoverExactMatches(SearchContext $context): void
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        if (isset($wordIndex[$normalizedQuery])) {
            foreach ($wordIndex[$normalizedQuery] as $match) {
                $context->addPotentialMatch($match);
            }
        }
    }

    /**
     * Découvre les matches par mot individuel.
     */
    private function discoverWordMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();

        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) >= 2 && isset($wordIndex[$queryWord])) {
                foreach ($wordIndex[$queryWord] as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Découvre les matches flous.
     */
    private function discoverFuzzyMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();

        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            foreach ($wordIndex as $indexedWord => $matches) {
                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    (string) $indexedWord
                );

                if ($similarity >= $context->options->threshold) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Découvre les matches multi-mots additionnels.
     */
    private function discoverMultiWordMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();
        $queryWords = $context->getQueryWords();

        foreach ($queryWords as $queryWord) {
            if (!isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex[$queryWord] as $match) {
                $key = $match['indexable_type'] . '_' . $match['indexable_id'];

                // Ignorer si déjà découvert par les autres méthodes
                if ($context->hasPotentialMatches($key)) {
                    continue;
                }

                $context->addPotentialMatch($match);
            }
        }
    }
}
