<?php

declare(strict_types=1);

namespace Fuzzy\Stages\MatchDiscoveryStage;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Config\MatchDiscoveryConfig;

/**
 * Finds matches in the index using various strategies.
 */
class MatchFinder
{
    private MatchDiscoveryConfig $config;
    private IndexOptimizer $optimizer;

    public function __construct(?MatchDiscoveryConfig $config = null)
    {
        $this->config = $config ?? MatchDiscoveryConfig::fromConfig();
        $this->optimizer = new IndexOptimizer($this->config);
    }

    /**
     * Discover exact matches for the complete query.
     */
    public function discoverExactMatches(SearchContextInterface $context): bool
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
     * Discover word-by-word matches in the index.
     */
    public function discoverWordMatches(SearchContextInterface $context): void
    {
        $wordIndex = $context->getWordIndex();
        $queryWords = $context->getQueryWords();
        $minWordLength = $this->config->getMinWordLength();

        foreach ($queryWords as $queryWord) {
            // Skip words that are too short
            if (strlen($queryWord) < $minWordLength) {
                continue;
            }

            // Skip if this is a single-word query and the word is the full query
            if (!$context->hasMultipleWords() && $queryWord === $context->getNormalizedQuery()) {
                continue;
            }

            // Check if the word exists in the index
            if (isset($wordIndex[$queryWord])) {
                foreach ($wordIndex[$queryWord] as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Discover fuzzy matches using optimized strategies.
     */
    public function discoverFuzzyMatchesOptimized(SearchContextInterface $context): void
    {
        $wordIndex = $context->getWordIndex();

        if ($wordIndex === []) {
            return;
        }

        if (count($wordIndex) < $this->config->getSmallIndexThreshold()) {
            $this->discoverFuzzyMatchesSimple($context, $wordIndex);
            return;
        }

        $optimizedIndexes = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);

        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < $this->config->getMinWordLength()) {
                continue;
            }

            if ($context->hasMultipleWords() && isset($wordIndex[$queryWord])) {
                continue;
            }

            $this->findContainedMatches($queryWord, $optimizedIndexes['byLength'], $context);
            $this->findMatchesByTrigrams($queryWord, $optimizedIndexes['trigramIndex'], $context);
            $this->findMatchesByFirstCharAndLength(
                $queryWord,
                $optimizedIndexes['byLength'],
                $context,
                $context->options->threshold
            );
        }
    }

    /**
     * Simple fuzzy match discovery for small indexes.
     */
    private function discoverFuzzyMatchesSimple(SearchContextInterface $context, array $wordIndex): void
    {
        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < $this->config->getMinWordLength()) {
                continue;
            }

            if ($context->hasMultipleWords() && isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex as $indexedWord => $matches) {
                $indexedWordString = (string) $indexedWord;

                if (strlen($indexedWordString) < $this->config->getMinWordLength()) {
                    continue;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    $indexedWordString
                );

                if ($similarity >= $context->options->threshold) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Find words containing the query word.
     */
    private function findContainedMatches(
        string $queryWord,
        array $byLength,
        SearchContextInterface $context
    ): void {
        $queryLength = strlen($queryWord);
        $maxOffset = $queryLength <= $this->config->getSmallWordLength()
            ? $this->config->getSmallWordOffset()
            : $this->config->getLargeWordOffset();

        for ($targetLength = $queryLength; $targetLength <= $queryLength + $maxOffset; ++$targetLength) {
            if (!isset($byLength[$targetLength])) {
                continue;
            }

            $maxChecks = min($this->config->getMaxContainedChecks(), count($byLength[$targetLength]));
            $wordsToCheck = array_slice($byLength[$targetLength], 0, $maxChecks, true);

            foreach ($wordsToCheck as $indexedWord => $matches) {
                if (str_contains((string) $indexedWord, $queryWord)) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Find matches using trigram similarity.
     */
    private function findMatchesByTrigrams(
        string $queryWord,
        array $trigramIndex,
        SearchContextInterface $context
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
        $maxCandidates = min($this->config->getMaxTrigramCandidates(), count($candidates));
        $topCandidates = array_slice(array_keys($candidateScores), 0, $maxCandidates, true);

        foreach ($topCandidates as $candidateWord) {
            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                $candidateWord
            );

            if ($similarity >= $context->options->threshold) {
                foreach ($candidates[$candidateWord] as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Find matches by first character and similar length.
     */
    private function findMatchesByFirstCharAndLength(
        string $queryWord,
        array $byLength,
        SearchContextInterface $context,
        ?float $customThreshold = null
    ): void {
        $queryLength = strlen($queryWord);
        $firstChar = $queryWord[0];
        $threshold = $customThreshold ?? $context->options->threshold;
        $lengthsToCheck = $this->optimizer->getOptimalLengthsToCheck($queryLength);

        $totalChecks = 0;

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
                if ($totalChecks > $this->config->getMaxChecksPerQuery()) {
                    return;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    $indexedWordString
                );

                if ($similarity >= $threshold) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Discover multi-word matches.
     */
    public function discoverMultiWordMatches(SearchContextInterface $context): void
    {
        $wordIndex = $context->getWordIndex();
        $queryWords = $context->getQueryWords();

        foreach ($queryWords as $queryWord) {
            if (strlen($queryWord) < $this->config->getMinWordLength()) {
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
     * Discover only very similar matches for single word queries with exact matches.
     */
    public function discoverVeryCloseMatches(SearchContextInterface $context, string $queryWord, array $wordIndex): void
    {
        $highThreshold = max($context->options->threshold, $this->config->getHighThreshold());

        foreach ($wordIndex as $indexedWord => $matches) {
            $indexedWordString = (string) $indexedWord;

            if (strlen($indexedWordString) < $this->config->getMinWordLength()) {
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
                foreach ($matches as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Discover close matches for large indexes.
     */
    public function discoverCloseMatchesOptimized(SearchContextInterface $context, string $queryWord): void
    {
        $wordIndex = $context->getWordIndex();
        $optimizedIndexes = $this->optimizer->getOrBuildOptimizedIndexes($wordIndex);
        $highThreshold = max($context->options->threshold, $this->config->getHighThreshold());

        $this->findMatchesByFirstCharAndLength(
            $queryWord,
            $optimizedIndexes['byLength'],
            $context,
            $highThreshold
        );
    }

    /**
     * Generate trigrams from a word.
     */
    private function generateTrigrams(string $word): array
    {
        $trigrams = [];
        $length = strlen($word);
        $minTrigramLength = $this->config->getMinTrigramLength();

        if ($length < $minTrigramLength) {
            return [];
        }

        for ($i = 0; $i <= $length - $minTrigramLength; ++$i) {
            $trigram = substr($word, $i, $minTrigramLength);
            $trigrams[$trigram] = true;
        }

        return array_keys($trigrams);
    }

    /**
     * Apply quick filters to skip unlikely matches.
     */
    private function passesQuickFilters(string $queryWord, string $indexedWord): bool
    {
        $queryLength = strlen($queryWord);
        $indexedLength = strlen($indexedWord);

        if (abs($queryLength - $indexedLength) > $this->config->getMaxLengthDifference()) {
            return false;
        }

        if ($queryLength === 0 || $indexedLength === 0) {
            return false;
        }

        return $queryWord[0] === $indexedWord[0];
    }

    /**
     * Add all matches to context.
     */
    private function addAllMatches(SearchContextInterface $context, array $matches): void
    {
        foreach ($matches as $match) {
            $context->addPotentialMatch($match);
        }
    }
}
