<?php

declare(strict_types=1);

namespace Fuzzy\Config;

/**
 * Configuration for PrefixSimilarityAlgorithm.
 *
 * Contains all parameters specific to prefix-based similarity matching
 * including minimum prefix length, base scores, and scoring multipliers.
 *
 * @package Fuzzy\Config
 */
class PrefixAlgorithmConfig extends BaseSimilarityConfig
{
    /** Minimum prefix length for prefix matching (3 characters). */
    public const DEFAULT_MIN_PREFIX_LENGTH = 3;

    /** Base score for prefix matches (0.4). */
    public const DEFAULT_PREFIX_BASE_SCORE = 0.4;

    /** Variable multiplier for prefix score adjustment (0.3). */
    public const DEFAULT_PREFIX_VARIABLE_MULTIPLIER = 0.3;

    /** Maximum score cap for prefix matches (0.6). */
    public const DEFAULT_PREFIX_MAX_SCORE = 0.6;

    private int $minPrefixLength;
    private float $prefixBaseScore;
    private float $prefixVariableMultiplier;
    private float $prefixMaxScore;
    private float $weight;

    private function __construct(
        int $minQueryLength,
        float $longestCommonSubstringWeight,
        float $levenshteinWeight,
        float $prefixWeight,
        float $coverageBonusThreshold,
        float $coverageBonusMultiplier,
        float $lowCoverageMultiplier,
        float $containmentHighRatio,
        float $containmentQueryInTargetHighScore,
        float $containmentTargetInQueryHighScore,
        float $containmentBaseScoreQueryInTarget,
        float $containmentBaseScoreTargetInQuery,
        float $containmentMultiplierQueryInTarget,
        float $containmentMultiplierTargetInQuery,
        float $containmentMaxScoreQueryInTarget,
        float $containmentMaxScoreTargetInQuery,
        float $containmentRatioHigh,
        float $containmentRatioMedium,
        float $containmentHighMultiplier,
        float $containmentMediumMultiplier,
        ?int $minPrefixLength = null,
        ?float $prefixBaseScore = null,
        ?float $prefixVariableMultiplier = null,
        ?float $prefixMaxScore = null,
        ?float $weight = null
    ) {
        parent::__construct(
            minQueryLength: $minQueryLength,
            longestCommonSubstringWeight: $longestCommonSubstringWeight,
            levenshteinWeight: $levenshteinWeight,
            prefixWeight: $prefixWeight,
            coverageBonusThreshold: $coverageBonusThreshold,
            coverageBonusMultiplier: $coverageBonusMultiplier,
            lowCoverageMultiplier: $lowCoverageMultiplier,
            containmentHighRatio: $containmentHighRatio,
            containmentQueryInTargetHighScore: $containmentQueryInTargetHighScore,
            containmentTargetInQueryHighScore: $containmentTargetInQueryHighScore,
            containmentBaseScoreQueryInTarget: $containmentBaseScoreQueryInTarget,
            containmentBaseScoreTargetInQuery: $containmentBaseScoreTargetInQuery,
            containmentMultiplierQueryInTarget: $containmentMultiplierQueryInTarget,
            containmentMultiplierTargetInQuery: $containmentMultiplierTargetInQuery,
            containmentMaxScoreQueryInTarget: $containmentMaxScoreQueryInTarget,
            containmentMaxScoreTargetInQuery: $containmentMaxScoreTargetInQuery,
            containmentRatioHigh: $containmentRatioHigh,
            containmentRatioMedium: $containmentRatioMedium,
            containmentHighMultiplier: $containmentHighMultiplier,
            containmentMediumMultiplier: $containmentMediumMultiplier
        );

        $this->minPrefixLength = $minPrefixLength ?? self::DEFAULT_MIN_PREFIX_LENGTH;
        $this->prefixBaseScore = $prefixBaseScore ?? self::DEFAULT_PREFIX_BASE_SCORE;
        $this->prefixVariableMultiplier = $prefixVariableMultiplier ?? self::DEFAULT_PREFIX_VARIABLE_MULTIPLIER;
        $this->prefixMaxScore = $prefixMaxScore ?? self::DEFAULT_PREFIX_MAX_SCORE;
        $this->weight = $weight ?? $prefixWeight;
    }

