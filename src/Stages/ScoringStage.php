<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Enums\StageType;
use Fuzzy\Data\SearchResultData;
use Closure;

/**
 * Scoring stage that consolidates all score calculations for search results.
 *
 * Calculates and assigns scores to potential matches based on various factors
 * including field weights, similarity algorithms, and multi-word bonuses.
 */
class ScoringStage implements StageInterface
{
    /**
     * Priority for this stage (medium priority)
     */
    private const PRIORITY = 55;

    /**
     * Minimum score value
     */
    private const MIN_SCORE = 0.0;

    /**
     * Maximum score value
     */
    private const MAX_SCORE = 1.0;

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): StageType
    {
        return StageType::SCORING;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(SearchContextInterface $context, Closure $next): mixed
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
     * @param SearchContextInterface $context Search context with configuration and scoring engine
     * @param array<int, array> $matches Array of potential matches for a model
     * @return float Best score between 0.0 and 1.0
     */
    private function calculateBestScore(SearchContextInterface $context, array $matches): float
    {
        $bestScore = self::MIN_SCORE;

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
     * @throws \InvalidArgumentException If matches array is empty
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
     * @param SearchContextInterface $context Search context with query information
     * @param array<int, array> $matches Array of matches
     * @return bool True if multi-word bonus should be applied
     */
    private function shouldApplyMultiWordBonus(SearchContextInterface $context, array $matches): bool
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
        return min(max($score, self::MIN_SCORE), self::MAX_SCORE);
    }
}
