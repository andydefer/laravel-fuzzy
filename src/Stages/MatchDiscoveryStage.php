<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Carbon\Carbon;
use Fuzzy\SearchContext;
use Closure;

class MatchDiscoveryStage
{
    private const CACHE_TTL = 300;

    private static array $cachedOptimizedIndexes = [];
    private static array $cacheTimestamps = [];

    /**
     * Process the search context through match discovery stage
     *
     * Discovers exact, fuzzy, and multi-word matches in the index based on the query.
     * Implements optimized strategies for different query patterns.
     *
     * @param SearchContext $context Search context containing query and configuration
     * @param Closure $next Next stage in the pipeline
     * @return mixed
     */
    public function handle(SearchContext $context, Closure $next)
    {
        if ($context->query->isEmpty()) {
            return $next($context);
        }

        $hasExactMatches = $this->discoverExactMatches($context);

        if (!$context->hasMultipleWords() && $hasExactMatches && $context->options->fuzzy) {
            $this->handleSingleWordWithExactMatch($context);
        } else {
            $this->discoverWordMatches($context);

            if ($context->options->fuzzy) {
                $this->discoverFuzzyMatchesOptimized($context);
            }

            if ($context->hasMultipleWords()) {
                $this->discoverMultiWordMatches($context);
            }
        }

        return $next($context);
    }

    /**
     * Discover exact matches for the complete query
     *
     * @param SearchContext $context Search context
     * @return bool True if any exact matches were found
     */
    private function discoverExactMatches(SearchContext $context): bool
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        if (!isset($wordIndex[$normalizedQuery])) {
            return false;
        }

        foreach ($wordIndex[$normalizedQuery] as $match) {
            $context->addPotentialMatch($match);
        }

