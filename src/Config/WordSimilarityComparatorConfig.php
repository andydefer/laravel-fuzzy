<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Configuration for WordSimilarityComparator.
 *
 * Contains all parameters specific to advanced lexical similarity comparison,
 * including containment detection, phonetic similarity, letter matching,
 * and dynamic penalty calculations.
 *
 * @package Fuzzy\Config
 */
class WordSimilarityComparatorConfig implements ConfigInterface
{
    // ============================================
    // Default Constants
    // ============================================

    /** Default multiplier for converting ratios to scores (100). */
    public const DEFAULT_SCORE_MULTIPLIER = 100;

    /** Default sigma weight factor for word distance influence (1.0). */
    public const DEFAULT_SIGMA = 1.0;

    /** Ratio threshold for high containment detection (80%). */
    public const DEFAULT_HIGH_CONTAINMENT_RATIO = 0.8;

    /** Ratio threshold for medium containment detection (50%). */
    public const DEFAULT_MEDIUM_CONTAINMENT_RATIO = 0.5;

    /** Minimum length to avoid division by zero (1). */
    public const DEFAULT_MIN_LENGTH_FOR_DIVISION = 1;

    /** Base increment value for loops and offsets (1). */
    public const DEFAULT_BASE_INCREMENT = 1;

    /** Starting index for array and string operations (0). */
    public const DEFAULT_START_INDEX = 0;

    /** Multiplication factor for empty text penalty (100). */
    public const DEFAULT_EMPTY_TEXT_PENALTY_FACTOR = 100;

    /** Maximum score cap (10.0). */
    public const DEFAULT_MAX_SCORE_CAP = 10.0;

    /** Penalty per unmatched letter (0.15). */
    public const DEFAULT_UNMATCHED_LETTER_PENALTY = 0.15;

    /** Multiplier for unmatched letter penalties (1.5). */
    public const DEFAULT_UNMATCHED_LETTER_MULTIPLIER = 1.5;

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

    /** High containment ratio threshold (80%). */
    public const DEFAULT_CONTAINMENT_RATIO_HIGH = 0.8;

    /** Medium containment ratio threshold (50%). */
    public const DEFAULT_CONTAINMENT_RATIO_MEDIUM = 0.5;

    /** Multiplier for high containment matches (1.8). */
    public const DEFAULT_CONTAINMENT_HIGH_MULTIPLIER = 1.8;

    /** Multiplier for medium containment matches (2.5). */
    public const DEFAULT_CONTAINMENT_MEDIUM_MULTIPLIER = 2.5;

    private function __construct(
        private int $scoreMultiplier,
        private float $sigma,
        private float $highContainmentRatio,
        private float $mediumContainmentRatio,
        private int $minLengthForDivision,
        private int $baseIncrement,
        private int $startIndex,
        private int $emptyTextPenaltyFactor,
        private float $maxScoreCap,
        private float $unmatchedLetterPenalty,
        private float $unmatchedLetterMultiplier,
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
        private float $containmentRatioHigh,
        private float $containmentRatioMedium,
        private float $containmentHighMultiplier,
        private float $containmentMediumMultiplier
    ) {}

