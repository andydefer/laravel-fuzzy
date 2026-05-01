<?php

declare(strict_types=1);

namespace Fuzzy\Config;

/**
 * Configuration for LongestCommonSubstringAlgorithm.
 *
 * Contains algorithm-specific constants and parameters for the LCS algorithm.
 *
 * @package Fuzzy\Config
 */
class LongestCommonSubstringConfig extends BaseSimilarityConfig
{
    /**
     * Starting index for array initialization and empty string check.
     */
    public const DEFAULT_BASE_INDEX = 0;

    /**
     * Increment value for consecutive character matches.
     */
    public const DEFAULT_MATCH_INCREMENT = 1;

    private int $baseIndex;
    private int $matchIncrement;
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
        ?int $baseIndex = null,
        ?int $matchIncrement = null,
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

        $this->baseIndex = $baseIndex ?? self::DEFAULT_BASE_INDEX;
        $this->matchIncrement = $matchIncrement ?? self::DEFAULT_MATCH_INCREMENT;
        $this->weight = $weight ?? $longestCommonSubstringWeight;
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
            baseIndex: $config['lcs_base_index'] ?? self::DEFAULT_BASE_INDEX,
            matchIncrement: $config['lcs_match_increment'] ?? self::DEFAULT_MATCH_INCREMENT,
            weight: $config['lcs_weight'] ?? null
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
            baseIndex: self::DEFAULT_BASE_INDEX,
            matchIncrement: self::DEFAULT_MATCH_INCREMENT,
            weight: self::DEFAULT_LONGEST_COMMON_SUBSTRING_WEIGHT
        );
    }

    /**
     * Get the base index value for array initialization.
     *
     * @return int Base index (usually 0)
     */
    public function getBaseIndex(): int
    {
        return $this->baseIndex;
    }

    /**
     * Get the match increment value for consecutive character matches.
     *
     * @return int Match increment (usually 1)
     */
    public function getMatchIncrement(): int
    {
        return $this->matchIncrement;
    }

    /**
     * Get the algorithm weight in composite similarity calculations.
     *
     * This weight determines how much this algorithm's score contributes
     * when combined with other similarity algorithms.
     *
     * @return float Weight between 0.0 and 1.0
     */
    public function getWeight(): float
    {
        return $this->weight;
    }
}
