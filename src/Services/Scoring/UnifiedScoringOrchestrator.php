<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\ValueObjects\ScoredResult;

/**
 * Orchestrateur unifié de scoring qui combine stratégies et calculs avancés
 */
class UnifiedScoringOrchestrator
{
    private array $strategies = [];

    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {
        $this->initializeStrategies();
    }

    private function initializeStrategies(): void
    {
        $this->strategies = [
            new ExactMatchStrategy($this->advancedCalculator),
            new WordMatchStrategy($this->advancedCalculator),
            new FuzzyMatchStrategy($this->advancedCalculator),
            new MultiWordStrategy($this->advancedCalculator),
        ];

        // Tri par priorité (plus haute priorité en premier)
        usort($this->strategies, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
    }

    /**
     * Calcule le score optimal pour une entrée d'index
     */
    public function calculateOptimalScore(SearchContext $context, array $indexEntry): float
    {
        $bestScore = 0.0;

        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context, $indexEntry)) {
                $score = $strategy->calculate($context, $indexEntry);
                $bestScore = max($bestScore, $score);
            }
        }

        // Si aucune stratégie ne supporte, utiliser le fallback
        if ($bestScore === 0.0) {
            $bestScore = $this->calculateFallbackScore($context, $indexEntry);
        }

        return min(max($bestScore, 0.0), 1.0);
    }

    /**
     * Applique le scoring avancé (bonus/penalties) à un score de base
     */
    public function applyAdvancedScoring(
        float $baseScore,
        array $indexEntry,
        SearchContext $context,
        ?string $matchedWord = null
    ): float {
        return $this->advancedCalculator->calculateFinalScore(
            $baseScore,
            $indexEntry,
            $context,
            $matchedWord
        );
    }

    /**
     * Score de fallback basé sur la similarité simple
     */
    private function calculateFallbackScore(SearchContext $context, array $indexEntry): float
    {
        $query = $context->getNormalizedQuery();
        $value = $indexEntry['original_value'] ?? '';

        if (empty($value)) {
            return 0.0;
        }

        return $context->similarityCalculator->calculateWordSimilarity($query, $value);
    }

    /**
     * Score pour une requête multi-mots
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContext $context): float
    {
        return $this->advancedCalculator->calculateMultiWordScore($indexEntries, $context);
    }
}
