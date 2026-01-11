<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;

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
        // Utilise directement la méthode multi-mots du calculator
        return $this->advancedCalculator->calculateMultiWordScore([$indexEntry], $context);
    }

    public function getPriority(): int
    {
        return 80;
    }
}
