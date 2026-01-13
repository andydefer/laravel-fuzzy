<?php

declare(strict_types=1);

namespace Fuzzy\Services\Algorithms;

use Fuzzy\Services\StringNormalizer;

/**
 * Advanced lexical similarity comparator for strings.
 *
 * Calculates a weighted lexical distance between two strings,
 * considering orthographic and phonetic similarities.
 * Lower scores indicate higher similarity, with 0 representing perfect identity.
 */
class WordSimilarityComparator
{
    private StringNormalizer $normalizer;
    private float $unmatchedLetterPenalty;
    private float $maxScoreCap;
    private float $wordPenaltyPerChar;
    private float $lengthPenaltyMultiplier;
    private float $minWordMatchRatio;
    private float $minimalPenalty;
    private float $matchFuzzinessPenalty;

    /**
     * Constructor.
     *
     * @param StringNormalizer $normalizer String normalizer utility
     * @param float $unmatchedLetterPenalty Penalty for unmatched letters
     * @param float $maxScoreCap Maximum allowed similarity score
     * @param float $wordPenaltyPerChar Penalty per character difference
     * @param float $lengthPenaltyMultiplier Multiplier for length-based penalties
     * @param float $minimalPenalty Minimum penalty for imperfect matches
     * @param float $matchFuzzinessPenalty Penalty for fuzzy matches
     * @param float $minWordMatchRatio Minimum word match ratio threshold
     */
    public function __construct(
        StringNormalizer $normalizer,
        float $unmatchedLetterPenalty = 0.3,
        float $maxScoreCap = 10.0,
        float $wordPenaltyPerChar = 0.08,
        float $lengthPenaltyMultiplier = 0.08,
        float $minimalPenalty = 0.5,
        float $matchFuzzinessPenalty = 0.1,
        float $minWordMatchRatio = 0.8
    ) {
        $this->normalizer = $normalizer;
        $this->unmatchedLetterPenalty = $unmatchedLetterPenalty;
        $this->maxScoreCap = $maxScoreCap;
        $this->wordPenaltyPerChar = $wordPenaltyPerChar;
        $this->lengthPenaltyMultiplier = $lengthPenaltyMultiplier;
        $this->minimalPenalty = $minimalPenalty;
        $this->matchFuzzinessPenalty = $matchFuzzinessPenalty;
        $this->minWordMatchRatio = $minWordMatchRatio;
    }

    /**
     * Compare two strings and return a similarity score.
     *
     * @param string $query First input string (query - what we're searching for)
     * @param string $text Second input string (text - where we're searching)
     * @param float $sigma Weight factor for word distance influence
     * @return float Similarity score (lower = more similar)
     */
    public function compare(string $query, string $text, float $sigma = 1.0): float
    {
        $normalizedQuery = $this->normalizer->normalize($query);
        $normalizedText = $this->normalizer->normalize($text);

        $queryWords = $this->normalizer->splitIntoWords($normalizedQuery);
        $textWords = $this->normalizer->splitIntoWords($normalizedText);

        if ($normalizedQuery === $normalizedText) {
            return 0.0;
        }

        if (empty($queryWords)) {
            return $this->maxScoreCap;
        }

        if (empty($textWords)) {
            return min($this->maxScoreCap, count($queryWords) * 3.0);
        }

        $filteredQueryWords = $this->filterShortWords($queryWords);

        if (empty($filteredQueryWords)) {
            return $this->maxScoreCap;
        }

        $globalScore = $this->calculateQueryBasedScore($filteredQueryWords, $textWords, $sigma);

        return min($this->applyMinimalPenalty($globalScore), $this->maxScoreCap);
    }

    /**
     * Filter out short words (less than 3 characters).
     *
     * @param array<string> $words Words to filter
     * @return array<string> Filtered words
     */
    private function filterShortWords(array $words): array
    {
        return array_filter($words, function ($word) {
            return strlen($word) >= 3;
        });
    }