        return true;
    }

    /**
     * Handle single word queries that have exact matches
     *
     * Applies hybrid strategy: keeps exact matches and adds only very close fuzzy matches
     * to avoid redundant expensive similarity calculations.
     *
     * @param SearchContext $context Search context
     */
    private function handleSingleWordWithExactMatch(SearchContext $context): void
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        if (count($wordIndex) < 1000) {
            $this->discoverVeryCloseMatches($context, $normalizedQuery, $wordIndex);
        } else {
            $this->discoverCloseMatchesOptimized($context, $normalizedQuery);
        }
    }

    /**
     * Discover only very similar matches for single word queries with exact matches
     *
     * Uses higher threshold and length-based filtering to limit comparisons.
     *
     * @param SearchContext $context Search context
     * @param string $queryWord Normalized query word
     * @param array $wordIndex Current word index
     */
    private function discoverVeryCloseMatches(SearchContext $context, string $queryWord, array $wordIndex): void
    {
        $highThreshold = max($context->options->threshold, 0.7);

        foreach ($wordIndex as $indexedWord => $matches) {
            $indexedWordString = (string) $indexedWord;

            if (strlen($indexedWordString) < 2) {
                continue;
            }

            if (!$this->passesQuickFilters($queryWord, $indexedWordString)) {
                continue;
            }

            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                $indexedWordString
            );

            if ($similarity >= $highThreshold) {
                $this->addAllMatches($context, $matches);
            }
        }
    }

    /**
     * Discover close matches for large indexes
     *
     * @param SearchContext $context Search context
     * @param string $queryWord Normalized query word
     */
    private function discoverCloseMatchesOptimized(SearchContext $context, string $queryWord): void
    {
        $wordIndex = $context->getWordIndex();
        $optimizedIndexes = $this->getOrBuildOptimizedIndexes($wordIndex);
        $highThreshold = max($context->options->threshold, 0.7);

        $this->findMatchesByFirstCharAndLengthOptimized(
            $queryWord,
            $optimizedIndexes['byLength'],
            $context,
            $highThreshold
        );
    }

    /**
     * Discover word-by-word matches in the index
     *
     * @param SearchContext $context Search context
     */
    private function discoverWordMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();

        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            if (!$context->hasMultipleWords() && $queryWord === $context->getNormalizedQuery()) {
                continue;
            }

            if (isset($wordIndex[$queryWord])) {
                $this->addAllMatches($context, $wordIndex[$queryWord]);
            }
        }
    }

    /**
     * Discover fuzzy matches using optimized strategies
     *
     * Implements three-level strategy for efficiency:
     * 1. Contained matches (fastest)
     * 2. Trigram-based matches
     * 3. First character + length filtered matches
     *
     * @param SearchContext $context Search context
     */
    private function discoverFuzzyMatchesOptimized(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();

        if ($wordIndex === []) {
            return;
        }

        if (count($wordIndex) < 1000) {
            $this->discoverFuzzyMatchesSimple($context, $wordIndex);
            return;
        }

        $optimizedIndexes = $this->getOrBuildOptimizedIndexes($wordIndex);

        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            if ($context->hasMultipleWords() && isset($wordIndex[$queryWord])) {
                continue;
            }

            $this->findContainedMatchesOptimized(
                $queryWord,
                $optimizedIndexes['byLength'],
                $context
            );

            $this->findMatchesByTrigrams(
                $queryWord,
                $optimizedIndexes['trigramIndex'],
                $context
            );

            $this->findMatchesByFirstCharAndLengthOptimized(
                $queryWord,
                $optimizedIndexes['byLength'],
                $context,
                $context->options->threshold
            );
        }
    }

    /**
     * Simple fuzzy match discovery for small indexes
     *
     * @param SearchContext $context Search context
     * @param array $wordIndex Current word index
     */
    private function discoverFuzzyMatchesSimple(SearchContext $context, array $wordIndex): void
    {
        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            if ($context->hasMultipleWords() && isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex as $indexedWord => $matches) {
                $indexedWordString = (string) $indexedWord;

                if (strlen($indexedWordString) < 2) {
                    continue;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    $indexedWordString
                );

                if ($similarity >= $context->options->threshold) {
                    $this->addAllMatches($context, $matches);
                }
            }
        }
    }

    /**
     * Get cached optimized indexes or build them
     *
     * Builds three optimized index structures:
     * - byLength: Words grouped by character count
     * - byFirstChar: Words grouped by first character
     * - trigramIndex: Words indexed by 3-character sequences
     *
     * @param array $wordIndex Original word index
     * @return array<string, array> Optimized index structures
     */
    private function getOrBuildOptimizedIndexes(array $wordIndex): array
    {
        $cacheKey = md5(serialize(array_keys($wordIndex)));
        $now = Carbon::now()->getTimestamp();

        if ($this->isCacheValid($cacheKey, $now)) {
            return self::$cachedOptimizedIndexes[$cacheKey];
        }

        $optimizedIndexes = $this->buildOptimizedIndexes($wordIndex);

        self::$cachedOptimizedIndexes[$cacheKey] = $optimizedIndexes;
        self::$cacheTimestamps[$cacheKey] = $now;

        $this->cleanupCache($now);

        return $optimizedIndexes;
    }

    /**
     * Build optimized index structures
     *
     * @param array $wordIndex Original word index
     * @return array<string, array> Optimized index structures
     */
    private function buildOptimizedIndexes(array $wordIndex): array
    {
        $byLength = [];
        $byFirstChar = [];
        $trigramIndex = [];

        foreach ($wordIndex as $word => $matches) {
            $wordString = (string) $word;
            $wordLength = strlen($wordString);

            if ($wordLength < 2) {
                continue;
            }

            if (!isset($byLength[$wordLength])) {
                $byLength[$wordLength] = [];
            }
            $byLength[$wordLength][$wordString] = $matches;

            $firstChar = $wordString[0];
            if (!isset($byFirstChar[$firstChar])) {
                $byFirstChar[$firstChar] = [];
            }
            $byFirstChar[$firstChar][$wordString] = $matches;

            if ($wordLength >= 3) {
                $this->addToTrigramIndex($wordString, $matches, $trigramIndex);
            }
        }

        return [
            'byLength' => $byLength,
            'byFirstChar' => $byFirstChar,
            'trigramIndex' => $trigramIndex,
        ];
    }

    /**
     * Add word to trigram index
     *
     * @param string $word Word to index
     * @param array $matches Associated matches
     * @param array<string, array> $trigramIndex Reference to trigram index
     */
    private function addToTrigramIndex(string $word, array $matches, array &$trigramIndex): void
    {
        $trigrams = $this->generateTrigrams($word);

        foreach ($trigrams as $trigram) {
            if (!isset($trigramIndex[$trigram])) {
                $trigramIndex[$trigram] = [];
            }
            $trigramIndex[$trigram][$word] = $matches;
        }
    }

    /**
     * Generate trigrams from a word
     *
     * @param string $word Input word
     * @return array<int, string> List of trigrams
     */
    private function generateTrigrams(string $word): array
    {
        $trigrams = [];
        $length = strlen($word);

        if ($length < 3) {
            return [];
        }

        for ($i = 0; $i <= $length - 3; ++$i) {
            $trigram = substr($word, $i, 3);
            $trigrams[$trigram] = true;
        }

        return array_keys($trigrams);
    }

    /**
     * Find words containing the query word
     *
     * @param string $queryWord Query word to search for
     * @param array<int, array> $byLength Words grouped by length
     * @param SearchContext $context Search context
     */
    private function findContainedMatchesOptimized(
        string $queryWord,
        array $byLength,
        SearchContext $context
    ): void {
        $queryLength = strlen($queryWord);

        for ($targetLength = $queryLength; $targetLength <= $queryLength + 10; ++$targetLength) {
            if (!isset($byLength[$targetLength])) {
                continue;
            }

            $maxChecks = min(200, count($byLength[$targetLength]));
            $wordsToCheck = array_slice($byLength[$targetLength], 0, $maxChecks, true);

            foreach ($wordsToCheck as $indexedWord => $matches) {
                if (str_contains((string) $indexedWord, $queryWord)) {
                    $this->addAllMatches($context, $matches);
                }
            }
        }
    }

    /**
     * Find matches using trigram similarity
     *
     * @param string $queryWord Query word
     * @param array<string, array> $trigramIndex Trigram index
     * @param SearchContext $context Search context
     */
    private function findMatchesByTrigrams(
        string $queryWord,
        array $trigramIndex,
        SearchContext $context
    ): void {
        $queryTrigrams = $this->generateTrigrams($queryWord);

        if ($queryTrigrams === []) {
            return;
        }

        $candidates = [];
        $candidateScores = [];

        foreach ($queryTrigrams as $trigram) {
            if (!isset($trigramIndex[$trigram])) {
                continue;
            }

            foreach ($trigramIndex[$trigram] as $word => $matches) {
                $candidateScores[$word] = ($candidateScores[$word] ?? 0) + 1;
                $candidates[$word] = $matches;
            }
        }

        if ($candidates === []) {
            return;
        }

        arsort($candidateScores);
        $maxCandidates = min(100, count($candidates));
        $topCandidates = array_slice(array_keys($candidateScores), 0, $maxCandidates, true);

        foreach ($topCandidates as $candidateWord) {
            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                $candidateWord
            );

            if ($similarity >= $context->options->threshold) {
                $this->addAllMatches($context, $candidates[$candidateWord]);
            }
        }
    }

    /**
     * Find matches by first character and similar length
     *
     * @param string $queryWord Query word
     * @param array<int, array> $byLength Words grouped by length
     * @param SearchContext $context Search context
     * @param float|null $customThreshold Optional custom similarity threshold
     */
    private function findMatchesByFirstCharAndLengthOptimized(
        string $queryWord,
        array $byLength,
        SearchContext $context,
        ?float $customThreshold = null
    ): void {
        $queryLength = strlen($queryWord);
        $firstChar = $queryWord[0];
        $threshold = $customThreshold ?? $context->options->threshold;
        $lengthsToCheck = $this->getOptimalLengthsToCheck($queryLength);

        $totalChecks = 0;
        $maxChecksPerQuery = 500;

        foreach ($lengthsToCheck as $length) {
            if (!isset($byLength[$length])) {
                continue;
            }

            foreach ($byLength[$length] as $indexedWord => $matches) {
                $indexedWordString = (string) $indexedWord;

                if ($indexedWordString[0] !== $firstChar) {
                    continue;
                }

                ++$totalChecks;
                if ($totalChecks > $maxChecksPerQuery) {
                    return;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    $indexedWordString
                );

                if ($similarity >= $threshold) {
                    $this->addAllMatches($context, $matches);
                }
            }
        }
    }

    /**
     * Get optimal word lengths to check based on query length
     *
     * @param int $queryLength Length of query word
     * @return array<int> List of lengths to check
     */
    private function getOptimalLengthsToCheck(int $queryLength): array
    {
        if ($queryLength <= 3) {
            return array_filter([
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
                $queryLength + 2,
                $queryLength + 3,
            ], fn(int $l): bool => $l >= 2);
        }

        if ($queryLength <= 6) {
            return array_filter([
                $queryLength - 2,
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
                $queryLength + 2,
            ], fn(int $l): bool => $l >= 2);
        }

        return array_filter([
            $queryLength - 1,
            $queryLength,
            $queryLength + 1,
        ], fn(int $l): bool => $l >= 2);
    }

    /**
     * Discover additional multi-word matches
     *
     * @param SearchContext $context Search context
     */
    private function discoverMultiWordMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();
        $queryWords = $context->getQueryWords();

        foreach ($queryWords as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            if (!isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex[$queryWord] as $match) {
                $key = $match['indexable_type'] . '_' . $match['indexable_id'];

                if ($context->hasPotentialMatches($key)) {
                    continue;
                }

                $context->addPotentialMatch($match);
            }
        }
    }

    /**
     * Check if cache is still valid
     *
     * @param string $cacheKey Cache key
     * @param int $currentTime Current timestamp
     * @return bool True if cache is valid
     */
    private function isCacheValid(string $cacheKey, int $currentTime): bool
    {
        return isset(self::$cachedOptimizedIndexes[$cacheKey]) &&
            isset(self::$cacheTimestamps[$cacheKey]) &&
            ($currentTime - self::$cacheTimestamps[$cacheKey]) < self::CACHE_TTL;
    }

    /**
     * Clean up expired cache entries
     *
     * @param int $currentTime Current timestamp
     */
    private function cleanupCache(int $currentTime): void
    {
        if (count(self::$cacheTimestamps) > 20 && rand(1, 100) === 1) {
            foreach (self::$cacheTimestamps as $key => $timestamp) {
                if (($currentTime - $timestamp) > self::CACHE_TTL) {
                    unset(self::$cachedOptimizedIndexes[$key]);
                    unset(self::$cacheTimestamps[$key]);
                }
            }
        }
    }

    /**
     * Apply quick filters to skip unlikely matches
     *
     * @param string $queryWord Query word
     * @param string $indexedWord Indexed word
     * @return bool True if word passes filters
     */
    private function passesQuickFilters(string $queryWord, string $indexedWord): bool
    {
        $queryLength = strlen($queryWord);
        $indexedLength = strlen($indexedWord);

        if (abs($queryLength - $indexedLength) > 2) {
            return false;
        }

        return $queryWord[0] === $indexedWord[0];
    }

    /**
     * Add all matches to context
     *
     * @param SearchContext $context Search context
     * @param array $matches List of matches to add
     */
    private function addAllMatches(SearchContext $context, array $matches): void
    {
        foreach ($matches as $match) {
            $context->addPotentialMatch($match);
        }
    }
}
