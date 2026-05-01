<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Enums\StageType;
use Illuminate\Support\Collection;
use Closure;

/**
 * Query normalization stage in the fuzzy search pipeline.
 *
 * This stage validates that the search query is not empty after normalization.
 * The actual query normalization is handled by the SearchQuery value object.
 */
class NormalizeQueryStage implements StageInterface
{
    /**
     * Priority for this stage (high priority - runs early)
     */
    private const PRIORITY = 90;

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): StageType
    {
        return StageType::PRE_PROCESSING;
    }

    /**
     * {@inheritDoc}
     *
     * Process the search context by validating the normalized query.
     * Returns an empty collection if the query is invalid, otherwise
     * proceeds to the next stage with the original context.
     *
     * @param SearchContextInterface $context The search context containing user query and configuration
     * @param Closure $next The next stage in the pipeline
     * @return Collection|mixed Empty collection if query is invalid, otherwise result from next stage
     */
    public function handle(SearchContextInterface $context, Closure $next): mixed
    {
        if ($context->query->isEmpty()) {
            return new Collection();
        }

        return $next($context);
    }
}
