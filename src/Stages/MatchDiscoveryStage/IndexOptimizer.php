<?php

declare(strict_types=1);

namespace Fuzzy\Stages\MatchDiscoveryStage;

use Fuzzy\Config\MatchDiscoveryConfig;
use Carbon\Carbon;

/**
 * Optimizes and caches index structures for faster match discovery.
 */
class IndexOptimizer
{
    private array $cachedOptimizedIndexes = [];
    private array $cacheTimestamps = [];
    private MatchDiscoveryConfig $config;

    public function __construct(?MatchDiscoveryConfig $config = null)
    {
        $this->config = $config ?? MatchDiscoveryConfig::fromConfig();
    }

    /**
     * Get cached optimized indexes or build them.
     */
    public function getOrBuildOptimizedIndexes(array $wordIndex): array
    {
        $cacheKey = md5(serialize(array_keys($wordIndex)));
        $now = Carbon::now()->getTimestamp();

        if ($this->isCacheValid($cacheKey, $now)) {
            return $this->cachedOptimizedIndexes[$cacheKey];
        }

        $optimizedIndexes = $this->buildOptimizedIndexes($wordIndex);

        $this->cachedOptimizedIndexes[$cacheKey] = $optimizedIndexes;
        $this->cacheTimestamps[$cacheKey] = $now;

        $this->cleanupCache($now);

        return $optimizedIndexes;
    }

    /**
     * Build optimized index structures.
     */
    private function buildOptimizedIndexes(array $wordIndex): array
    {
        $byLength = [];
        $byFirstChar = [];
        $trigramIndex = [];

        foreach ($wordIndex as $word => $matches) {
            $wordString = (string) $word;
            $wordLength = strlen($wordString);

            if ($wordLength < $this->config->getMinWordLength()) {
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

            if ($wordLength >= $this->config->getMinTrigramLength()) {
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
     * Add word to trigram index.
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
     * Check if cache is still valid.
     */
    private function isCacheValid(string $cacheKey, int $currentTime): bool
    {
        return isset($this->cachedOptimizedIndexes[$cacheKey]) &&
            isset($this->cacheTimestamps[$cacheKey]) &&
            ($currentTime - $this->cacheTimestamps[$cacheKey]) < $this->config->getCacheTtl();
    }

    /**
     * Clean up expired cache entries.
     */
    private function cleanupCache(int $currentTime): void
    {
        if (
            count($this->cacheTimestamps) > $this->config->getMaxCacheEntries() &&
            rand(1, $this->config->getCacheCleanupProbability()) === 1
        ) {
            foreach ($this->cacheTimestamps as $key => $timestamp) {
                if (($currentTime - $timestamp) > $this->config->getCacheTtl()) {
                    unset($this->cachedOptimizedIndexes[$key]);
                    unset($this->cacheTimestamps[$key]);
                }
            }
        }
    }

    /**
     * Get optimal word lengths to check based on query length.
     */
    public function getOptimalLengthsToCheck(int $queryLength): array
    {
        $minWordLength = $this->config->getMinWordLength();

        if ($queryLength <= $this->config->getSmallWordLength()) {
            return array_filter([
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
                $queryLength + 2,
                $queryLength + 3,
            ], fn(int $l): bool => $l >= $minWordLength);
        }

        if ($queryLength <= $this->config->getMediumWordLength()) {
            return array_filter([
                $queryLength - 2,
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
                $queryLength + 2,
            ], fn(int $l): bool => $l >= $minWordLength);
        }

        return array_filter([
            $queryLength - 1,
            $queryLength,
            $queryLength + 1,
        ], fn(int $l): bool => $l >= $minWordLength);
    }

    public function getConfig(): MatchDiscoveryConfig
    {
        return $this->config;
    }
}
