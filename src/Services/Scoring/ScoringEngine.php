<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;

/**
 * Moteur de scoring unifié qui orchestre toutes les stratégies.
 */
class ScoringEngine
{
    /**
     * @var ScoringStrategy[] Stratégies de scoring triées par priorité
     */
    private array $strategies;

    public function __construct(
        ScoringStrategy ...$strategies
    ) {
        $this->strategies = $strategies;

        // Trier les stratégies par priorité décroissante
        usort($this->strategies, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
    }

    /**
     * Calcule le score optimal pour une entrée d'index.
     */
    public function calculateScore(SearchContext $context, array $indexEntry): float
    {
        $bestScore = 0.0;

        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context, $indexEntry)) {
                $score = $strategy->calculate($context, $indexEntry);
                $bestScore = max($bestScore, $score);

                // Si on a un score parfait, on peut arrêter
                if ($bestScore >= 1.0) {
                    break;
                }
            }
        }

        // Si aucune stratégie ne supporte, utiliser le fallback
        if ($bestScore === 0.0) {
            $bestScore = $this->calculateFallbackScore($context, $indexEntry);
        }

        return min(max($bestScore, 0.0), 1.0);
    }

    /**
     * Score pour une requête multi-mots.
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContext $context): float
    {
        if (empty($indexEntries) || !$context->hasMultipleWords()) {
            return 0.0;
        }

        $queryWords = $context->getQueryWords();
        $wordScores = [];

        foreach ($queryWords as $queryWord) {
            $bestWordScore = 0.0;

            foreach ($indexEntries as $indexEntry) {
                $targetWords = $indexEntry['normalized_words'] ?? [];

                foreach ($targetWords as $targetWord) {
                    $similarity = $context->similarityCalculator->calculateWordSimilarity(
                        $queryWord,
                        (string) $targetWord
                    );

                    if ($similarity >= $context->options->threshold) {
                        $bestWordScore = max($bestWordScore, $similarity);
                    }
                }
            }

            if ($bestWordScore > 0) {
                $wordScores[] = $bestWordScore;
            }
        }

        if (empty($wordScores)) {
            return 0.0;
        }

        // Score moyen avec bonus de couverture
        $averageScore = array_sum($wordScores) / count($wordScores);
        $coverage = count($wordScores) / count($queryWords);
        $coverageBonus = $this->calculateCoverageBonus($coverage);

        $finalScore = $averageScore * (1 + $coverage) + $coverageBonus;

        // Appliquer la pondération du champ
        $firstEntry = reset($indexEntries);
        $finalScore = $this->applyFieldWeighting($finalScore, $firstEntry);

        return min(max($finalScore, 0.0), 1.0);
    }

    /**
     * Score de fallback basé sur la similarité simple.
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
     * Calcule le bonus de couverture pour les requêtes multi-mots.
     */
    private function calculateCoverageBonus(float $coverage): float
    {
        if ($coverage === 1.0) {
            return config('fuzzy.scoring.bonuses.full_coverage', 0.3);
        }

        if ($coverage >= 0.75) {
            return config('fuzzy.scoring.bonuses.high_coverage', 0.15);
        }

        return 0.0;
    }

    /**
     * Applique la pondération du champ.
     */
    private function applyFieldWeighting(float $score, array $match): float
    {
        $fieldWeights = config('fuzzy.scoring.field_weights', [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ]);

        $fieldWeight = $fieldWeights[$match['field']] ?? $fieldWeights['default'];
        return $score * $fieldWeight;
    }
}
