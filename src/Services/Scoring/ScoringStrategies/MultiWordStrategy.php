<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategy;

class MultiWordStrategy implements ScoringStrategy
{
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    public function supports(SearchContext $context, array $indexEntry): bool
    {
        return $context->hasMultipleWords();
    }

    public function calculate(SearchContext $context, array $indexEntry): float
    {
        // Cette stratégie délègue au ScoringEngine pour le calcul multi-mots
        // car il nécessite plusieurs entrées d'index
        return 0.0; // Le ScoringEngine gère le multi-mots différemment
    }

    public function getPriority(): int
    {
        return 80;
    }
}
