<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms\WordSimilarity;

use Fuzzy\Config\WordSimilarityComparatorConfig;

/**
 * Calculates letter-based distance between strings.
 */
class LetterDistanceCalculator
{
    private WordSimilarityComparatorConfig $config;

    public function __construct(WordSimilarityComparatorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Calculate letter-based distance using match and delete algorithm.
     */
    public function calculateLetterDistance(string $stringA, string $stringB): float
    {
        if ($stringA === $stringB) {
            return FUZZY_DISTANCE_IDENTICAL;
        }

        $globalSimilarity = $this->calculateBasicSimilarity($stringA, $stringB);
        $threshold = $this->config->getLowGlobalSimilarityThreshold();
        $fallback = $this->config->getLowGlobalSimilarityFallback();

        if ($globalSimilarity < $threshold) {
            return max($fallback, $this->calculatePositionBasedLetterDistance($stringA, $stringB));
        }

        return $this->calculatePositionBasedLetterDistance($stringA, $stringB);
    }

    /**
     * Calculate letter distance considering character positions.
     */
    private function calculatePositionBasedLetterDistance(string $stringA, string $stringB): float
    {
        $lettersA = str_split($stringA);
        $lettersB = str_split($stringB);

        $matchedPairs = $this->findLetterMatches($lettersA, $lettersB);
        $totalDistance = $this->calculateTotalMatchedDistance($matchedPairs, $lettersA, $lettersB);

        return max($this->config->getMinimalPenalty(), $totalDistance);
    }

    /**
     * Calculate basic similarity ratio using unique letter comparison.
     */
    private function calculateBasicSimilarity(string $wordA, string $wordB): float
    {
        $startIndex = $this->config->getStartIndex();
        $lettersA = array_unique(str_split($wordA));
        $lettersB = array_unique(str_split($wordB));

        $commonLetters = $this->countCommonLetters($lettersA, $lettersB);
        $totalUniqueLetters = count(array_unique(array_merge($lettersA, $lettersB)));

        return $totalUniqueLetters > $startIndex
            ? $commonLetters / $totalUniqueLetters
            : FUZZY_SCORE_NONE;
    }

    /**
     * Find matching letters between two sets with position windows.
     */
    private function findLetterMatches(array $lettersA, array $lettersB): array
    {
        $matchedPairs = [];
        $usedIndicesB = [];
        $searchWindow = $this->config->getSearchWindowSize();

        foreach ($lettersA as $positionA => $letterA) {
            $bestMatch = $this->findBestLetterMatch($letterA, $lettersB, $positionA, $usedIndicesB, $searchWindow);

            if ($bestMatch !== null) {
                $matchedPairs[] = [
                    'letter' => $letterA,
                    'positionA' => $positionA,
                    'positionB' => $bestMatch['index'],
                    'distance' => $bestMatch['distance'],
                    'isExact' => $bestMatch['exact']
                ];
                $usedIndicesB[] = $bestMatch['index'];
            }
        }

        return $matchedPairs;
    }

    /**
     * Find the best matching letter in the target string.
     */
    private function findBestLetterMatch(
        string $targetLetter,
        array $searchLetters,
        int $currentPosition,
        array $usedPositions,
        int $searchWindow
    ): ?array {
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;
        $startIndex = $this->config->getStartIndex();
        $baseIncrement = $this->config->getBaseIncrement();

        $startSearch = max($startIndex, $currentPosition - $searchWindow);
        $endSearch = min(count($searchLetters), $currentPosition + $searchWindow + $baseIncrement);

        for ($searchPosition = $startSearch; $searchPosition < $endSearch; $searchPosition++) {
            if (in_array($searchPosition, $usedPositions, true)) {
                continue;
            }

            if ($targetLetter === $searchLetters[$searchPosition]) {
                $distance = abs($currentPosition - $searchPosition);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = [
                        'index' => $searchPosition,
                        'distance' => $distance,
                        'exact' => true,
                    ];
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate total distance from matched letter pairs.
     */
    private function calculateTotalMatchedDistance(array $matchedPairs, array $lettersA, array $lettersB): float
    {
        $totalDistance = FUZZY_DISTANCE_IDENTICAL;
        $startIndex = $this->config->getStartIndex();
        $imperfectMatchCount = $startIndex;

        foreach ($matchedPairs as $pair) {
            $penalty = $this->calculateDynamicPenalty(
                distance: $pair['distance'],
                sourceString: implode('', $lettersA),
                targetString: implode('', $lettersB),
                sourcePosition: $pair['positionA'],
                targetPosition: $pair['positionB']
            );
            $totalDistance += $penalty;

            $totalDistance -= $this->calculatePhoneticReduction(
                letter: $pair['letter'],
                sourceString: implode('', $lettersA),
                targetString: implode('', $lettersB),
                sourcePosition: $pair['positionA'],
                targetPosition: $pair['positionB']
            );

            if (!$pair['isExact']) {
                $imperfectMatchCount++;
            }
        }

        $unmatchedCountA = count($lettersA) - count($matchedPairs);
        $unmatchedCountB = count($lettersB) - count($matchedPairs);
        $unmatchedPenaltyBase = $this->config->getUnmatchedLetterPenalty();
        $unmatchedMultiplier = $this->config->getUnmatchedLetterMultiplier();
        $totalDistance += ($unmatchedCountA + $unmatchedCountB) * $unmatchedPenaltyBase * $unmatchedMultiplier;

        $totalDistance += $imperfectMatchCount * $this->config->getImperfectMatchPenalty();

        return $totalDistance;
    }

    /**
     * Calculate dynamic penalty based on distance and positions.
     */
    private function calculateDynamicPenalty(
        int $distance,
        string $sourceString,
        string $targetString,
        int $sourcePosition,
        int $targetPosition
    ): float {
        $startIndex = $this->config->getStartIndex();
        $baseIncrement = $this->config->getBaseIncrement();
        $zeroDistancePenalty = $this->config->getMatchDistanceZeroPenalty();

        if ($distance === $startIndex) {
            return $zeroDistancePenalty;
        }

        $longestLength = max(strlen($sourceString), strlen($targetString));
        $maxCeiling = $this->config->getMaxCeiling();
        $ceilingDivisor = $this->config->getCeilingDivisor();
        $distanceCeiling = min($distance, min($maxCeiling, $longestLength / $ceilingDivisor));

        $relativePositionA = $sourcePosition / max($baseIncrement, strlen($sourceString));
        $relativePositionB = $targetPosition / max($baseIncrement, strlen($targetString));
        $positionDifference = abs($relativePositionA - $relativePositionB);

        $adjustmentBase = $this->config->getPenaltyAdjustmentBase();
        $adjustedPenalty = $distanceCeiling * ($adjustmentBase + $positionDifference);

        $maxAdjustedPenalty = $this->config->getMaxAdjustedPenalty();

        return max($zeroDistancePenalty, min($adjustedPenalty, $maxAdjustedPenalty));
    }

    /**
     * Calculate phonetic reduction amount for a matched letter.
     */
    private function calculatePhoneticReduction(
        string $letter,
        string $sourceString,
        string $targetString,
        int $sourcePosition,
        int $targetPosition
    ): float {
        $contextRadius = $this->config->getPhoneticContextRadius();
        $contextA = $this->extractContext($sourceString, $sourcePosition, $contextRadius);
        $contextB = $this->extractContext($targetString, $targetPosition, $contextRadius);

        $exactContextReduction = $this->config->getPhoneticReductionExactContext();
        $similarContextReduction = $this->config->getPhoneticReductionSimilarContext();
        $similarityPercentThreshold = $this->config->getPhoneticSimilarityPercentThreshold();

        if ($contextA === $contextB) {
            return $exactContextReduction;
        }

        similar_text($contextA, $contextB, $similarityPercent);

        if ($similarityPercent > $similarityPercentThreshold) {
            return $similarContextReduction;
        }

        return FUZZY_SCORE_NONE;
    }

    /**
     * Extract context substring around a position.
     */
    private function extractContext(string $string, int $position, int $radius): string
    {
        $startIndex = $this->config->getStartIndex();
        $baseIncrement = $this->config->getBaseIncrement();

        $startPosition = max($startIndex, $position - $radius);
        $extractionLength = min($radius * $baseIncrement + $baseIncrement, strlen($string) - $startPosition);

        return substr($string, $startPosition, $extractionLength);
    }

    /**
     * Count common letters between two letter sets.
     */
    private function countCommonLetters(array $lettersA, array $lettersB): int
    {
        $startIndex = $this->config->getStartIndex();
        $commonCount = $startIndex;

        foreach ($lettersA as $letterA) {
            if (in_array($letterA, $lettersB, true)) {
                $commonCount++;
            }
        }

        return $commonCount;
    }
}
