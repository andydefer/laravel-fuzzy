<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

use Fuzzy\SearchContext;

/**
 * Interface for score calculation strategies.
 */
interface ScoreCalculatorInterface
{
    /**
     * Calculate score for a result.
     */
    public function calculate(SearchContext $context, array $indexEntry, array $queryWords): float;

    /**
     * Get calculator priority (higher = executed first).
     */
    public function getPriority(): int;

    /**
     * Check if calculator supports the given context.
     */
    public function supports(SearchContext $context): bool;
}
