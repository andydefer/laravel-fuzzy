<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Configuration Value Object for the Relevance Scoring Stage.
 *
 * Encapsulates all configurable parameters for relevance scoring:
 * - Weight distribution between original and relevance scores
 * - Penalty values for missing or low-quality data
 * - Result limits for search operations
 * - Relevance score normalization parameters
 *
 * All values are immutable and loaded from Laravel configuration with sensible defaults.
 */
final class RelevanceScoringConfig implements ConfigInterface
{
    /** Default penalty value applied to low-quality or missing data. */
    private const DEFAULT_PENALTY = 10.0;

    /** Default maximum number of results when not specified in options. */
    private const DEFAULT_MAX_RESULTS = 20;

    /** Weight multiplier for the original similarity score (0.7 = 70%). */
    private const DEFAULT_ORIGINAL_SCORE_WEIGHT = 0.7;

    /** Weight multiplier for the enhanced relevance score (0.3 = 30%). */
    private const DEFAULT_RELEVANCE_SCORE_WEIGHT = 0.3;

    /** Maximum relevance value used for normalization scaling. */
    private const DEFAULT_MAX_NORMALIZED_RELEVANCE = 100.0;

    /** Minimum relevance value used for normalization scaling. */
    private const DEFAULT_MIN_NORMALIZED_RELEVANCE = 0.0;

    /** Factor for converting relevance points to normalized scores. */
    private const DEFAULT_NORMALIZATION_FACTOR = 10.0;

    public function __construct(
        private readonly float $penalty,
        private readonly int $defaultMaxResults,
        private readonly float $originalScoreWeight,
        private readonly float $relevanceScoreWeight,
        private readonly float $maxNormalizedRelevance,
        private readonly float $minNormalizedRelevance,
        private readonly float $normalizationFactor
    ) {}

    /**
     * Create an instance from Laravel configuration.
     *
     * Loads values from 'fuzzy.relevance_scoring' config key and merges with defaults.
     *
     * @return self Configured instance
     */
    public static function fromConfig(): self
    {
        $config = config('fuzzy.relevance_scoring', []);

        return new self(
            penalty: (float) ($config['penalty'] ?? self::DEFAULT_PENALTY),
            defaultMaxResults: (int) ($config['default_max_results'] ?? self::DEFAULT_MAX_RESULTS),
            originalScoreWeight: (float) ($config['original_score_weight'] ?? self::DEFAULT_ORIGINAL_SCORE_WEIGHT),
            relevanceScoreWeight: (float) ($config['relevance_score_weight'] ?? self::DEFAULT_RELEVANCE_SCORE_WEIGHT),
            maxNormalizedRelevance: (float) ($config['max_normalized_relevance'] ?? self::DEFAULT_MAX_NORMALIZED_RELEVANCE),
            minNormalizedRelevance: (float) ($config['min_normalized_relevance'] ?? self::DEFAULT_MIN_NORMALIZED_RELEVANCE),
            normalizationFactor: (float) ($config['normalization_factor'] ?? self::DEFAULT_NORMALIZATION_FACTOR)
        );
    }

    /**
     * Create a default instance with built-in values.
     *
     * Useful for testing or when no configuration is available.
     *
     * @return self Default configured instance
     */
    public static function createDefault(): self
    {
        return new self(
            penalty: self::DEFAULT_PENALTY,
            defaultMaxResults: self::DEFAULT_MAX_RESULTS,
            originalScoreWeight: self::DEFAULT_ORIGINAL_SCORE_WEIGHT,
            relevanceScoreWeight: self::DEFAULT_RELEVANCE_SCORE_WEIGHT,
            maxNormalizedRelevance: self::DEFAULT_MAX_NORMALIZED_RELEVANCE,
            minNormalizedRelevance: self::DEFAULT_MIN_NORMALIZED_RELEVANCE,
            normalizationFactor: self::DEFAULT_NORMALIZATION_FACTOR
        );
    }

    /**
     * Get the penalty value for missing or low-quality data.
     *
     * @return float Penalty amount to subtract from scores
     */
    public function getPenalty(): float
    {
        return $this->penalty;
    }

    /**
     * Get the default maximum number of search results.
     *
     * @return int Maximum results limit
     */
    public function getDefaultMaxResults(): int
    {
        return $this->defaultMaxResults;
    }

    /**
     * Get the weight multiplier for the original similarity score.
     *
     * @return float Weight between 0 and 1
     */
    public function getOriginalScoreWeight(): float
    {
        return $this->originalScoreWeight;
    }

    /**
     * Get the weight multiplier for the enhanced relevance score.
     *
     * @return float Weight between 0 and 1
     */
    public function getRelevanceScoreWeight(): float
    {
        return $this->relevanceScoreWeight;
    }

    /**
     * Get the maximum relevance value for normalization.
     *
     * @return float Upper bound for normalized relevance scores
     */
    public function getMaxNormalizedRelevance(): float
    {
        return $this->maxNormalizedRelevance;
    }

    /**
     * Get the minimum relevance value for normalization.
     *
     * @return float Lower bound for normalized relevance scores
     */
    public function getMinNormalizedRelevance(): float
    {
        return $this->minNormalizedRelevance;
    }

    /**
     * Get the factor for converting relevance points to normalized scores.
     *
     * @return float Multiplication factor for normalization
     */
    public function getNormalizationFactor(): float
    {
        return $this->normalizationFactor;
    }
}