    /**
     * Apply minimal penalty to ensure no zero score for imperfect matches.
     *
     * @param float $score Raw score
     * @return float Score with minimal penalty applied
     */
    private function applyMinimalPenalty(float $score): float
    {
        return max($score, $this->minimalPenalty);
    }

    /**
     * Calculate score based on query words.
     *
     * For each query word, find its best match in the text words and average them.
     *
     * @param array<string> $queryWords Query words
     * @param array<string> $textWords Text words
     * @param float $sigma Weight factor
     * @return float Global score
     */
    private function calculateQueryBasedScore(array $queryWords, array $textWords, float $sigma): float
    {
        if (empty($textWords)) {
            return count($queryWords) * 3.0 * $sigma;
        }

        $bestScores = $this->findBestScoresForQuery($queryWords, $textWords);

        if (empty($bestScores)) {
            return count($queryWords) * 3.0 * $sigma;
        }

        $averageScore = array_sum($bestScores) / count($bestScores);
        $veryBadMatchCount = $this->countVeryBadMatches($bestScores);
        $veryBadPenalty = $veryBadMatchCount * 1.2;

        $finalScore = $averageScore + $veryBadPenalty;

        if (count($queryWords) > 1) {
            $strictnessFactor = 1.0 + (count($queryWords) * 0.1);
            $finalScore *= $strictnessFactor;
        }

        $finalScore *= $sigma;

        return max($this->minimalPenalty, $finalScore);
    }

    /**
     * Find the best score for each query word.
     *
     * @param array<string> $queryWords Query words
     * @param array<string> $textWords Text words
     * @return array<float> Best scores for each query word
     */
    private function findBestScoresForQuery(array $queryWords, array $textWords): array
    {
        $bestScores = [];

        foreach ($queryWords as $queryWord) {
            $bestScore = $this->findBestWordMatchScore($queryWord, $textWords);
            $bestScore = $this->validateWordMatchScore($queryWord, $textWords, $bestScore);
            $bestScores[] = $bestScore;
        }

        return $bestScores;
    }

    /**
     * Find the best match score for a query word against text words.
     *
     * @param string $queryWord Query word
     * @param array<string> $textWords Text words
     * @return float Best match score
     */
    private function findBestWordMatchScore(string $queryWord, array $textWords): float
    {
        $bestScore = PHP_FLOAT_MAX;

        foreach ($textWords as $textWord) {
            $score = $this->calculateWordSimilarity($queryWord, $textWord);
            $score = $this->validateAndAdjustScore($queryWord, $textWord, $score);

            if ($score < $bestScore) {
                $bestScore = $score;
            }
        }

        return $bestScore;
    }

    /**
     * Validate and adjust a word match score if necessary.
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @param float $score Calculated score
     * @return float Adjusted score
     */
    private function validateAndAdjustScore(string $wordA, string $wordB, float $score): float
    {
        if ($score < 2.0) {
            $realSimilarity = $this->calculateWordRealSimilarity($wordA, $wordB);

            if ($realSimilarity < 0.4 && $score < 2.0) {
                return max($score, 2.0 + (1.0 - $realSimilarity) * 2.0);
            }
        }

        return $score;
    }

    /**
     * Calculate real similarity ratio between two words (0-1).
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float Similarity ratio
     */
    private function calculateWordRealSimilarity(string $wordA, string $wordB): float
    {
        $lettersA = array_unique(str_split($wordA));
        $lettersB = array_unique(str_split($wordB));

        $commonLetters = $this->countCommonLetters($lettersA, $lettersB);
        $totalUniqueLetters = count(array_unique(array_merge($lettersA, $lettersB)));

        return $totalUniqueLetters > 0 ? $commonLetters / $totalUniqueLetters : 0;
    }

