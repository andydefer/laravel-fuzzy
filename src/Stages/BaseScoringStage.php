<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

/**
 * Base class for scoring stages with common functionality.
 */
abstract class BaseScoringStage
{
    /**
     * Common field weights configuration.
     */
    protected function getFieldWeights(): array
    {
        return config('fuzzy.scoring.field_weights', [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ]);
    }

    /**
     * Check if a result is valid.
     */
    protected function isValidResult(?SearchResultData $result, float $minScore = 0.0): bool
    {
        return $result !== null && $result->score >= $minScore;
    }

    /**
     * Build result key.
     */
    protected function buildResultKey(string $indexableType, $indexableId): string
    {
        return $indexableType . '_' . $indexableId;
    }

    /**
     * Get index entries for a specific model.
     */
    protected function getIndexEntriesForModel(
        SearchContext $context,
        string $modelType,
        $modelId
    ): array {
        $indexEntries = [];

        foreach ($context->wordIndex as $word => $matches) {
            foreach ($matches as $match) {
                if ($match['indexable_type'] === $modelType && $match['indexable_id'] == $modelId) {
                    $indexEntries[] = $match;
                }
            }
        }

        return $indexEntries;
    }

    /**
     * Filter results by minimum score.
     */
    protected function filterResultsByScore(array $results, float $minScore): array
    {
        return array_filter($results, function ($result) use ($minScore) {
            return $this->isValidResult($result, $minScore);
        });
    }
}
