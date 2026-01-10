<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

/**
 * Pipeline stage for exact matching of search queries.
 *
 * This stage handles exact matches for both complete normalized queries
 * and individual query words, assigning higher scores to complete phrase matches.
 */
class ExactMatchStage
{
    /**
     * Process search context for exact matches.
     *
     * @param SearchContext $context The search context containing query data and results
     * @param Closure $next The next pipeline handler
     * @return mixed Result from the next handler
     */
    public function handle(SearchContext $context, Closure $next)
    {
        $this->matchCompleteQuery($context);
        $this->matchIndividualWords($context);

        return $next($context);
    }

    /**
     * Find exact matches for the complete normalized query.
     *
     * @param SearchContext $context The search context
     * @return void
     */
    private function matchCompleteQuery(SearchContext $context): void
    {
        if ($this->hasExactMatch($context->normalizedQuery, $context->wordIndex)) {
            $this->processExactMatches($context, $context->normalizedQuery, 1.0);
        }
    }

    /**
     * Find exact matches for individual query words.
     *
     * @param SearchContext $context The search context
     * @return void
     */
    private function matchIndividualWords(SearchContext $context): void
    {
        foreach ($context->queryWords as $queryWord) {
            if ($this->hasExactMatch($queryWord, $context->wordIndex)) {
                $this->processExactMatches($context, $queryWord, 0.95);
            }
        }
    }

    /**
     * Check if a word has exact matches in the index.
     *
     * @param string $word The word to check
     * @param array $wordIndex The word index
     * @return bool True if exact match exists
     */
    private function hasExactMatch(string $word, array $wordIndex): bool
    {
        return !empty($word) && isset($wordIndex[$word]);
    }

    /**
     * Process exact matches and add them to results.
     *
     * @param SearchContext $context The search context
     * @param string $matchedWord The word that matched
     * @param float $scoreMultiplier Multiplier for the base score
     * @return void
     */
    private function processExactMatches(SearchContext $context, string $matchedWord, float $scoreMultiplier): void
    {
        /** @var array<int, array> $matches */
        $matches = $context->wordIndex[$matchedWord];

        foreach ($matches as $match) {
            $resultKey = $this->buildResultKey($match['indexable_type'], $match['indexable_id']);

            if ($this->shouldProcessMatch($context, $resultKey)) {
                $this->addMatchToResults($context, $match, $resultKey, $scoreMultiplier);
            }
        }
    }

    /**
     * Build a unique key for a search result.
     *
     * @param string $indexableType The model type
     * @param mixed $indexableId The model ID
     * @return string Unique result key
     */
    private function buildResultKey(string $indexableType, $indexableId): string
    {
        return $indexableType . '_' . $indexableId;
    }

    /**
     * Determine if a match should be processed.
     *
     * @param SearchContext $context The search context
     * @param string $resultKey The result key
     * @return bool True if should process
     */
    private function shouldProcessMatch(SearchContext $context, string $resultKey): bool
    {
        return !isset($context->seen[$resultKey]);
    }

    /**
     * Add a match to search results.
     *
     * @param SearchContext $context The search context
     * @param array $match The match data
     * @param string $resultKey The result key
     * @param float $scoreMultiplier Multiplier for the base score
     * @return void
     */
    private function addMatchToResults(SearchContext $context, array $match, string $resultKey, float $scoreMultiplier): void
    {
        $model = $context->getModelInstance($resultKey);

        if ($model) {
            $score = $scoreMultiplier * $match['weight'];

            $context->results[$resultKey] = new SearchResultData(
                item: $model,
                score: $score,
                modelType: $match['indexable_type'],
                matchedField: $match['field'],
                matchedValue: $match['original_value']
            );
            $context->seen[$resultKey] = true;
        }
    }
}