    public static function fromConfig(): self
    {
        $config = config('fuzzy.similarity', []);

        return new self(
            minQueryLength: $config['min_query_length'] ?? self::DEFAULT_MIN_QUERY_LENGTH,
            longestCommonSubstringWeight: $config['algorithm_weights']['longest_common_substring'] ?? self::DEFAULT_LONGEST_COMMON_SUBSTRING_WEIGHT,
            levenshteinWeight: $config['algorithm_weights']['levenshtein'] ?? self::DEFAULT_LEVENSHTEIN_WEIGHT,
            prefixWeight: $config['algorithm_weights']['prefix'] ?? self::DEFAULT_PREFIX_WEIGHT,
            coverageBonusThreshold: $config['coverage_bonus_threshold'] ?? self::DEFAULT_COVERAGE_BONUS_THRESHOLD,
            coverageBonusMultiplier: $config['coverage_bonus_multiplier'] ?? self::DEFAULT_COVERAGE_BONUS_MULTIPLIER,
            lowCoverageMultiplier: $config['low_coverage_multiplier'] ?? self::DEFAULT_LOW_COVERAGE_MULTIPLIER,
            containmentHighRatio: $config['containment_high_ratio'] ?? self::DEFAULT_CONTAINMENT_HIGH_RATIO,
            containmentQueryInTargetHighScore: $config['containment_query_in_target_high_score'] ?? self::DEFAULT_CONTAINMENT_QUERY_IN_TARGET_HIGH_SCORE,
            containmentTargetInQueryHighScore: $config['containment_target_in_query_high_score'] ?? self::DEFAULT_CONTAINMENT_TARGET_IN_QUERY_HIGH_SCORE,
            containmentBaseScoreQueryInTarget: $config['containment_base_score_query_in_target'] ?? self::DEFAULT_CONTAINMENT_BASE_SCORE_QUERY_IN_TARGET,
            containmentBaseScoreTargetInQuery: $config['containment_base_score_target_in_query'] ?? self::DEFAULT_CONTAINMENT_BASE_SCORE_TARGET_IN_QUERY,
            containmentMultiplierQueryInTarget: $config['containment_multiplier_query_in_target'] ?? self::DEFAULT_CONTAINMENT_MULTIPLIER_QUERY_IN_TARGET,
            containmentMultiplierTargetInQuery: $config['containment_multiplier_target_in_query'] ?? self::DEFAULT_CONTAINMENT_MULTIPLIER_TARGET_IN_QUERY,
            containmentMaxScoreQueryInTarget: $config['containment_max_score_query_in_target'] ?? self::DEFAULT_CONTAINMENT_MAX_SCORE_QUERY_IN_TARGET,
            containmentMaxScoreTargetInQuery: $config['containment_max_score_target_in_query'] ?? self::DEFAULT_CONTAINMENT_MAX_SCORE_TARGET_IN_QUERY,
            containmentRatioHigh: $config['containment_ratio_high'] ?? self::DEFAULT_CONTAINMENT_RATIO_HIGH,
            containmentRatioMedium: $config['containment_ratio_medium'] ?? self::DEFAULT_CONTAINMENT_RATIO_MEDIUM,
            containmentHighMultiplier: $config['containment_high_multiplier'] ?? self::DEFAULT_CONTAINMENT_HIGH_MULTIPLIER,
            containmentMediumMultiplier: $config['containment_medium_multiplier'] ?? self::DEFAULT_CONTAINMENT_MEDIUM_MULTIPLIER,
            minPrefixLength: $config['min_prefix_length'] ?? self::DEFAULT_MIN_PREFIX_LENGTH,
            prefixBaseScore: $config['prefix_base_score'] ?? self::DEFAULT_PREFIX_BASE_SCORE,
            prefixVariableMultiplier: $config['prefix_variable_multiplier'] ?? self::DEFAULT_PREFIX_VARIABLE_MULTIPLIER,
            prefixMaxScore: $config['prefix_max_score'] ?? self::DEFAULT_PREFIX_MAX_SCORE,
            weight: $config['prefix_weight'] ?? null
        );
    }

