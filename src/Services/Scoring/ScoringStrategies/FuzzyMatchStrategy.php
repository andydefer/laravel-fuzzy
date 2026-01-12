<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring\ScoringStrategies;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;
use Fuzzy\Services\Scoring\ScoringStrategy;

class FuzzyMatchStrategy implements ScoringStrategy
{
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    public function supports(SearchContext $context, array $indexEntry): bool
    {
        return $context->options->fuzzy && !$this->isExactOrWordMatch($context, $indexEntry);
    }

    public function calculate(SearchContext $context, array $indexEntry): float
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $originalValue = $indexEntry['original_value'] ?? '';

        $similarity = $context->similarityCalculator->calculateWordSimilarity(
            $normalizedQuery,
            $originalValue
        );

        if ($similarity < $context->options->threshold) {
            return 0.0;
        }

        $baseScore = $similarity * ($indexEntry['weight'] ?? 1.0);

        return $this->advancedCalculator->calculateFinalScore(
            $baseScore,
            $indexEntry,
            $context
        );
    }

    public function getPriority(): int
    {
        return 70;
    }

    private function isExactOrWordMatch(SearchContext $context, array $indexEntry): bool
    {
        $exactStrategy = new ExactMatchStrategy($this->advancedCalculator);
        $wordStrategy = new WordMatchStrategy($this->advancedCalculator);
        if ($exactStrategy->supports($context, $indexEntry)) {
            return true;
        }

        return $wordStrategy->supports($context, $indexEntry);
    }
}
