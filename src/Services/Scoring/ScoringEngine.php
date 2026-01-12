<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;

/**
 * Unified scoring engine that orchestrates all scoring strategies
 *
 * Calculates optimal relevance scores for search matches using multiple strategies.
 * Supports both single-word and multi-word queries with configurable weighting.
 */
class ScoringEngine
{
    /**
     * Scoring strategies sorted by priority
     *
     * @var array<ScoringStrategy>
     */
    private array $strategies;

    /**
     * Initialize scoring engine with available strategies
     *
     * @param ScoringStrategy ...$strategies Scoring strategies to use
     */
    public function __construct(ScoringStrategy ...$strategies)
    {
        $this->strategies = $strategies;

        // Sort strategies by descending priority
        usort($this->strategies, fn($a, $b): int => $b->getPriority() <=> $a->getPriority());
    }

    /**
     * Calculate optimal score for an index entry
     *
     * Iterates through available strategies to find the best matching score.
     * Falls back to basic similarity calculation if no strategy supports the entry.
     *
     * @param SearchContext $context Search context with query and options
     * @param array<string, mixed> $indexEntry Index entry data
     * @return float Normalized score between 0.0 and 1.0
     */
    public function calculateScore(SearchContext $context, array $indexEntry): float
    {
        $bestScore = 0.0;

        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context, $indexEntry)) {
                $score = $strategy->calculate($context, $indexEntry);
                $bestScore = max($bestScore, $score);

                // Stop early if perfect score achieved
                if ($bestScore >= 1.0) {
                    break;
                }
            }
        }

        // Use fallback if no strategy supports the entry
        if ($bestScore === 0.0) {
            $bestScore = $this->calculateFallbackScore($context, $indexEntry);
        }

        return min(max($bestScore, 0.0), 1.0);
    }

    /**
     * Calculate score for multi-word query across multiple index entries
     *
     * @param array<array<string, mixed>> $indexEntries Matching index entries
     * @param SearchContext $context Search context with query and options
     * @return float Normalized score between 0.0 and 1.0
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContext $context): float
    {
        if ($indexEntries === [] || !$context->hasMultipleWords()) {
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

        // Calculate average score with coverage bonus
        $averageScore = array_sum($wordScores) / count($wordScores);
        $coverage = count($wordScores) / count($queryWords);
        $coverageBonus = $this->calculateCoverageBonus($coverage);

        $finalScore = $averageScore * (1 + $coverage) + $coverageBonus;

        // Apply field weighting
        $firstEntry = reset($indexEntries);
        $finalScore = $this->applyFieldWeighting($finalScore, $firstEntry);

        return min(max($finalScore, 0.0), 1.0);
    }

    /**
     * Fallback score calculation based on basic similarity
     *
     * Used when no scoring strategy supports the index entry.
     *
     * @param SearchContext $context Search context with query and options
     * @param array<string, mixed> $indexEntry Index entry data
     * @return float Basic similarity score
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
     * Calculate coverage bonus for multi-word queries
     *
     * Provides bonus points based on how many query words are matched.
     *
     * @param float $coverage Coverage ratio (matched words / total query words)
     * @return float Bonus to add to the score
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
     * Apply field-specific weighting to score
     *
     * Adjusts score based on the importance of the matched field.
     *
     * @param float $score Original calculated score
     * @param array<string, mixed> $match Match data including field information
     * @return float Weighted score
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
