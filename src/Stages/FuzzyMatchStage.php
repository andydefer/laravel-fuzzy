<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

/**
 * Pipeline stage for fuzzy matching of search queries.
 *
 * Finds approximate matches using similarity calculations
 * and applies configurable similarity thresholds.
 */
class FuzzyMatchStage
{
    /**
     * Process search context for fuzzy matches.
     *
     * @param SearchContext $context The search context containing query data and results
     * @param Closure $next The next pipeline handler
     * @return mixed Result from the next handler
     */
    public function handle(SearchContext $context, Closure $next)
    {
        if ($this->shouldSkipFuzzyMatching($context)) {
            return $next($context);
        }

        $this->processAllQueryWords($context);

        return $next($context);
    }

    /**
     * Determine if fuzzy matching should be skipped.
     *
     * @param SearchContext $context The search context
     * @return bool True if should skip
     */
    private function shouldSkipFuzzyMatching(SearchContext $context): bool
    {
        return !$context->options->fuzzy || empty($context->queryWords);
    }

    /**
     * Process all query words for fuzzy matches.
     *
     * @param SearchContext $context The search context
     * @return void
     */
    private function processAllQueryWords(SearchContext $context): void
    {
        foreach ($context->queryWords as $queryWord) {
            if (strlen($queryWord) >= 2) {
                $this->findAndProcessFuzzyMatches($context, $queryWord);
            }
        }
    }

    /**
     * Find and process fuzzy matches for a query word.
     *
     * @param SearchContext $context The search context
     * @param string $queryWord The query word to match
     * @return void
     */
    private function findAndProcessFuzzyMatches(SearchContext $context, string $queryWord): void
    {
        foreach ($context->wordIndex as $indexedWord => $matches) {
            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                (string) $indexedWord
            );

            if ($similarity >= $context->options->threshold) {
                $this->processMatchingEntries($context, $matches, $similarity);
            }
        }
    }

    /**
     * Process matching index entries.
     *
     * @param SearchContext $context The search context
     * @param array $matches Array of matching entries
     * @param float $similarity Similarity score
     * @return void
     */
    private function processMatchingEntries(SearchContext $context, array $matches, float $similarity): void
    {
        /** @var array<int, array> $matches */
        foreach ($matches as $match) {
            $this->addFuzzyMatchToResults($context, $match, $similarity);
        }
    }

    /**
     * Add a fuzzy match to search results.
     *
     * @param SearchContext $context The search context
     * @param array $match The match data
     * @param float $similarity Similarity score
     * @return void
     */
    private function addFuzzyMatchToResults(SearchContext $context, array $match, float $similarity): void
    {
        $resultKey = $this->buildResultKey($match['indexable_type'], $match['indexable_id']);

        if (isset($context->seen[$resultKey])) {
            return;
        }

        $model = $context->getModelInstance($resultKey);
        if ($model) {
            $score = $similarity * ($match['weight'] ?? 1.0);

            $context->results[$resultKey] = new SearchResultData(
                item: $model,
                score: round($score, 2),
                modelType: $match['indexable_type'],
                matchedField: $match['field'],
                matchedValue: $match['original_value']
            );
            $context->seen[$resultKey] = true;
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
}
