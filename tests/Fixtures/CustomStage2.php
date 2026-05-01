<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Fixtures;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Enums\StageType;
use Closure;

/**
 * Second custom stage for testing pipeline extensibility.
 */
class CustomStage2 implements StageInterface
{
    private const PRIORITY = 86;

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getType(): StageType
    {
        return StageType::PRE_PROCESSING;
    }

    public function handle(SearchContextInterface $context, Closure $next): mixed
    {
        if (!isset($context->processedStages)) {
            $context->processedStages = [];
        }

        $context->processedStages[] = self::class;

        return $next($context);
    }
}
