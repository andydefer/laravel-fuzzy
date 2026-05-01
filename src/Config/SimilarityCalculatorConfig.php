<?php

declare(strict_types=1);

namespace Fuzzy\Config;

/**
 * Configuration for the main SimilarityCalculator service.
 *
 * Extends base configuration with calculator-specific parameters
 * including word penalty, score caps, threshold values, and regex patterns.
 *
 * @package Fuzzy\Config
 */
class SimilarityCalculatorConfig extends BaseSimilarityConfig
{
    // ============================================
    // Regex Patterns for String Normalization
    // ============================================

    /**
     * Pattern to remove special characters (keeps letters, numbers, spaces)
     * @var string
     */
    public const REGEX_REMOVE_SPECIAL_CHARS = '/[^a-z0-9\s]/i';

    /**
     * Pattern to collapse multiple spaces into a single space
     * @var string
     */
    public const REGEX_COLLAPSE_SPACES = '/\s+/';

    /**
     * Pattern to split words (separators: spaces, hyphens, underscores, commas, dots)
     * @var string
     */
    public const REGEX_WORD_SPLITTER = '/[\s\-_,\.]+/';

    // ============================================
    // Calculator-specific Constants
    // ============================================

    /** Penalty per unmatched letter (0.15). */
    public const DEFAULT_UNMATCHED_LETTER_PENALTY = 0.15;

    /** Maximum score cap to prevent excessive scores (7.0). */
    public const DEFAULT_MAX_SCORE_CAP = 7.0;

    /** Penalty per character for word mismatches (0.04). */
    public const DEFAULT_WORD_PENALTY_PER_CHAR = 0.04;

    /** Multiplier for length-based penalties (0.04). */
    public const DEFAULT_LENGTH_PENALTY_MULTIPLIER = 0.04;

    /** Minimum penalty value to avoid zero penalties (0.3). */
    public const DEFAULT_MINIMAL_PENALTY = 0.3;

    /** Penalty for fuzzy matches (0.1). */
    public const DEFAULT_MATCH_FUZZINESS_PENALTY = 0.1;

    /** Minimum ratio for word matches (0.8 = 80%). */
    public const DEFAULT_MIN_WORD_MATCH_RATIO = 0.8;

    /** Threshold for short word detection (≤ 3 characters). */
    public const DEFAULT_SHORT_WORD_THRESHOLD = 3;

    /** Threshold for very bad matches (score ≥ 4.0). */
    public const DEFAULT_VERY_BAD_MATCH_THRESHOLD = 4.0;

    /** Penalty for very bad matches (0.8). */
    public const DEFAULT_VERY_BAD_MATCH_PENALTY = 0.8;

    /** Strictness factor increase per word (0.05 = +5%). */
    public const DEFAULT_STRICTNESS_FACTOR_PER_WORD = 0.05;

    /** Threshold for real similarity detection (0.35). */
    public const DEFAULT_REAL_SIMILARITY_THRESHOLD = 0.35;

    /** Base penalty when real similarity is below threshold (1.5). */
    public const DEFAULT_REAL_SIMILARITY_BASE_PENALTY = 1.5;

    /** Multiplier for real similarity penalty (1.5). */
    public const DEFAULT_REAL_SIMILARITY_MULTIPLIER = 1.5;

    /** Threshold for low similarity detection (0.3). */
    public const DEFAULT_LOW_SIMILARITY_THRESHOLD = 0.3;

    /** Penalty for low similarity matches (2.0). */
    public const DEFAULT_LOW_SIMILARITY_PENALTY = 2.0;

    /** Threshold for basic similarity fallback (0.2). */
    public const DEFAULT_BASIC_SIMILARITY_THRESHOLD = 0.2;

    /** Fallback value for very low basic similarity (2.5). */
    public const DEFAULT_BASIC_SIMILARITY_FALLBACK = 2.5;

    /** Penalty for length differences (0.1). */
    public const DEFAULT_LENGTH_DIFFERENCE_PENALTY = 0.1;

    /** Reduction factor for phonetic similarity (0.6 = 40% reduction). */
    public const DEFAULT_PHONETIC_REDUCTION_FACTOR = 0.6;

    /** Threshold for low global similarity (0.25). */
    public const DEFAULT_LOW_GLOBAL_SIMILARITY_THRESHOLD = 0.25;

