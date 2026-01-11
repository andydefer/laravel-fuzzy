<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;
use Fuzzy\Services\AdvancedScoringCalculator;

class WordMatchStrategy implements ScoringStrategy
{
    public function __construct(
        private AdvancedScoringCalculator $advancedCalculator
    ) {}

    public function supports(SearchContext $context, array $indexEntry): bool
    {
        $queryWords = $context->getQueryWords();
        $targetWords = $indexEntry['normalized_words'] ?? [];

        foreach ($queryWords as $queryWord) {
            if (in_array($queryWord, $targetWords)) {
                return true;
            }
        }

        return false;
    }

    public function calculate(SearchContext $context, array $indexEntry): float
    {
        $bestScore = 0.0;
        $queryWords = $context->getQueryWords();
        $targetWords = $indexEntry['normalized_words'] ?? [];

        foreach ($queryWords as $queryWord) {
            if (in_array($queryWord, $targetWords)) {
                $baseScore = 0.9 * ($indexEntry['weight'] ?? 1.0);
                $score = $this->advancedCalculator->calculateFinalScore(
                    $baseScore,
                    $indexEntry,
                    $context,
                    $queryWord
                );
                $bestScore = max($bestScore, $score);
            }
        }

        return $bestScore;
    }

    public function getPriority(): int
    {
        return 90;
    }
}