    /**
     * Count common letters between two sets.
     *
     * @param array<string> $lettersA First set of letters
     * @param array<string> $lettersB Second set of letters
     * @return int Number of common letters
     */
    private function countCommonLetters(array $lettersA, array $lettersB): int
    {
        $common = 0;

        foreach ($lettersA as $letterA) {
            foreach ($lettersB as $letterB) {
                if ($this->lettersMatch($letterA, $letterB)) {
                    $common++;
                    break;
                }
            }
        }

        return $common;
    }

    /**
     * Validate a word match score against text words.
     *
     * @param string $queryWord Query word
     * @param array<string> $textWords Text words
     * @param float $score Current score
     * @return float Validated score
     */
    private function validateWordMatchScore(string $queryWord, array $textWords, float $score): float
    {
        if ($score < 2.0) {
            $similarity = $this->calculateRealSimilarity($queryWord, $textWords);
            if ($similarity < 0.3) {
                return max($score, 2.5);
            }
        }

        return $score;
    }

    /**
     * Calculate real similarity of a word with a set of words.
     *
     * @param string $queryWord Query word
     * @param array<string> $textWords Text words
     * @return float Best similarity ratio
     */
    private function calculateRealSimilarity(string $queryWord, array $textWords): float
    {
        $bestSimilarity = 0.0;

        foreach ($textWords as $textWord) {
            $similarity = $this->calculateWordRealSimilarity($queryWord, $textWord);
            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
            }
        }