    /** Fallback penalty for low global similarity (1.5). */
    public const DEFAULT_LOW_GLOBAL_SIMILARITY_FALLBACK = 1.5;

    /** Search window size for local matching (2 characters). */
    public const DEFAULT_SEARCH_WINDOW_SIZE = 2;

    /** Penalty when match distance is zero (0.1). */
    public const DEFAULT_MATCH_DISTANCE_ZERO_PENALTY = 0.1;

    /** Maximum ceiling for score adjustments (2.5). */
    public const DEFAULT_MAX_CEILING = 2.5;

    /** Divisor for ceiling calculations (2.5). */
    public const DEFAULT_CEILING_DIVISOR = 2.5;

    /** Base value for penalty adjustment (0.6). */
    public const DEFAULT_PENALTY_ADJUSTMENT_BASE = 0.6;

    /** Maximum adjusted penalty value (3.0). */
    public const DEFAULT_MAX_ADJUSTED_PENALTY = 3.0;

    /** Context radius for phonetic analysis (2 characters). */
    public const DEFAULT_PHONETIC_CONTEXT_RADIUS = 2;

    /** Reduction for exact context matches (0.12 = 12% reduction). */
    public const DEFAULT_PHONETIC_REDUCTION_EXACT_CONTEXT = 0.12;

    /** Reduction for similar context matches (0.08 = 8% reduction). */
    public const DEFAULT_PHONETIC_REDUCTION_SIMILAR_CONTEXT = 0.08;

    /** Threshold for phonetic similarity percentage (70%). */
    public const DEFAULT_PHONETIC_SIMILARITY_PERCENT_THRESHOLD = 70.0;

    /** Penalty for imperfect matches (0.1). */
    public const DEFAULT_IMPERFECT_MATCH_PENALTY = 0.1;

    /** Multiplier for unmatched letter penalties (1.5). */
    public const DEFAULT_UNMATCHED_LETTER_MULTIPLIER = 1.5;

    private string $regexRemoveSpecialChars;
    private string $regexCollapseSpaces;
    private string $regexWordSplitter;

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
        private float $unmatchedLetterPenalty,
        private float $maxScoreCap,
        private float $wordPenaltyPerChar,
        private float $lengthPenaltyMultiplier,
        private float $minimalPenalty,
        private float $matchFuzzinessPenalty,
        private float $minWordMatchRatio,
        private int $shortWordThreshold,
        private float $veryBadMatchThreshold,
        private float $veryBadMatchPenalty,
        private float $strictnessFactorPerWord,
        private float $realSimilarityThreshold,
        private float $realSimilarityBasePenalty,
        private float $realSimilarityMultiplier,
        private float $lowSimilarityThreshold,
        private float $lowSimilarityPenalty,
        private float $basicSimilarityThreshold,
        private float $basicSimilarityFallback,
        private float $lengthDifferencePenalty,
        private float $phoneticReductionFactor,
        private float $lowGlobalSimilarityThreshold,
        private float $lowGlobalSimilarityFallback,
        private int $searchWindowSize,
        private float $matchDistanceZeroPenalty,
        private float $maxCeiling,
        private float $ceilingDivisor,
        private float $penaltyAdjustmentBase,
        private float $maxAdjustedPenalty,
        private int $phoneticContextRadius,
        private float $phoneticReductionExactContext,
        private float $phoneticReductionSimilarContext,
        private float $phoneticSimilarityPercentThreshold,
        private float $imperfectMatchPenalty,
        private float $unmatchedLetterMultiplier,
        ?string $regexRemoveSpecialChars = null,
        ?string $regexCollapseSpaces = null,
        ?string $regexWordSplitter = null
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

