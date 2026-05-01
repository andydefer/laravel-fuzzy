<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Config\RelevanceScoringConfig;
use Fuzzy\Enums\StageType;
use Fuzzy\Services\Algorithms\WordSimilarityComparator;
use Illuminate\Support\Collection;
use Closure;

/**
 * Calculates and applies relevance scores to search results.
 *
 * Uses WordSimilarityComparator to calculate relevance between matched value
 * and search query, then sorts results by relevance.
 */
class RelevanceScoringStage implements StageInterface
{
    /**
     * Priority for this stage (medium-low priority)
     */
    private const PRIORITY = 45;

    private RelevanceScoringConfig $config;

    /**
     * Constructor.
     *
     * @param WordSimilarityComparator $comparator Word similarity comparator
     * @param RelevanceScoringConfig|null $config Relevance scoring configuration
     */
    public function __construct(
        private WordSimilarityComparator $comparator,
        ?RelevanceScoringConfig $config = null
    ) {
        $this->config = $config ?? RelevanceScoringConfig::fromConfig();
    }

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
        if (empty($context->results)) {
            return $next($context);
        }

        $scoredResults = $this->calculateRelevanceScores($context);
        $sortedResults = $this->sortByRelevance($scoredResults);
        $limitedResults = $this->applyMaxResultsLimit($sortedResults, $context);

        $context->results = $limitedResults;

        return $next($context);
    }

    /**
     * Calculate relevance scores for all results.
     *
     * @param SearchContextInterface $context Search context
     * @return Collection<int, object> Results with relevance scores
     */
    private function calculateRelevanceScores(SearchContextInterface $context): Collection
    {
        return collect($context->results)
            ->map(function (object $result) use ($context) {
                $relevance = $this->calculateRelevanceForResult($result, $context);
                $result->relevance = $relevance;
                return $result;
            });
    }

    /**
     * Calculate relevance score for a single result.
     *
     * @param object $result Search result
     * @param SearchContextInterface $context Search context
     * @return float Relevance score (lower = more relevant)
     */
    private function calculateRelevanceForResult(object $result, SearchContextInterface $context): float
    {
        $matchedValue = $result->matchedValue ?? '';
        $query = $context->query->originalQuery;

        if (empty($matchedValue) || empty($query)) {
            return $this->config->getPenalty();
        }

        return $this->comparator->compare($query, $matchedValue);
    }

    /**
     * Sort results by relevance (ascending - lower score = more relevant).
     *
     * @param Collection<int, object> $results Results with relevance scores
     * @return Collection<int, object> Sorted results
     */
    private function sortByRelevance(Collection $results): Collection
    {
        return $results->sortBy('relevance');
    }

    /**
     * Apply maximum results limit based on configuration.
     *
     * @param Collection<int, object> $results Sorted results
     * @param SearchContextInterface $context Search context
     * @return array<int, object> Limited results
     */
    private function applyMaxResultsLimit(Collection $results, SearchContextInterface $context): array
    {
        $maxResults = $context->options->maxResults ?? $this->config->getDefaultMaxResults();

        return $results
            ->take($maxResults)
            ->values()
            ->all();
    }

    /**
     * Combine relevance with original score for final ranking.
     *
     * @param Collection<int, object> $results Results with relevance scores
     * @return Collection<int, object> Results with combined scores
     */
    private function combineScores(Collection $results): Collection
    {
        return $results
            ->map(function (object $result) {
                $normalizedRelevance = $this->normalizeRelevance($result->relevance);
                $combinedScore = ($result->score * $this->config->getOriginalScoreWeight()) +
                    ($normalizedRelevance * $this->config->getRelevanceScoreWeight());

                $result->combinedScore = $combinedScore;
                $result->originalScore = $result->score;
                $result->relevanceScore = $normalizedRelevance;

                return $result;
            })
            ->sortByDesc('combinedScore');
    }

    /**
     * Normalize relevance score to 0-100 scale.
     *
     * @param float $relevance Relevance score from comparator
     * @return float Normalized score (100 = perfect, 0 = poor)
     */
    private function normalizeRelevance(float $relevance): float
    {
        if ($relevance <= FUZZY_DISTANCE_IDENTICAL) {
            return $this->config->getMaxNormalizedRelevance();
        }

        $normalized = max(
            $this->config->getMinNormalizedRelevance(),
            $this->config->getMaxNormalizedRelevance() - ($relevance * $this->config->getNormalizationFactor())
        );
        return min($this->config->getMaxNormalizedRelevance(), $normalized);
    }
}