        return $bestSimilarity;
    }

    /**
     * Count very bad matches in scores.
     *
     * @param array<float> $scores Match scores
     * @return int Number of very bad matches
     */
    private function countVeryBadMatches(array $scores): int
    {
        $count = 0;

        foreach ($scores as $score) {
            if ($score > 3.5) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate similarity between two words (0 = identical, higher = more different).
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float Similarity score (always > 0 unless exact match)
     */
    private function calculateWordSimilarity(string $wordA, string $wordB): float
    {
        if ($wordA === $wordB) {
            return 0.0;
        }

        $containmentScore = $this->calculateContainmentScore($wordA, $wordB);
        if ($containmentScore !== null) {
            return $containmentScore;
        }

        $basicSimilarity = $this->calculateBasicSimilarity($wordA, $wordB);
        if ($basicSimilarity < 0.2) {
            return max(3.0, $this->calculateDetailedSimilarity($wordA, $wordB));
        }

        return $this->calculateDetailedSimilarity($wordA, $wordB);
    }

    /**
     * Calculate containment score for words contained within each other.
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float|null Score if contained, null otherwise
     */
    private function calculateContainmentScore(string $wordA, string $wordB): ?float
    {
        if (strpos($wordA, $wordB) !== false || strpos($wordB, $wordA) !== false) {
            $shorterLength = min(strlen($wordA), strlen($wordB));
            $longerLength = max(strlen($wordA), strlen($wordB));
            $ratio = $shorterLength / max(1, $longerLength);

            if ($ratio >= 0.8) {
                return (1 - $ratio) * 2.5;
            } elseif ($ratio >= 0.5) {
                return (1 - $ratio) * 3.5;
            }
        }

        return null;
    }

    /**
     * Calculate basic similarity ratio (quick calculation).
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float Similarity ratio
     */
    private function calculateBasicSimilarity(string $wordA, string $wordB): float
    {
        $lettersA = array_unique(str_split($wordA));
        $lettersB = array_unique(str_split($wordB));

        $commonLetters = $this->countCommonLetters($lettersA, $lettersB);
        $totalUniqueLetters = count(array_unique(array_merge($lettersA, $lettersB)));

        return $totalUniqueLetters > 0 ? $commonLetters / $totalUniqueLetters : 0;
    }

    /**
     * Calculate detailed similarity score.
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return float Detailed similarity score
     */
    private function calculateDetailedSimilarity(string $wordA, string $wordB): float
    {
        $letterDistance = $this->calculateLetterDistance($wordA, $wordB);
        $maxLength = max(strlen($wordA), strlen($wordB), 1);
        $baseScore = $letterDistance / $maxLength * 5.0;

        if ($this->areWordsPhoneticallySimilar($wordA, $wordB)) {
            $baseScore *= 0.6;
        }

        $lengthDifference = abs(strlen($wordA) - strlen($wordB));
        if ($lengthDifference > 0) {
            $baseScore += $lengthDifference * 0.15;
        }

        return max($this->minimalPenalty, $baseScore);
    }

    /**
     * Check if two words are phonetically similar.
     *
     * @param string $wordA First word
     * @param string $wordB Second word
     * @return bool True if phonetically similar
     */
    private function areWordsPhoneticallySimilar(string $wordA, string $wordB): bool
    {
        return soundex($wordA) === soundex($wordB);
    }

    /**
     * Calculate letter-based distance using match & delete algorithm.
     *
     * @param string $stringA First normalized string
     * @param string $stringB Second normalized string
     * @return float Letter distance score
     */
    private function calculateLetterDistance(string $stringA, string $stringB): float
    {
        if ($stringA === $stringB) {
            return 0.0;
        }

        $globalSimilarity = $this->calculateBasicSimilarity($stringA, $stringB);
        if ($globalSimilarity < 0.25) {
            return max(2.5, $this->calculateStrictLetterDistance($stringA, $stringB));
        }

        return $this->calculateStrictLetterDistance($stringA, $stringB);
    }

    /**
     * Calculate strict letter distance.
     *
     * @param string $stringA First string
     * @param string $stringB Second string
     * @return float Strict letter distance
     */
    private function calculateStrictLetterDistance(string $stringA, string $stringB): float
    {
        $lettersA = str_split($stringA);
        $lettersB = str_split($stringB);

        $matchedPairs = $this->findLetterMatches($lettersA, $lettersB);
        $totalDistance = $this->calculateTotalLetterDistance($matchedPairs, $lettersA, $lettersB);

        return max($this->minimalPenalty, $totalDistance);
    }

    /**
     * Find matching letters between two sets.
     *
     * @param array<string> $lettersA First set of letters
     * @param array<string> $lettersB Second set of letters
     * @return array<array<string,mixed>> Matched pairs information
     */
    private function findLetterMatches(array $lettersA, array $lettersB): array
    {
        $matchedPairs = [];
        $usedIndicesB = [];
        $searchWindow = 2;

        foreach ($lettersA as $indexA => $letterA) {
            $bestMatch = $this->findBestLetterMatch($letterA, $lettersB, $indexA, $usedIndicesB, $searchWindow);

            if ($bestMatch !== null) {
                $matchedPairs[] = [
                    'letter' => $letterA,
                    'posA' => $indexA,
                    'posB' => $bestMatch['index'],
                    'distance' => $bestMatch['distance'],
                    'exact' => $bestMatch['exact']
                ];
                $usedIndicesB[] = $bestMatch['index'];
            }
        }

        return $matchedPairs;
    }

    /**
     * Calculate total letter distance from matched pairs.
     *
     * @param array<array<string,mixed>> $matchedPairs Matched letter information
     * @param array<string> $lettersA First set of letters
     * @param array<string> $lettersB Second set of letters
     * @return float Total distance
     */
    private function calculateTotalLetterDistance(array $matchedPairs, array $lettersA, array $lettersB): float
    {
        $totalDistance = 0;
        $imperfectMatches = 0;

        foreach ($matchedPairs as $pair) {
            $penalty = $this->calculateDynamicPenalty(
                $pair['distance'],
                implode('', $lettersA),
                implode('', $lettersB),
                $pair['posA'],
                $pair['posB']
            );
            $totalDistance += $penalty;

            $totalDistance -= $this->calculatePhoneticReduction(
                $pair['letter'],
                implode('', $lettersA),
                implode('', $lettersB),
                $pair['posA'],
                $pair['posB']
            );

            if (!$pair['exact']) {
                $imperfectMatches++;
            }
        }

        $unmatchedA = count($lettersA) - count($matchedPairs);
        $unmatchedB = count($lettersB) - count($matchedPairs);
        $totalDistance += ($unmatchedA + $unmatchedB) * $this->unmatchedLetterPenalty * 2.0;

        $totalDistance += $imperfectMatches * 0.15;

        return $totalDistance;
    }

    /**
     * Find best matching letter in target string.
     *
     * @param string $letter Letter to match
     * @param array<string> $lettersB Letters from second string
     * @param int $indexA Position in first string
     * @param array<int> $usedIndicesB Already used indices in second string
     * @param int $searchWindow Search window size
     * @return array<string,mixed>|null Best match information or null
     */
    private function findBestLetterMatch(string $letter, array $lettersB, int $indexA, array $usedIndicesB, int $searchWindow = 2): ?array
    {
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;

        $startSearch = max(0, $indexA - $searchWindow);
        $endSearch = min(count($lettersB), $indexA + $searchWindow + 1);

        for ($indexB = $startSearch; $indexB < $endSearch; $indexB++) {
            if (in_array($indexB, $usedIndicesB)) {
                continue;
            }

            if ($this->lettersMatch($letter, $lettersB[$indexB])) {
                $distance = abs($indexA - $indexB);
                $exact = ($letter === $lettersB[$indexB]);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = [
                        'index' => $indexB,
                        'distance' => $distance,
                        'exact' => $exact
                    ];
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Check if two letters match (including phonetic similarity).
     *
     * @param string $letterA First letter
     * @param string $letterB Second letter
     * @return bool True if letters match
     */
    private function lettersMatch(string $letterA, string $letterB): bool
    {
        if ($letterA === $letterB) {
            return true;
        }

        $phoneticPairs = [
            ['c', 'k'],
            ['c', 's'],
            ['g', 'j'],
            ['s', 'z'],
        ];

        foreach ($phoneticPairs as $pair) {
            if (($letterA === $pair[0] && $letterB === $pair[1]) ||
                ($letterA === $pair[1] && $letterB === $pair[0])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate dynamic penalty ceiling.
     *
     * @param int $distance Raw distance between positions
     * @param string $stringA First string
     * @param string $stringB Second string
     * @param int $posA Position in first string
     * @param int $posB Position in second string
     * @return float Adjusted penalty
     */
    private function calculateDynamicPenalty(int $distance, string $stringA, string $stringB, int $posA, int $posB): float
    {
        if ($distance === 0) {
            return 0.15;
        }

        $maxLength = max(strlen($stringA), strlen($stringB));
        $ceiling = min($distance, min(3.5, $maxLength / 2.5));

        $relativePosA = $posA / max(1, strlen($stringA));
        $relativePosB = $posB / max(1, strlen($stringB));
        $relativeDifference = abs($relativePosA - $relativePosB);

        $adjustedPenalty = $ceiling * (0.6 + $relativeDifference);

        return max(0.15, min($adjustedPenalty, 4.0));
    }

    /**
     * Calculate phonetic reduction.
     *
     * @param string $letter Letter
     * @param string $stringA First string
     * @param string $stringB Second string
     * @param int $posA Position in first string
     * @param int $posB Position in second string
     * @return float Phonetic reduction amount
     */
    private function calculatePhoneticReduction(string $letter, string $stringA, string $stringB, int $posA, int $posB): float
    {
        $contextA = $this->extractContext($stringA, $posA, 2);
        $contextB = $this->extractContext($stringB, $posB, 2);

        if ($contextA === $contextB) {
            return 0.12;
        }

        $similarity = similar_text($contextA, $contextB, $percent);
        if ($percent > 70) {
            return 0.08;
        }

        return 0.0;
    }

    /**
     * Extract context around a position.
     *
     * @param string $string Input string
     * @param int $position Center position
     * @param int $radius Context radius
     * @return string Extracted context
     */
    private function extractContext(string $string, int $position, int $radius): string
    {
        $start = max(0, $position - $radius);
        $length = min($radius * 2 + 1, strlen($string) - $start);

        return substr($string, $start, $length);
    }
}