        $this->regexRemoveSpecialChars = $regexRemoveSpecialChars ?? self::REGEX_REMOVE_SPECIAL_CHARS;
        $this->regexCollapseSpaces = $regexCollapseSpaces ?? self::REGEX_COLLAPSE_SPACES;
        $this->regexWordSplitter = $regexWordSplitter ?? self::REGEX_WORD_SPLITTER;
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
            unmatchedLetterPenalty: $config['unmatched_letter_penalty'] ?? self::DEFAULT_UNMATCHED_LETTER_PENALTY,
            maxScoreCap: $config['max_score_cap'] ?? self::DEFAULT_MAX_SCORE_CAP,
            wordPenaltyPerChar: $config['word_penalty_per_char'] ?? self::DEFAULT_WORD_PENALTY_PER_CHAR,
            lengthPenaltyMultiplier: $config['length_penalty_multiplier'] ?? self::DEFAULT_LENGTH_PENALTY_MULTIPLIER,
            minimalPenalty: $config['minimal_penalty'] ?? self::DEFAULT_MINIMAL_PENALTY,
            matchFuzzinessPenalty: $config['match_fuzziness_penalty'] ?? self::DEFAULT_MATCH_FUZZINESS_PENALTY,
            minWordMatchRatio: $config['min_word_match_ratio'] ?? self::DEFAULT_MIN_WORD_MATCH_RATIO,
            shortWordThreshold: $config['short_word_threshold'] ?? self::DEFAULT_SHORT_WORD_THRESHOLD,
            veryBadMatchThreshold: $config['very_bad_match_threshold'] ?? self::DEFAULT_VERY_BAD_MATCH_THRESHOLD,
            veryBadMatchPenalty: $config['very_bad_match_penalty'] ?? self::DEFAULT_VERY_BAD_MATCH_PENALTY,
            strictnessFactorPerWord: $config['strictness_factor_per_word'] ?? self::DEFAULT_STRICTNESS_FACTOR_PER_WORD,
            realSimilarityThreshold: $config['real_similarity_threshold'] ?? self::DEFAULT_REAL_SIMILARITY_THRESHOLD,
            realSimilarityBasePenalty: $config['real_similarity_base_penalty'] ?? self::DEFAULT_REAL_SIMILARITY_BASE_PENALTY,
            realSimilarityMultiplier: $config['real_similarity_multiplier'] ?? self::DEFAULT_REAL_SIMILARITY_MULTIPLIER,
            lowSimilarityThreshold: $config['low_similarity_threshold'] ?? self::DEFAULT_LOW_SIMILARITY_THRESHOLD,
            lowSimilarityPenalty: $config['low_similarity_penalty'] ?? self::DEFAULT_LOW_SIMILARITY_PENALTY,
            basicSimilarityThreshold: $config['basic_similarity_threshold'] ?? self::DEFAULT_BASIC_SIMILARITY_THRESHOLD,
            basicSimilarityFallback: $config['basic_similarity_fallback'] ?? self::DEFAULT_BASIC_SIMILARITY_FALLBACK,
            lengthDifferencePenalty: $config['length_difference_penalty'] ?? self::DEFAULT_LENGTH_DIFFERENCE_PENALTY,
            phoneticReductionFactor: $config['phonetic_reduction_factor'] ?? self::DEFAULT_PHONETIC_REDUCTION_FACTOR,
            lowGlobalSimilarityThreshold: $config['low_global_similarity_threshold'] ?? self::DEFAULT_LOW_GLOBAL_SIMILARITY_THRESHOLD,
            lowGlobalSimilarityFallback: $config['low_global_similarity_fallback'] ?? self::DEFAULT_LOW_GLOBAL_SIMILARITY_FALLBACK,
            searchWindowSize: $config['search_window_size'] ?? self::DEFAULT_SEARCH_WINDOW_SIZE,
            matchDistanceZeroPenalty: $config['match_distance_zero_penalty'] ?? self::DEFAULT_MATCH_DISTANCE_ZERO_PENALTY,
            maxCeiling: $config['max_ceiling'] ?? self::DEFAULT_MAX_CEILING,
            ceilingDivisor: $config['ceiling_divisor'] ?? self::DEFAULT_CEILING_DIVISOR,
            penaltyAdjustmentBase: $config['penalty_adjustment_base'] ?? self::DEFAULT_PENALTY_ADJUSTMENT_BASE,
            maxAdjustedPenalty: $config['max_adjusted_penalty'] ?? self::DEFAULT_MAX_ADJUSTED_PENALTY,
            phoneticContextRadius: $config['phonetic_context_radius'] ?? self::DEFAULT_PHONETIC_CONTEXT_RADIUS,
            phoneticReductionExactContext: $config['phonetic_reduction_exact_context'] ?? self::DEFAULT_PHONETIC_REDUCTION_EXACT_CONTEXT,
            phoneticReductionSimilarContext: $config['phonetic_reduction_similar_context'] ?? self::DEFAULT_PHONETIC_REDUCTION_SIMILAR_CONTEXT,
            phoneticSimilarityPercentThreshold: $config['phonetic_similarity_percent_threshold'] ?? self::DEFAULT_PHONETIC_SIMILARITY_PERCENT_THRESHOLD,
            imperfectMatchPenalty: $config['imperfect_match_penalty'] ?? self::DEFAULT_IMPERFECT_MATCH_PENALTY,
            unmatchedLetterMultiplier: $config['unmatched_letter_multiplier'] ?? self::DEFAULT_UNMATCHED_LETTER_MULTIPLIER,
            regexRemoveSpecialChars: $config['regex_remove_special_chars'] ?? self::REGEX_REMOVE_SPECIAL_CHARS,
            regexCollapseSpaces: $config['regex_collapse_spaces'] ?? self::REGEX_COLLAPSE_SPACES,
            regexWordSplitter: $config['regex_word_splitter'] ?? self::REGEX_WORD_SPLITTER
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
            unmatchedLetterPenalty: self::DEFAULT_UNMATCHED_LETTER_PENALTY,
            maxScoreCap: self::DEFAULT_MAX_SCORE_CAP,
            wordPenaltyPerChar: self::DEFAULT_WORD_PENALTY_PER_CHAR,
            lengthPenaltyMultiplier: self::DEFAULT_LENGTH_PENALTY_MULTIPLIER,
            minimalPenalty: self::DEFAULT_MINIMAL_PENALTY,
            matchFuzzinessPenalty: self::DEFAULT_MATCH_FUZZINESS_PENALTY,
            minWordMatchRatio: self::DEFAULT_MIN_WORD_MATCH_RATIO,
            shortWordThreshold: self::DEFAULT_SHORT_WORD_THRESHOLD,
            veryBadMatchThreshold: self::DEFAULT_VERY_BAD_MATCH_THRESHOLD,
            veryBadMatchPenalty: self::DEFAULT_VERY_BAD_MATCH_PENALTY,
            strictnessFactorPerWord: self::DEFAULT_STRICTNESS_FACTOR_PER_WORD,
            realSimilarityThreshold: self::DEFAULT_REAL_SIMILARITY_THRESHOLD,
            realSimilarityBasePenalty: self::DEFAULT_REAL_SIMILARITY_BASE_PENALTY,
            realSimilarityMultiplier: self::DEFAULT_REAL_SIMILARITY_MULTIPLIER,
            lowSimilarityThreshold: self::DEFAULT_LOW_SIMILARITY_THRESHOLD,
            lowSimilarityPenalty: self::DEFAULT_LOW_SIMILARITY_PENALTY,
            basicSimilarityThreshold: self::DEFAULT_BASIC_SIMILARITY_THRESHOLD,
            basicSimilarityFallback: self::DEFAULT_BASIC_SIMILARITY_FALLBACK,
            lengthDifferencePenalty: self::DEFAULT_LENGTH_DIFFERENCE_PENALTY,
            phoneticReductionFactor: self::DEFAULT_PHONETIC_REDUCTION_FACTOR,
            lowGlobalSimilarityThreshold: self::DEFAULT_LOW_GLOBAL_SIMILARITY_THRESHOLD,
            lowGlobalSimilarityFallback: self::DEFAULT_LOW_GLOBAL_SIMILARITY_FALLBACK,
            searchWindowSize: self::DEFAULT_SEARCH_WINDOW_SIZE,
            matchDistanceZeroPenalty: self::DEFAULT_MATCH_DISTANCE_ZERO_PENALTY,
            maxCeiling: self::DEFAULT_MAX_CEILING,
            ceilingDivisor: self::DEFAULT_CEILING_DIVISOR,
            penaltyAdjustmentBase: self::DEFAULT_PENALTY_ADJUSTMENT_BASE,
            maxAdjustedPenalty: self::DEFAULT_MAX_ADJUSTED_PENALTY,
            phoneticContextRadius: self::DEFAULT_PHONETIC_CONTEXT_RADIUS,
            phoneticReductionExactContext: self::DEFAULT_PHONETIC_REDUCTION_EXACT_CONTEXT,
            phoneticReductionSimilarContext: self::DEFAULT_PHONETIC_REDUCTION_SIMILAR_CONTEXT,
            phoneticSimilarityPercentThreshold: self::DEFAULT_PHONETIC_SIMILARITY_PERCENT_THRESHOLD,
            imperfectMatchPenalty: self::DEFAULT_IMPERFECT_MATCH_PENALTY,
            unmatchedLetterMultiplier: self::DEFAULT_UNMATCHED_LETTER_MULTIPLIER,
            regexRemoveSpecialChars: self::REGEX_REMOVE_SPECIAL_CHARS,
            regexCollapseSpaces: self::REGEX_COLLAPSE_SPACES,
            regexWordSplitter: self::REGEX_WORD_SPLITTER
        );
    }

    // Getters for regex patterns
    public function getRegexRemoveSpecialChars(): string
    {
        return $this->regexRemoveSpecialChars;
    }

    public function getRegexCollapseSpaces(): string
    {
        return $this->regexCollapseSpaces;
    }

    public function getRegexWordSplitter(): string
    {
        return $this->regexWordSplitter;
    }

    // Original getters
    public function getUnmatchedLetterPenalty(): float
    {
        return $this->unmatchedLetterPenalty;
    }

    public function getMaxScoreCap(): float
    {
        return $this->maxScoreCap;
    }

    public function getWordPenaltyPerChar(): float
    {
        return $this->wordPenaltyPerChar;
    }

    public function getLengthPenaltyMultiplier(): float
    {
        return $this->lengthPenaltyMultiplier;
    }

    public function getMinimalPenalty(): float
    {
        return $this->minimalPenalty;
    }

    public function getMatchFuzzinessPenalty(): float
    {
        return $this->matchFuzzinessPenalty;
    }

    public function getMinWordMatchRatio(): float
    {
        return $this->minWordMatchRatio;
    }

    public function getShortWordThreshold(): int
    {
        return $this->shortWordThreshold;
    }

    public function getVeryBadMatchThreshold(): float
    {
        return $this->veryBadMatchThreshold;
    }

    public function getVeryBadMatchPenalty(): float
    {
        return $this->veryBadMatchPenalty;
    }

    public function getStrictnessFactorPerWord(): float
    {
        return $this->strictnessFactorPerWord;
    }

    public function getRealSimilarityThreshold(): float
    {
        return $this->realSimilarityThreshold;
    }

    public function getRealSimilarityBasePenalty(): float
    {
        return $this->realSimilarityBasePenalty;
    }

    public function getRealSimilarityMultiplier(): float
    {
        return $this->realSimilarityMultiplier;
    }

    public function getLowSimilarityThreshold(): float
    {
        return $this->lowSimilarityThreshold;
    }

    public function getLowSimilarityPenalty(): float
    {
        return $this->lowSimilarityPenalty;
    }

    public function getBasicSimilarityThreshold(): float
    {
        return $this->basicSimilarityThreshold;
    }

    public function getBasicSimilarityFallback(): float
    {
        return $this->basicSimilarityFallback;
    }

    public function getLengthDifferencePenalty(): float
    {
        return $this->lengthDifferencePenalty;
    }

    public function getPhoneticReductionFactor(): float
    {
        return $this->phoneticReductionFactor;
    }

    public function getLowGlobalSimilarityThreshold(): float
    {
        return $this->lowGlobalSimilarityThreshold;
    }

    public function getLowGlobalSimilarityFallback(): float
    {
        return $this->lowGlobalSimilarityFallback;
    }

    public function getSearchWindowSize(): int
    {
        return $this->searchWindowSize;
    }

    public function getMatchDistanceZeroPenalty(): float
    {
        return $this->matchDistanceZeroPenalty;
    }

    public function getMaxCeiling(): float
    {
        return $this->maxCeiling;
    }

    public function getCeilingDivisor(): float
    {
        return $this->ceilingDivisor;
    }

    public function getPenaltyAdjustmentBase(): float
    {
        return $this->penaltyAdjustmentBase;
    }

    public function getMaxAdjustedPenalty(): float
    {
        return $this->maxAdjustedPenalty;
    }

    public function getPhoneticContextRadius(): int
    {
        return $this->phoneticContextRadius;
    }

    public function getPhoneticReductionExactContext(): float
    {
        return $this->phoneticReductionExactContext;
    }

    public function getPhoneticReductionSimilarContext(): float
    {
        return $this->phoneticReductionSimilarContext;
    }

    public function getPhoneticSimilarityPercentThreshold(): float
    {
        return $this->phoneticSimilarityPercentThreshold;
    }

    public function getImperfectMatchPenalty(): float
    {
        return $this->imperfectMatchPenalty;
    }

    public function getUnmatchedLetterMultiplier(): float
    {
        return $this->unmatchedLetterMultiplier;
    }
}
