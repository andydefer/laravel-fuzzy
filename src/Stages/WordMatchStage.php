<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;
use Illuminate\Support\Collection;

/**
 * Exact word matching stage.
 *
 * This stage searches for exact word matches in the inverted index
 * and assigns a high score for these direct matches.
 */
class WordMatchStage
{
    /**
     * Searches for exact word matches in the inverted index.
     *
     * @param SearchContext $context Context containing word index and query
     * @param Closure $next Next stage in the pipeline
     * @return mixed Exact match results or next stage
     */
    public function handle(SearchContext $context, Closure $next)
    {
        foreach ($context->queryWords as $queryWord) {
            if (strlen($queryWord) >= 2 && isset($context->wordIndex[$queryWord])) {
                $this->processExactMatches($context, $queryWord);
            }
        }

        return $next($context);
    }

    /**
     * Processes exact matches for a given word.
     *
     * @param SearchContext $context Search context
     * @param string $queryWord Word to search in the index
     */
    private function processExactMatches(SearchContext $context, string $queryWord): void
    {
        $matches = $context->wordIndex[$queryWord];

        foreach ($matches as $match) {
            $resultKey = $match['indexable_type'] . '_' . $match['indexable_id'];

            if (isset($context->seen[$resultKey])) {
                continue;
            }

            $this->createSearchResult($context, $match, $resultKey, $queryWord);
        }
    }

    /**
     * Creates a search result for an exact match.
     *
     * @param SearchContext $context Search context
     * @param array $match Index match data
     * @param string $resultKey Unique result identifier
     * @param string $matchedWord Exactly matching word
     */
    private function createSearchResult(
        SearchContext $context,
        array $match,
        string $resultKey,
        string $matchedWord
    ): void {
        $exactMatchScore = 0.9 * $match['weight'];
        $model = $context->getModelInstance($resultKey);

        if ($model) {
            $context->results[$resultKey] = new SearchResultData(
                item: $model,
                score: round($exactMatchScore, 2),
                modelType: $match['indexable_type'],
                matchedField: $match['field'],
                matchedValue: $match['original_value']
            );
            $context->seen[$resultKey] = true;
        }
    }
}
