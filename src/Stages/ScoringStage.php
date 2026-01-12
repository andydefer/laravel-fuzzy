<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

/**
 * Scoring stage that consolidates all score calculations for search results.
 *
 * Replaces UnifiedScoringStage, MultiWordConsolidationStage and individual strategies.
 * Calculates and assigns scores to potential matches based on various factors
 * including field weights, similarity algorithms, and multi-word bonuses.
 */
class ScoringStage
{
    /**
     * Process the search context to calculate and assign scores to potential matches.
     *
     * @param SearchContext $context The search context containing queries and potential matches
     * @param Closure $next The next stage in the pipeline
     * @return mixed Result from the next pipeline stage
     */
    public function handle(SearchContext $context, Closure $next)
    {
        foreach ($context->getAllPotentialMatches() as $key => $matches) {
            $model = $context->getModelInstance($key);

            if ($model === null) {
                continue;
            }

            $bestScore = $this->calculateBestScore($context, $matches);

            if ($bestScore >= $context->options->minScore) {
                $bestMatch = $this->extractBestMatchDetails($matches);

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
     * Calculate the best possible score for a set of matches.
     *
     * @param SearchContext $context Search context with configuration and scoring engine
     * @param array<int, array> $matches Array of potential matches for a model
     * @return float Best score between 0.0 and 1.0
     */
    private function calculateBestScore(SearchContext $context, array $matches): float
    {
        $bestScore = 0.0;

        foreach ($matches as $match) {
            $score = $context->scoringEngine->calculateScore($context, $match);
            $bestScore = max($bestScore, $score);
        }

        if ($this->shouldApplyMultiWordBonus($context, $matches)) {
            $multiWordScore = $context->scoringEngine->calculateMultiWordScore($matches, $context);
            $bestScore = max($bestScore, $multiWordScore);
        }

        return $this->normalizeScore($bestScore);
    }

    /**
     * Extract details from the best match in the array.
     *
     * @param array<int, array> $matches Array of matches with field and value information
     * @return array{indexable_type: string, field: string, original_value: string} Match details
     */
    private function extractBestMatchDetails(array $matches): array
    {
        if ($matches === []) {
            throw new \InvalidArgumentException('Matches array cannot be empty');
        }

        return $matches[0];
    }

    /**
     * Determine if multi-word bonus should be applied.
     *
     * @param SearchContext $context Search context with query information
     * @param array<int, array> $matches Array of matches
     * @return bool True if multi-word bonus should be applied
     */
    private function shouldApplyMultiWordBonus(SearchContext $context, array $matches): bool
    {
        return $context->hasMultipleWords() && count($matches) > 1;
    }

    /**
     * Normalize score to ensure it stays within valid range.
     *
     * @param float $score Raw score value
     * @return float Normalized score between 0.0 and 1.0
     */
    private function normalizeScore(float $score): float
    {
        return min(max($score, 0.0), 1.0);
    }
}
