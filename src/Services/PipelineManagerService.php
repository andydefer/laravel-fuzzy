<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\PipelineManagerInterface;
use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

/**
 * Service that manages the search pipeline execution.
 *
 * Orchestrates the flow of search request through multiple processing stages
 * including normalization, match discovery, scoring, and result sorting.
 * 
 * Stages are automatically sorted by priority (highest first) regardless of
 * the order they are provided in configuration.
 */
class PipelineManagerService implements PipelineManagerInterface
{
    /**
     * @var array<int, StageInterface>
     */
    private array $stages;

    /**
     * Constructor.
     *
     * @param Pipeline $pipeline Laravel pipeline instance for stage execution
     * @param array<int, StageInterface> $stages Array of stage instances (order doesn't matter, will be sorted by priority)
     * @throws InvalidArgumentException If any stage does not implement StageInterface
     */
    public function __construct(
        private Pipeline $pipeline,
        array $stages
    ) {
        $this->stages = $this->validateAndSortStages($stages);
    }

    /**
     * Process the search context through the pipeline stages.
     *
     * {@inheritDoc}
     *
     * @param SearchContextInterface $context The search context containing query,
     *                                        options, and index data
     * @return array<int, mixed> Array of processed search results
     */
    public function process(SearchContextInterface $context): array
    {
        return $this->pipeline
            ->send($context)
            ->through($this->stages)
            ->then(fn(SearchContextInterface $context): array => $context->results);
    }

    /**
     * Validate and sort stages by priority (highest first).
     *
     * @param array<int, StageInterface> $stages Array of stage instances
     * @return array<int, StageInterface> Validated and sorted stages
     * @throws InvalidArgumentException If any stage does not implement StageInterface
     */
    private function validateAndSortStages(array $stages): array
    {
        foreach ($stages as $stage) {
            if (!$stage instanceof StageInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'All pipeline stages must implement %s. Got: %s',
                        StageInterface::class,
                        get_debug_type($stage)
                    )
                );
            }
        }

        // Sort stages by priority (higher priority = earlier execution)
        usort($stages, function (StageInterface $a, StageInterface $b): int {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $stages;
    }
}
