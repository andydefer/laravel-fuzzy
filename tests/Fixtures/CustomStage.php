<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Enums\StageType;
use Closure;

/**
 * Custom stage for testing pipeline extensibility.
 *
 * This stage demonstrates how users can add their own processing logic
 * to the search pipeline by implementing StageInterface.
 * 
 * It also shows how to properly implement the required methods:
 * - getPriority(): Defines execution order
 * - getType(): Defines the stage category
 */
class CustomStage implements StageInterface
{
    /**
     * Priority for this custom stage.
     * 
     * Priority 85 means it will execute before NormalizeQueryStage (priority 90? Actually higher priority = earlier)
     * Wait: In our sorting, higher priority executes earlier.
     * 
     * Priority reference:
     * - 100: Critical (authentication, security)
     * - 90: Query normalization
     * - 85: Custom pre-processing (this stage)
     * - 80: Cache checking
     * - 75: Match discovery
     * - 55: Primary scoring
     * - 45: Relevance scoring
     * - 20: Sorting and limiting
     * - 10: Cleanup
     */
    private const PRIORITY = 85;

    /**
     * {@inheritDoc}
     * 
     * Returns the priority of this stage.
     * Higher priority values are executed earlier in the pipeline.
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * {@inheritDoc}
     * 
     * Returns the type of this stage.
     * This custom stage is for pre-processing operations.
     */
    public function getType(): StageType
    {
        return StageType::PRE_PROCESSING;
    }

    /**
     * {@inheritDoc}
     *
     * This custom stage adds a processing marker to the context
     * for testing purposes.
     * 
     * @param SearchContextInterface $context The search context to process
     * @param Closure $next The next stage in the pipeline
     * @return mixed The result of the stage processing
     */
    public function handle(SearchContextInterface $context, Closure $next): mixed
    {
        // Custom processing logic here
        // Example: Add a marker that this stage was executed
        if (!isset($context->processedStages)) {
            $context->processedStages = [];
        }

        $context->processedStages[] = self::class;

        // Example: Add custom data to context
        // $context->customData = 'some value';

        // Example: Log the stage execution
        // logger()->info('CustomStage executed', ['query' => $context->query->originalQuery]);

        // Proceed to the next stage in the pipeline
        return $next($context);
    }
}
