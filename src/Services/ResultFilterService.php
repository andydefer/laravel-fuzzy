<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\ResultFilterInterface;
use Illuminate\Support\Collection;

class ResultFilterService implements ResultFilterInterface
{
    public function filterAndSort(Collection $results, float $minScore): Collection
    {
        return $results
            ->filter(fn($result): bool => $result !== null && $result->score >= $minScore)
            ->sortByDesc('score')
            ->values();
    }
}
