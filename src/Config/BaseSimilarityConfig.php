<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Base configuration class for similarity algorithms.
 *
 * Provides shared configuration parameters used across multiple
 * similarity algorithms including containment detection, coverage bonuses,
 * and algorithm weights.
 *
 * @package Fuzzy\Config
 */
abstract class BaseSimilarityConfig implements ConfigInterface
{
    // ============================================
    // Shared Constants
    // ============================================

    /** Minimum query length required for similarity calculation. */
    public const DEFAULT_MIN_QUERY_LENGTH = 2;

    /** Weight for Longest Common Substring algorithm (0.4 = 40%). */
    public const DEFAULT_LONGEST_COMMON_SUBSTRING_WEIGHT = 0.4;

    /** Weight for Levenshtein distance algorithm (0.3 = 30%). */
    public const DEFAULT_LEVENSHTEIN_WEIGHT = 0.3;

    /** Weight for prefix matching algorithm (0.2 = 20%). */
    public const DEFAULT_PREFIX_WEIGHT = 0.2;

    /** Threshold for applying coverage bonus (50% coverage). */
    public const DEFAULT_COVERAGE_BONUS_THRESHOLD = 0.5;

    /** Multiplier for coverage bonus when threshold is met (0.15 = +15%). */
    public const DEFAULT_COVERAGE_BONUS_MULTIPLIER = 0.15;

    /** Multiplier for low coverage penalty (1.5). */
    public const DEFAULT_LOW_COVERAGE_MULTIPLIER = 1.5;

    /** Ratio threshold for high containment detection (80%). */
    public const DEFAULT_CONTAINMENT_HIGH_RATIO = 0.8;

    /** High score when query is contained in target (0.95 = 95%). */
    public const DEFAULT_CONTAINMENT_QUERY_IN_TARGET_HIGH_SCORE = 0.95;

    /** High score when target is contained in query (0.9 = 90%). */
    public const DEFAULT_CONTAINMENT_TARGET_IN_QUERY_HIGH_SCORE = 0.9;

    /** Base score for query-in-target containment (0.75). */
    public const DEFAULT_CONTAINMENT_BASE_SCORE_QUERY_IN_TARGET = 0.75;

    /** Base score for target-in-query containment (0.65). */
    public const DEFAULT_CONTAINMENT_BASE_SCORE_TARGET_IN_QUERY = 0.65;

    /** Multiplier for query-in-target score adjustment (0.2). */
    public const DEFAULT_CONTAINMENT_MULTIPLIER_QUERY_IN_TARGET = 0.2;

    /** Multiplier for target-in-query score adjustment (0.25). */
    public const DEFAULT_CONTAINMENT_MULTIPLIER_TARGET_IN_QUERY = 0.25;

    /** Maximum score cap for query-in-target matches (0.9). */
    public const DEFAULT_CONTAINMENT_MAX_SCORE_QUERY_IN_TARGET = 0.9;

    /** Maximum score cap for target-in-query matches (0.85). */
    public const DEFAULT_CONTAINMENT_MAX_SCORE_TARGET_IN_QUERY = 0.85;

    /** High containment ratio threshold (80%). */
    public const DEFAULT_CONTAINMENT_RATIO_HIGH = 0.8;

    /** Medium containment ratio threshold (50%). */
    public const DEFAULT_CONTAINMENT_RATIO_MEDIUM = 0.5;

    /** Multiplier for high containment matches (1.8). */
    public const DEFAULT_CONTAINMENT_HIGH_MULTIPLIER = 1.8;

    /** Multiplier for medium containment matches (2.5). */
    public const DEFAULT_CONTAINMENT_MEDIUM_MULTIPLIER = 2.5;

    /**
     * Constructor for base similarity configuration.
     */
    protected function __construct(
        protected int $minQueryLength,
        protected float $longestCommonSubstringWeight,
        protected float $levenshteinWeight,
        protected float $prefixWeight,
        protected float $coverageBonusThreshold,
        protected float $coverageBonusMultiplier,
        protected float $lowCoverageMultiplier,
        protected float $containmentHighRatio,
        protected float $containmentQueryInTargetHighScore,
        protected float $containmentTargetInQueryHighScore,
        protected float $containmentBaseScoreQueryInTarget,
        protected float $containmentBaseScoreTargetInQuery,
        protected float $containmentMultiplierQueryInTarget,
        protected float $containmentMultiplierTargetInQuery,
        protected float $containmentMaxScoreQueryInTarget,
        protected float $containmentMaxScoreTargetInQuery,
        protected float $containmentRatioHigh,
        protected float $containmentRatioMedium,
        protected float $containmentHighMultiplier,
        protected float $containmentMediumMultiplier
    ) {}

    // ============================================
    // Shared Getters
    // ============================================

    final public function getMinQueryLength(): int
    {
        return $this->minQueryLength;
    }

    final public function getLongestCommonSubstringWeight(): float
    {
        return $this->longestCommonSubstringWeight;
    }

    final public function getLevenshteinWeight(): float
    {
        return $this->levenshteinWeight;
    }

    final public function getPrefixWeight(): float
    {
        return $this->prefixWeight;
    }

    final public function getCoverageBonusThreshold(): float
    {
        return $this->coverageBonusThreshold;
    }

    final public function getCoverageBonusMultiplier(): float
    {
        return $this->coverageBonusMultiplier;
    }

    final public function getLowCoverageMultiplier(): float
    {
        return $this->lowCoverageMultiplier;
    }

    final public function getContainmentHighRatio(): float
    {
        return $this->containmentHighRatio;
    }

    final public function getContainmentQueryInTargetHighScore(): float
    {
        return $this->containmentQueryInTargetHighScore;
    }

    final public function getContainmentTargetInQueryHighScore(): float
    {
        return $this->containmentTargetInQueryHighScore;
    }

    final public function getContainmentBaseScoreQueryInTarget(): float
    {
        return $this->containmentBaseScoreQueryInTarget;
    }

    final public function getContainmentBaseScoreTargetInQuery(): float
    {
        return $this->containmentBaseScoreTargetInQuery;
    }

    final public function getContainmentMultiplierQueryInTarget(): float
    {
        return $this->containmentMultiplierQueryInTarget;
    }

    final public function getContainmentMultiplierTargetInQuery(): float
    {
        return $this->containmentMultiplierTargetInQuery;
    }

    final public function getContainmentMaxScoreQueryInTarget(): float
    {
        return $this->containmentMaxScoreQueryInTarget;
    }

    final public function getContainmentMaxScoreTargetInQuery(): float
    {
        return $this->containmentMaxScoreTargetInQuery;
    }

    final public function getContainmentRatioHigh(): float
    {
        return $this->containmentRatioHigh;
    }

    final public function getContainmentRatioMedium(): float
    {
        return $this->containmentRatioMedium;
    }

    final public function getContainmentHighMultiplier(): float
    {
        return $this->containmentHighMultiplier;
    }

    final public function getContainmentMediumMultiplier(): float
    {
        return $this->containmentMediumMultiplier;
    }
}
