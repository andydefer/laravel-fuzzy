<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Configuration Value Object for the Advanced Scoring Calculator.
 *
 * Encapsulates all configurable parameters for scoring bonuses and penalties
 * used by the advanced scoring algorithm. This includes:
 * - Field-specific weights for different attribute types
 * - Consecutive match bonuses for longer matching sequences
 * - Position-based bonuses (early and mid-position matches)
 * - Penalties for short queries or low-quality matches
 *
 * All values are immutable and loaded from Laravel configuration
 * with sensible defaults that can be overridden by the consuming application.
 */
final class AdvancedScoringConfig implements ConfigInterface
{
    /**
     * Default weights for different field types.
     * Higher weights indicate more important fields.
     *
     * @var array<string, float>
     */
    private const DEFAULT_FIELD_WEIGHTS = [
        'name' => 1.3,
        'title' => 1.2,
        'email' => 1.0,
        'description' => 0.8,
        'content' => 0.7,
        'default' => 0.6,
    ];

    /**
     * Default bonuses for consecutive character matches.
     * Longer consecutive sequences receive higher multipliers.
     *
     * @var array<int, float>
     */
    private const DEFAULT_CONSECUTIVE_BONUS = [
        2 => 1.05,
        3 => 1.10,
        4 => 1.35,
        5 => 1.50,
    ];

    /**
     * Default penalty values for low-quality matches.
     *
     * @var array<string, float>
     */
    private const DEFAULT_PENALTIES = [
        'short_query' => 0.4,
    ];

    /**
     * Default bonus values for high-quality matches.
     *
     * @var array<string, float>
     */
    private const DEFAULT_BONUSES = [
        'early_position' => 0.2,
        'full_coverage' => 0.3,
        'high_coverage' => 0.15,
    ];

    /** Minimum consecutive character length to qualify for a bonus. */
    private const DEFAULT_MIN_CONSECUTIVE_LENGTH = 2;

    /** Maximum key used for consecutive bonus lookup (values beyond this cap at this key). */
    private const DEFAULT_MAX_CONSECUTIVE_BONUS_KEY = 5;

    /** Percentage threshold (20%) for early position bonus eligibility. */
    private const DEFAULT_EARLY_POSITION_THRESHOLD = 0.2;

    /** Percentage threshold (40%) for mid-position bonus eligibility. */
    private const DEFAULT_MID_POSITION_THRESHOLD = 0.4;

    /** Bonus multiplier applied to mid-position matches. */
    private const DEFAULT_MID_POSITION_BONUS = 1.1;

    /** Maximum query length to trigger the short query penalty. */
    private const DEFAULT_SHORT_QUERY_THRESHOLD = 4;

    /** Minimum offset for substring end position calculations (excludes single characters). */
    private const DEFAULT_MIN_SUBSTRING_END_OFFSET = 2;

    /** Minimum space required for position calculations to avoid division by zero. */
    private const DEFAULT_MIN_AVAILABLE_SPACE = 1;

    public function __construct(
        private readonly array $fieldWeights,
        private readonly array $consecutiveBonuses,
        private readonly array $penalties,
        private readonly array $bonuses,
        private readonly int $minConsecutiveLength,
        private readonly int $maxConsecutiveBonusKey,
        private readonly float $earlyPositionThreshold,
        private readonly float $midPositionThreshold,
        private readonly float $midPositionBonus,
        private readonly int $shortQueryThreshold,
        private readonly int $minSubstringEndOffset,
        private readonly int $minAvailableSpace
    ) {}

    /**
     * Create an instance from Laravel configuration.
     *
     * Loads values from 'fuzzy.scoring' config key and merges with defaults.
     *
     * @return self Configured instance
     */
    public static function fromConfig(): self
    {
        $scoringConfig = config('fuzzy.scoring', []);

        return new self(
            fieldWeights: $scoringConfig['field_weights'] ?? self::DEFAULT_FIELD_WEIGHTS,
            consecutiveBonuses: $scoringConfig['consecutive_bonus'] ?? self::DEFAULT_CONSECUTIVE_BONUS,
            penalties: $scoringConfig['penalties'] ?? self::DEFAULT_PENALTIES,
            bonuses: $scoringConfig['bonuses'] ?? self::DEFAULT_BONUSES,
            minConsecutiveLength: $scoringConfig['min_consecutive_length'] ?? self::DEFAULT_MIN_CONSECUTIVE_LENGTH,
            maxConsecutiveBonusKey: $scoringConfig['max_consecutive_bonus_key'] ?? self::DEFAULT_MAX_CONSECUTIVE_BONUS_KEY,
            earlyPositionThreshold: (float) ($scoringConfig['early_position_threshold'] ?? self::DEFAULT_EARLY_POSITION_THRESHOLD),
            midPositionThreshold: (float) ($scoringConfig['mid_position_threshold'] ?? self::DEFAULT_MID_POSITION_THRESHOLD),
            midPositionBonus: (float) ($scoringConfig['mid_position_bonus'] ?? self::DEFAULT_MID_POSITION_BONUS),
            shortQueryThreshold: (int) ($scoringConfig['short_query_threshold'] ?? self::DEFAULT_SHORT_QUERY_THRESHOLD),
            minSubstringEndOffset: (int) ($scoringConfig['min_substring_end_offset'] ?? self::DEFAULT_MIN_SUBSTRING_END_OFFSET),
            minAvailableSpace: (int) ($scoringConfig['min_available_space'] ?? self::DEFAULT_MIN_AVAILABLE_SPACE)
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
            fieldWeights: self::DEFAULT_FIELD_WEIGHTS,
            consecutiveBonuses: self::DEFAULT_CONSECUTIVE_BONUS,
            penalties: self::DEFAULT_PENALTIES,
            bonuses: self::DEFAULT_BONUSES,
            minConsecutiveLength: self::DEFAULT_MIN_CONSECUTIVE_LENGTH,
            maxConsecutiveBonusKey: self::DEFAULT_MAX_CONSECUTIVE_BONUS_KEY,
            earlyPositionThreshold: self::DEFAULT_EARLY_POSITION_THRESHOLD,
            midPositionThreshold: self::DEFAULT_MID_POSITION_THRESHOLD,
            midPositionBonus: self::DEFAULT_MID_POSITION_BONUS,
            shortQueryThreshold: self::DEFAULT_SHORT_QUERY_THRESHOLD,
            minSubstringEndOffset: self::DEFAULT_MIN_SUBSTRING_END_OFFSET,
            minAvailableSpace: self::DEFAULT_MIN_AVAILABLE_SPACE
        );
    }

