<?php

declare(strict_types=1);

namespace Fuzzy\Services\Scoring;

use Fuzzy\SearchContext;

/**
 * Interface commune pour toutes les stratégies de scoring
 */
interface ScoringStrategy
{
    public function supports(SearchContext $context, array $indexEntry): bool;

    public function calculate(SearchContext $context, array $indexEntry): float;

    public function getPriority(): int;
}
