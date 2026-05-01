<?php

declare(strict_types=1);

namespace Fuzzy\Enums;

/**
 * Types of stages for the search pipeline.
 *
 * Stages are grouped by their purpose and execution context.
 * This classification helps with debugging, logging, and potential
 * parallel execution in future versions.
 *
 * @package Fuzzy\Enums
 */
enum StageType: string
{
    /**
     * Stages executed before the search begins.
     *
     * Examples: query normalization, validation, authentication, rate limiting.
     */
    case PRE_PROCESSING = 'pre_processing';

    /**
     * Stages that discover potential matches in the index.
     *
     * Examples: exact match discovery, word match discovery, fuzzy match discovery.
     */
    case MATCH_DISCOVERY = 'match_discovery';

    /**
     * Stages that calculate relevance scores for discovered matches.
     *
     * Examples: primary scoring, multi-word scoring, relevance weighting.
     */
    case SCORING = 'scoring';

    /**
     * Stages executed after scoring to process and format results.
     *
     * Examples: result filtering, sorting, limiting, response formatting.
     */
    case POST_PROCESSING = 'post_processing';

    /**
     * Get the human-readable display name for the stage type.
     *
     * @return string Display name in title case
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::PRE_PROCESSING => 'Pre-processing',
            self::MATCH_DISCOVERY => 'Match Discovery',
            self::SCORING => 'Scoring',
            self::POST_PROCESSING => 'Post-processing',
        };
    }

    /**
     * Get the default priority range for this stage type.
     *
     * Returns an array with minimum and maximum priority values
     * recommended for stages of this type.
     *
     * Priority ranges:
     * - Pre-processing: 80-100 (highest priority)
     * - Match Discovery: 60-79
     * - Scoring: 40-59
     * - Post-processing: 0-39 (lowest priority)
     *
     * @return array{0: int, 1: int} Array containing [minPriority, maxPriority]
     */
    public function getDefaultPriorityRange(): array
    {
        return match ($this) {
            self::PRE_PROCESSING => [80, 100],
            self::MATCH_DISCOVERY => [60, 79],
            self::SCORING => [40, 59],
            self::POST_PROCESSING => [0, 39],
        };
    }
}