    public static function createDefault(): self
    {
        return new self(
            minQueryLength: self::DEFAULT_MIN_QUERY_LENGTH,
            longestCommonSubstringWeight: self::DEFAULT_LONGEST_COMMON_SUBSTRING_WEIGHT,
            levenshteinWeight: self::DEFAULT_LEVENSHTEIN_WEIGHT,
            prefixWeight: self::DEFAULT_PREFIX_WEIGHT,
            coverageBonusThreshold: self::DEFAULT_COVERAGE_BONUS_THRESHOLD,
            coverageBonusMultiplier: self::DEFAULT_COVERAGE_BONUS_MULTIPLIER,
            lowCoverageMultiplier: self::DEFAULT_LOW_COVERAGE_MULTIPLIER,
            containmentHighRatio: self::DEFAULT_CONTAINMENT_HIGH_RATIO,
            containmentQueryInTargetHighScore: self::DEFAULT_CONTAINMENT_QUERY_IN_TARGET_HIGH_SCORE,
            containmentTargetInQueryHighScore: self::DEFAULT_CONTAINMENT_TARGET_IN_QUERY_HIGH_SCORE,
            containmentBaseScoreQueryInTarget: self::DEFAULT_CONTAINMENT_BASE_SCORE_QUERY_IN_TARGET,
            containmentBaseScoreTargetInQuery: self::DEFAULT_CONTAINMENT_BASE_SCORE_TARGET_IN_QUERY,
            containmentMultiplierQueryInTarget: self::DEFAULT_CONTAINMENT_MULTIPLIER_QUERY_IN_TARGET,
            containmentMultiplierTargetInQuery: self::DEFAULT_CONTAINMENT_MULTIPLIER_TARGET_IN_QUERY,
            containmentMaxScoreQueryInTarget: self::DEFAULT_CONTAINMENT_MAX_SCORE_QUERY_IN_TARGET,
            containmentMaxScoreTargetInQuery: self::DEFAULT_CONTAINMENT_MAX_SCORE_TARGET_IN_QUERY,
            containmentRatioHigh: self::DEFAULT_CONTAINMENT_RATIO_HIGH,
            containmentRatioMedium: self::DEFAULT_CONTAINMENT_RATIO_MEDIUM,
            containmentHighMultiplier: self::DEFAULT_CONTAINMENT_HIGH_MULTIPLIER,
            containmentMediumMultiplier: self::DEFAULT_CONTAINMENT_MEDIUM_MULTIPLIER,
            minPrefixLength: self::DEFAULT_MIN_PREFIX_LENGTH,
            prefixBaseScore: self::DEFAULT_PREFIX_BASE_SCORE,
            prefixVariableMultiplier: self::DEFAULT_PREFIX_VARIABLE_MULTIPLIER,
            prefixMaxScore: self::DEFAULT_PREFIX_MAX_SCORE,
            weight: self::DEFAULT_PREFIX_WEIGHT
        );
    }

    public function getMinPrefixLength(): int
    {
        return $this->minPrefixLength;
    }

    public function getPrefixBaseScore(): float
    {
        return $this->prefixBaseScore;
    }

    public function getPrefixVariableMultiplier(): float
    {
        return $this->prefixVariableMultiplier;
    }

    public function getPrefixMaxScore(): float
    {
        return $this->prefixMaxScore;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }
}
