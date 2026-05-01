<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\Config\CoverageBonusConfig;
use Fuzzy\Contracts\ScoringEngineInterface;
use Fuzzy\Contracts\SearchContextInterface;

/**
 * Central scoring engine that calculates relevance scores for search matches using configurable strategies.
 * 
 * The engine supports multiple scoring strategies with priority-based ordering, multi-word query scoring
 * with coverage bonuses, field-specific weighting, and automatic fallback mechanisms. All scores are
 * normalized between FUZZY_SCORE_NONE and FUZZY_SCORE_IDENTICAL constants.
 */
class ScoringEngine implements ScoringEngineInterface
{
    /**
     * @var array<int, ScoringStrategyInterface> List of scoring strategies sorted by priority (highest first)
     */
    private array $strategies;

    /**
     * Configuration for coverage bonus calculations.
     */
    private readonly CoverageBonusConfig $coverageBonusConfig;

    /**
     * Initialize the scoring engine with one or more scoring strategies.
     * 
     * Strategies are automatically sorted by their priority in descending order to ensure
     * higher priority strategies are evaluated first for early termination optimization.
     *
     * @param ScoringStrategyInterface ...$strategies Variable list of scoring strategies
     */
    public function __construct(ScoringStrategyInterface ...$strategies)
    {
        $this->strategies = $this->sortStrategiesByPriority($strategies);
        $this->coverageBonusConfig = CoverageBonusConfig::fromConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function calculateScore(SearchContextInterface $context, array $indexEntry): float
    {
        $bestScore = FUZZY_SCORE_NONE;

        foreach ($this->strategies as $strategy) {
            if (!$strategy->supports($context, $indexEntry)) {
                continue;
            }

            $score = $strategy->calculate($context, $indexEntry);
            $bestScore = max($bestScore, $score);

            if ($this->isPerfectScore($bestScore)) {
                break;
            }
        }

        if ($this->hasNoMatch($bestScore)) {
            $bestScore = $this->calculateFallbackScore($context, $indexEntry);
        }

        return $this->normalizeScore($bestScore);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateMultiWordScore(array $indexEntries, SearchContextInterface $context): float
    {
        if ($this->cannotCalculateMultiWordScore($indexEntries, $context)) {
            return FUZZY_SCORE_NONE;
        }

        $queryWords = $context->getQueryWords();
        $matchedWordScores = $this->findBestScoresForEachQueryWord($indexEntries, $context, $queryWords);

        if ($this->noWordsMatched($matchedWordScores)) {
            return FUZZY_SCORE_NONE;
        }

        $averageScore = $this->calculateAverageScore($matchedWordScores);
        $coverageRatio = $this->calculateCoverageRatio($matchedWordScores, $queryWords);
        $coverageBonus = $this->getCoverageBonus($coverageRatio);

        $finalScore = $this->computeWeightedScore($averageScore, $coverageRatio, $coverageBonus);
        $finalScore = $this->applyFieldWeighting($finalScore, $indexEntries[0] ?? []);

        return $this->normalizeScore($finalScore);
    }

    /**
     * Sort scoring strategies by priority in descending order.
     *
     * @param array<int, ScoringStrategyInterface> $strategies
     * @return array<int, ScoringStrategyInterface>
     */
    private function sortStrategiesByPriority(array $strategies): array
    {
        usort(
            $strategies,
            static fn(ScoringStrategyInterface $a, ScoringStrategyInterface $b): int =>
            $b->getPriority() <=> $a->getPriority()
        );

        return $strategies;
    }

    /**
     * Check if the score has reached the maximum possible value.
     *
     * @param float $score
     * @return bool
     */
    private function isPerfectScore(float $score): bool
    {
        return $score >= FUZZY_SCORE_IDENTICAL;
    }

    /**
     * Check if no scoring strategy matched the index entry.
     *
     * @param float $score
     * @return bool
     */
    private function hasNoMatch(float $score): bool
    {
        return $score === FUZZY_SCORE_NONE;
    }

    /**
     * Check if multi-word scoring can be performed.
     *
     * @param array<int, array<string, mixed>> $indexEntries
     * @param SearchContextInterface $context
     * @return bool
     */
    private function cannotCalculateMultiWordScore(array $indexEntries, SearchContextInterface $context): bool
    {
        return $indexEntries === [] || !$context->hasMultipleWords();
    }

    /**
     * Find the best similarity score for each query word against all index entries.
     *
     * @param array<int, array<string, mixed>> $indexEntries
     * @param SearchContextInterface $context
     * @param array<int, string> $queryWords
     * @return array<int, float>
     */
    private function findBestScoresForEachQueryWord(array $indexEntries, SearchContextInterface $context, array $queryWords): array
    {
        $matchedScores = [];

        foreach ($queryWords as $queryWord) {
            $bestWordScore = $this->findBestMatchingScoreForWord($indexEntries, $context, $queryWord);

            if ($this->isWordMatched($bestWordScore)) {
                $matchedScores[] = $bestWordScore;
            }
        }

        return $matchedScores;
    }

    /**
     * Find the best similarity score for a single query word against all index entries.
     *
     * @param array<int, array<string, mixed>> $indexEntries
     * @param SearchContextInterface $context
     * @param string $queryWord
     * @return float
     */
    private function findBestMatchingScoreForWord(array $indexEntries, SearchContextInterface $context, string $queryWord): float
    {
        $bestScore = FUZZY_SCORE_NONE;

        foreach ($indexEntries as $indexEntry) {
            $targetWords = $indexEntry['normalized_words'] ?? [];

            foreach ($targetWords as $targetWord) {
                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    (string) $targetWord
                );

                if ($this->isScoreAboveThreshold($similarity, $context)) {
                    $bestScore = max($bestScore, $similarity);
                }
            }
        }

        return $bestScore;
    }

    /**
     * Check if a similarity score meets or exceeds the configured threshold.
     *
     * @param float $similarity
     * @param SearchContextInterface $context
     * @return bool
     */
    private function isScoreAboveThreshold(float $similarity, SearchContextInterface $context): bool
    {
        return $similarity >= $context->options->threshold;
    }

    /**
     * Check if a word was successfully matched (score > minimum).
     *
     * @param float $score
     * @return bool
     */
    private function isWordMatched(float $score): bool
    {
        return $score > FUZZY_SCORE_NONE;
    }

    /**
     * Check if no query words were matched against any index entry.
     *
     * @param array<int, float> $matchedScores
     * @return bool
     */
    private function noWordsMatched(array $matchedScores): bool
    {
        return $matchedScores === [];
    }

    /**
     * Calculate the average score of all matched query words.
     *
     * @param array<int, float> $matchedScores
     * @return float
     */
    private function calculateAverageScore(array $matchedScores): float
    {
        return array_sum($matchedScores) / count($matchedScores);
    }

    /**
     * Calculate the ratio of matched words to total query words.
     *
     * @param array<int, float> $matchedScores
     * @param array<int, string> $queryWords
     * @return float
     */
    private function calculateCoverageRatio(array $matchedScores, array $queryWords): float
    {
        return count($matchedScores) / count($queryWords);
    }

    /**
     * Get coverage bonus based on the coverage ratio using configured thresholds.
     *
     * @param float $coverageRatio Value between 0 and 1
     * @return float
     */
    private function getCoverageBonus(float $coverageRatio): float
    {
        $config = $this->coverageBonusConfig;

        if ($coverageRatio >= $config->getFullCoverageThreshold()) {
            return $config->getFullCoverageBonus();
        }

        if ($coverageRatio >= $config->getHighCoverageThreshold()) {
            return $config->getHighCoverageBonus();
        }

        return FUZZY_SCORE_NONE;
    }

    /**
     * Compute the final weighted score using average, coverage, and bonus.
     *
     * @param float $averageScore
     * @param float $coverageRatio
     * @param float $coverageBonus
     * @return float
     */
    private function computeWeightedScore(float $averageScore, float $coverageRatio, float $coverageBonus): float
    {
        return $averageScore * (FUZZY_BASE_FACTOR + $coverageRatio) + $coverageBonus;
    }

    /**
     * Calculate fallback score when no strategy matches the index entry.
     *
     * @param SearchContextInterface $context
     * @param array<string, mixed> $indexEntry
     * @return float
     */
    private function calculateFallbackScore(SearchContextInterface $context, array $indexEntry): float
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $originalValue = $indexEntry['original_value'] ?? '';

        if ($originalValue === '') {
            return FUZZY_SCORE_NONE;
        }

        return $context->similarityCalculator->calculateWordSimilarity($normalizedQuery, $originalValue);
    }

    /**
     * Apply field-specific weighting to the calculated score.
     *
     * @param float $score
     * @param array<string, mixed> $match
     * @return float
     */
    private function applyFieldWeighting(float $score, array $match): float
    {
        $fieldWeights = config('fuzzy.scoring.field_weights', $this->getDefaultFieldWeights());

        $field = $match['field'] ?? 'default';
        $weight = $fieldWeights[$field] ?? $fieldWeights['default'];

        return $score * $weight;
    }

    /**
     * Get default field weights configuration.
     *
     * @return array<string, float>
     */
    private function getDefaultFieldWeights(): array
    {
        return [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ];
    }

    /**
     * Normalize score to ensure it stays within allowed bounds.
     *
     * @param float $score
     * @return float
     */
    private function normalizeScore(float $score): float
    {
        return min(max($score, FUZZY_SCORE_NONE), FUZZY_SCORE_IDENTICAL);
    }
}
