<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;

class ExactMatchStrategy implements ScoringStrategy
{
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    public function supports(SearchContext $context, array $indexEntry): bool
    {
        $normalizedQuery = $context->getNormalizedQuery();
        return $normalizedQuery === ($indexEntry['original_value'] ?? '');
    }

    public function calculate(SearchContext $context, array $indexEntry): float
    {
        $baseScore = 1.0 * ($indexEntry['weight'] ?? 1.0);

        return $this->advancedCalculator->calculateFinalScore(
            $baseScore,
            $indexEntry,
            $context,
            $context->getNormalizedQuery()
        );
    }

    public function getPriority(): int
    {
        return 100; // Priorité la plus haute
    }
}
