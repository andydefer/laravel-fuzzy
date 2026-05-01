<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Closure;
use Fuzzy\Enums\StageType;

/**
 * Interface for pipeline stage handlers.
 *
 * Defines the contract for all stages in the fuzzy search pipeline.
 * Each stage receives a SearchContextInterface and can modify it
 * before passing it to the next stage.
 *
 * Stages are ordered by priority (higher value = executed earlier)
 * and grouped by type for better organization and potential
 * parallel execution capabilities.
 *
 * @package Fuzzy\Contracts
 */
interface StageInterface
{
    /**
     * Handle the stage processing.
     *
     * Each stage performs a specific transformation on the search context,
     * then delegates to the next stage via the provided closure.
     *
     * @param SearchContextInterface $context The search context to process and modify
     * @param Closure(SearchContextInterface): mixed $next The next stage in the pipeline
     * @return mixed The result of the stage processing (typically the final search results)
     */
    public function handle(SearchContextInterface $context, Closure $next): mixed;

    /**
     * Get the execution priority of this stage.
     *
     * Higher priority values are executed earlier in the pipeline.
     * Priority range: 0 (lowest) to 100 (highest).
     *
     * Standard priority ranges by stage type:
     * - PRE_PROCESSING (80-100): Query normalization, validation, cache checking
     * - MATCH_DISCOVERY (60-79): Exact, word, fuzzy, and multi-word match discovery
     * - SCORING (40-59): Primary scoring, multi-word scoring, relevance calculation
     * - POST_PROCESSING (0-39): Result filtering, sorting, limiting, cleanup
     *
     * @return int Priority value where higher values execute earlier
     */
    public function getPriority(): int;

    /**
     * Get the type of this stage.
     *
     * Stages are grouped by type to allow better organization,
     * conditional execution, and potential parallel processing
     * capabilities in future versions.
     *
     * @return StageType The type classification of this stage
     */
    public function getType(): StageType;
}
