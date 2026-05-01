<?php

declare(strict_types=1);

namespace Fuzzy\Config;

/**
 * Configuration for LevenshteinSimilarityAlgorithm.
 *
 * Contains all parameters specific to the Levenshtein distance algorithm
 * including distance thresholds, penalty factors, and bonus multipliers.
 *
 * @package Fuzzy\Config
 */
class LevenshteinAlgorithmConfig extends BaseSimilarityConfig
{
    /** Empty string length value (0). */
    public const DEFAULT_EMPTY_STRING_LENGTH = 0;

    /** Distance threshold for applying penalty (2 characters). */
    public const DEFAULT_DISTANCE_PENALTY_THRESHOLD = 2;

    /** Base penalty factor for Levenshtein distance (0.7). */
    public const DEFAULT_PENALTY_FACTOR_BASE = 0.7;

    /** Penalty reduction per distance unit (0.1). */
    public const DEFAULT_PENALTY_REDUCTION_PER_DISTANCE = 0.1;

    /** Threshold for close match bonus (2 characters difference). */
    public const DEFAULT_CLOSE_MATCH_BONUS_THRESHOLD = 2;

    /** Minimum word length to qualify for close match bonus (4 characters). */
    public const DEFAULT_MIN_LENGTH_FOR_BONUS = 4;

    /** Bonus multiplier for close matches (0.1 = +10%). */
    public const DEFAULT_CLOSE_MATCH_BONUS = 0.1;

    private int $emptyStringLength;
    private int $distancePenaltyThreshold;
    private float $penaltyFactorBase;
    private float $penaltyReductionPerDistance;
    private int $closeMatchBonusThreshold;
    private int $minLengthForBonus;
    private float $closeMatchBonus;
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
        ?int $emptyStringLength = null,
        ?int $distancePenaltyThreshold = null,
        ?float $penaltyFactorBase = null,
        ?float $penaltyReductionPerDistance = null,
        ?int $closeMatchBonusThreshold = null,
        ?int $minLengthForBonus = null,
        ?float $closeMatchBonus = null,
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

        $this->emptyStringLength = $emptyStringLength ?? self::DEFAULT_EMPTY_STRING_LENGTH;
        $this->distancePenaltyThreshold = $distancePenaltyThreshold ?? self::DEFAULT_DISTANCE_PENALTY_THRESHOLD;
        $this->penaltyFactorBase = $penaltyFactorBase ?? self::DEFAULT_PENALTY_FACTOR_BASE;
        $this->penaltyReductionPerDistance = $penaltyReductionPerDistance ?? self::DEFAULT_PENALTY_REDUCTION_PER_DISTANCE;
        $this->closeMatchBonusThreshold = $closeMatchBonusThreshold ?? self::DEFAULT_CLOSE_MATCH_BONUS_THRESHOLD;
        $this->minLengthForBonus = $minLengthForBonus ?? self::DEFAULT_MIN_LENGTH_FOR_BONUS;
        $this->closeMatchBonus = $closeMatchBonus ?? self::DEFAULT_CLOSE_MATCH_BONUS;
        $this->weight = $weight ?? $levenshteinWeight;
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
            emptyStringLength: $config['levenshtein_empty_string_length'] ?? self::DEFAULT_EMPTY_STRING_LENGTH,
            distancePenaltyThreshold: $config['distance_penalty_threshold'] ?? self::DEFAULT_DISTANCE_PENALTY_THRESHOLD,
            penaltyFactorBase: $config['penalty_factor_base'] ?? self::DEFAULT_PENALTY_FACTOR_BASE,
            penaltyReductionPerDistance: $config['penalty_reduction_per_distance'] ?? self::DEFAULT_PENALTY_REDUCTION_PER_DISTANCE,
            closeMatchBonusThreshold: $config['close_match_bonus_threshold'] ?? self::DEFAULT_CLOSE_MATCH_BONUS_THRESHOLD,
            minLengthForBonus: $config['min_length_for_bonus'] ?? self::DEFAULT_MIN_LENGTH_FOR_BONUS,
            closeMatchBonus: $config['close_match_bonus'] ?? self::DEFAULT_CLOSE_MATCH_BONUS,
            weight: $config['levenshtein_weight'] ?? null
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
            emptyStringLength: self::DEFAULT_EMPTY_STRING_LENGTH,
            distancePenaltyThreshold: self::DEFAULT_DISTANCE_PENALTY_THRESHOLD,
            penaltyFactorBase: self::DEFAULT_PENALTY_FACTOR_BASE,
            penaltyReductionPerDistance: self::DEFAULT_PENALTY_REDUCTION_PER_DISTANCE,
            closeMatchBonusThreshold: self::DEFAULT_CLOSE_MATCH_BONUS_THRESHOLD,
            minLengthForBonus: self::DEFAULT_MIN_LENGTH_FOR_BONUS,
            closeMatchBonus: self::DEFAULT_CLOSE_MATCH_BONUS,
            weight: self::DEFAULT_LEVENSHTEIN_WEIGHT
        );
    }

    public function getEmptyStringLength(): int
    {
        return $this->emptyStringLength;
    }

    public function getDistancePenaltyThreshold(): int
    {
        return $this->distancePenaltyThreshold;
    }

    public function getPenaltyFactorBase(): float
    {
        return $this->penaltyFactorBase;
    }

    public function getPenaltyReductionPerDistance(): float
    {
        return $this->penaltyReductionPerDistance;
    }

    public function getCloseMatchBonusThreshold(): int
    {
        return $this->closeMatchBonusThreshold;
    }

    public function getMinLengthForBonus(): int
    {
        return $this->minLengthForBonus;
    }

    public function getCloseMatchBonus(): float
    {
        return $this->closeMatchBonus;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }
}