    public static function fromConfig(): self
    {
        $config = config('fuzzy.word_similarity', []);

        return new self(
            scoreMultiplier: $config['score_multiplier'] ?? self::DEFAULT_SCORE_MULTIPLIER,
            sigma: $config['sigma'] ?? self::DEFAULT_SIGMA,
            highContainmentRatio: $config['high_containment_ratio'] ?? self::DEFAULT_HIGH_CONTAINMENT_RATIO,
            mediumContainmentRatio: $config['medium_containment_ratio'] ?? self::DEFAULT_MEDIUM_CONTAINMENT_RATIO,
            minLengthForDivision: $config['min_length_for_division'] ?? self::DEFAULT_MIN_LENGTH_FOR_DIVISION,
            baseIncrement: $config['base_increment'] ?? self::DEFAULT_BASE_INCREMENT,
            startIndex: $config['start_index'] ?? self::DEFAULT_START_INDEX,
            emptyTextPenaltyFactor: $config['empty_text_penalty_factor'] ?? self::DEFAULT_EMPTY_TEXT_PENALTY_FACTOR,
            maxScoreCap: $config['max_score_cap'] ?? self::DEFAULT_MAX_SCORE_CAP,
            unmatchedLetterPenalty: $config['unmatched_letter_penalty'] ?? self::DEFAULT_UNMATCHED_LETTER_PENALTY,
            unmatchedLetterMultiplier: $config['unmatched_letter_multiplier'] ?? self::DEFAULT_UNMATCHED_LETTER_MULTIPLIER,
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
            containmentRatioHigh: $config['containment_ratio_high'] ?? self::DEFAULT_CONTAINMENT_RATIO_HIGH,
            containmentRatioMedium: $config['containment_ratio_medium'] ?? self::DEFAULT_CONTAINMENT_RATIO_MEDIUM,
            containmentHighMultiplier: $config['containment_high_multiplier'] ?? self::DEFAULT_CONTAINMENT_HIGH_MULTIPLIER,
            containmentMediumMultiplier: $config['containment_medium_multiplier'] ?? self::DEFAULT_CONTAINMENT_MEDIUM_MULTIPLIER
        );
    }

    public static function createDefault(): self
    {
        return new self(
            scoreMultiplier: self::DEFAULT_SCORE_MULTIPLIER,
            sigma: self::DEFAULT_SIGMA,
            highContainmentRatio: self::DEFAULT_HIGH_CONTAINMENT_RATIO,
            mediumContainmentRatio: self::DEFAULT_MEDIUM_CONTAINMENT_RATIO,
            minLengthForDivision: self::DEFAULT_MIN_LENGTH_FOR_DIVISION,
            baseIncrement: self::DEFAULT_BASE_INCREMENT,
            startIndex: self::DEFAULT_START_INDEX,
            emptyTextPenaltyFactor: self::DEFAULT_EMPTY_TEXT_PENALTY_FACTOR,
            maxScoreCap: self::DEFAULT_MAX_SCORE_CAP,
            unmatchedLetterPenalty: self::DEFAULT_UNMATCHED_LETTER_PENALTY,
            unmatchedLetterMultiplier: self::DEFAULT_UNMATCHED_LETTER_MULTIPLIER,
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
            containmentRatioHigh: self::DEFAULT_CONTAINMENT_RATIO_HIGH,
            containmentRatioMedium: self::DEFAULT_CONTAINMENT_RATIO_MEDIUM,
            containmentHighMultiplier: self::DEFAULT_CONTAINMENT_HIGH_MULTIPLIER,
            containmentMediumMultiplier: self::DEFAULT_CONTAINMENT_MEDIUM_MULTIPLIER
        );
    }

    // ============================================
    // Getters
    // ============================================

    public function getScoreMultiplier(): int
    {
        return $this->scoreMultiplier;
    }
    public function getSigma(): float
    {
        return $this->sigma;
    }
    public function getHighContainmentRatio(): float
    {
        return $this->highContainmentRatio;
    }
    public function getMediumContainmentRatio(): float
    {
        return $this->mediumContainmentRatio;
    }
    public function getMinLengthForDivision(): int
    {
        return $this->minLengthForDivision;
    }
    public function getBaseIncrement(): int
    {
        return $this->baseIncrement;
    }
    public function getStartIndex(): int
    {
        return $this->startIndex;
    }
    public function getEmptyTextPenaltyFactor(): int
    {
        return $this->emptyTextPenaltyFactor;
    }
    public function getMaxScoreCap(): float
    {
        return $this->maxScoreCap;
    }
    public function getUnmatchedLetterPenalty(): float
    {
        return $this->unmatchedLetterPenalty;
    }
    public function getUnmatchedLetterMultiplier(): float
    {
        return $this->unmatchedLetterMultiplier;
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
    public function getContainmentRatioHigh(): float
    {
        return $this->containmentRatioHigh;
    }
    public function getContainmentRatioMedium(): float
    {
        return $this->containmentRatioMedium;
    }
    public function getContainmentHighMultiplier(): float
    {
        return $this->containmentHighMultiplier;
    }
    public function getContainmentMediumMultiplier(): float
    {
        return $this->containmentMediumMultiplier;
    }
}