    /**
     * Get all field weights.
     *
     * @return array<string, float> Associative array of field names to weight values
     */
    public function getFieldWeights(): array
    {
        return $this->fieldWeights;
    }

    /**
     * Get the weight for a specific field.
     *
     * Returns the default weight if the field is not explicitly configured.
     *
     * @param string $field The field name to look up
     * @return float The weight multiplier for this field
     */
    public function getFieldWeight(string $field): float
    {
        return $this->fieldWeights[$field] ?? $this->fieldWeights['default'];
    }

    /**
     * Get all consecutive match bonuses.
     *
     * @return array<int, float> Associative array of lengths to bonus multipliers
     */
    public function getConsecutiveBonuses(): array
    {
        return $this->consecutiveBonuses;
    }

    /**
     * Get the bonus multiplier for a given consecutive match length.
     *
     * Longer consecutive matches receive higher bonuses, capped at the
     * configured maximum key length.
     *
     * @param int $length The length of consecutive matching characters
     * @return float The bonus multiplier to apply
     */
    public function getConsecutiveBonus(int $length): float
    {
        $cappedLength = min($length, $this->maxConsecutiveBonusKey);

        return $this->consecutiveBonuses[$cappedLength] ?? FUZZY_BASE_FACTOR;
    }

    /**
     * Get all penalty values.
     *
     * @return array<string, float> Associative array of penalty types to values
     */
    public function getPenalties(): array
    {
        return $this->penalties;
    }

    /**
     * Get the penalty multiplier for short queries.
     *
     * @return float The penalty to apply when query is too short
     */
    public function getShortQueryPenalty(): float
    {
        return $this->penalties['short_query'] ?? 0.4;
    }

    /**
     * Get all bonus values.
     *
     * @return array<string, float> Associative array of bonus types to values
     */
    public function getBonuses(): array
    {
        return $this->bonuses;
    }

    /**
     * Get the bonus for matches occurring early in the text.
     *
     * @return float Bonus multiplier for early position matches
     */
    public function getEarlyPositionBonus(): float
    {
        return $this->bonuses['early_position'] ?? 0.2;
    }

    /**
     * Get the bonus for queries that fully cover the target text.
     *
     * @return float Bonus multiplier for full coverage matches
     */
    public function getFullCoverageBonus(): float
    {
        return $this->bonuses['full_coverage'] ?? 0.3;
    }

    /**
     * Get the bonus for queries with high text coverage.
     *
     * @return float Bonus multiplier for high coverage matches
     */
    public function getHighCoverageBonus(): float
    {
        return $this->bonuses['high_coverage'] ?? 0.15;
    }

    /**
     * Get the minimum consecutive length required for a bonus.
     *
     * @return int Minimum length threshold
     */
    public function getMinConsecutiveLength(): int
    {
        return $this->minConsecutiveLength;
    }

    /**
     * Get the maximum key used in consecutive bonus lookup.
     *
     * @return int Maximum key index for bonus array
     */
    public function getMaxConsecutiveBonusKey(): int
    {
        return $this->maxConsecutiveBonusKey;
    }

    /**
     * Get the threshold for early position bonus eligibility.
     *
     * @return float Percentage threshold (0.2 = first 20% of text)
     */
    public function getEarlyPositionThreshold(): float
    {
        return $this->earlyPositionThreshold;
    }

    /**
     * Get the threshold for mid-position bonus eligibility.
     *
     * @return float Percentage threshold (0.4 = first 40% of text)
     */
    public function getMidPositionThreshold(): float
    {
        return $this->midPositionThreshold;
    }

    /**
     * Get the bonus multiplier for mid-position matches.
     *
     * @return float Bonus multiplier for mid-position matches
     */
    public function getMidPositionBonus(): float
    {
        return $this->midPositionBonus;
    }

    /**
     * Get the maximum query length to trigger short query penalty.
     *
     * @return int Threshold length for short query detection
     */
    public function getShortQueryThreshold(): int
    {
        return $this->shortQueryThreshold;
    }

    /**
     * Get the minimum offset for substring end position calculations.
     *
     * @return int Minimum offset (2 means at least 2 characters)
     */
    public function getMinSubstringEndOffset(): int
    {
        return $this->minSubstringEndOffset;
    }

    /**
     * Get the minimum space required for position calculations.
     *
     * @return int Minimum available space (prevents division by zero)
     */
    public function getMinAvailableSpace(): int
    {
        return $this->minAvailableSpace;
    }
}
