<?php

declare(strict_types=1);

namespace LaravelFuzzy\Stages;

use LaravelFuzzy\SearchContext;
use Closure;

class MultiWordProcessingStage
{
    private const SHORT_QUERY_PENALTY = 0.4;
    private const CROSS_WORD_MATCH_PENALTY_MULTI = 0.3;

    public function handle(SearchContext $context, Closure $next)
    {
        if (!$context->hasMultipleWords || empty($context->results)) {
            $context->finalResults = collect($context->results);
            return $next($context);
        }

        $finalResults = [];

        foreach ($context->results as $key => $result) {
            $model = $context->getModelInstance($key);

            if (!$model) {
                continue;
            }

            $indexEntries = $this->getIndexEntriesForModel($context, $result->modelType, $model->getIndexableId());

            if (!empty($indexEntries)) {
                $finalScore = $this->calculateMultiWordScore($context, $indexEntries, $context->queryWords);

                if ($finalScore >= $context->options->minScore) {
                    $result->score = $finalScore;
                    $finalResults[$key] = $result;
                }
            }
        }

        $context->finalResults = collect($finalResults);
        return $next($context);
    }

    private function getIndexEntriesForModel(SearchContext $context, string $modelType, $modelId): array
    {
        $indexEntries = [];

        foreach ($context->wordIndex as $matches) {
            foreach ($matches as $match) {
                if ($match['indexable_type'] === $modelType && $match['indexable_id'] == $modelId) {
                    $indexEntries[] = $match;
                }
            }
        }

        return $indexEntries;
    }

    private function calculateMultiWordScore(SearchContext $context, array $indexEntries, array $queryWords): float
    {
        $matchedWordsCount = 0;
        $totalWordScore = 0.0;
        $hasShortQuery = false;
        $crossWordMatchCount = 0;

        foreach ($queryWords as $queryWord) {
            $bestWordScore = 0.0;
            $isCrossWordMatch = false;

            if (strlen($queryWord) < 4) {
                $hasShortQuery = true;
            }

            foreach ($indexEntries as $entry) {
                foreach ($entry['normalized_words'] as $targetWord) {
                    $targetWord = (string) $targetWord;
                    $wordScore = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);

                    if ($wordScore > 0 && $this->isLikelyCrossWordMatch($queryWord, $targetWord)) {
                        $isCrossWordMatch = true;
                        $wordScore *= 0.7;
                    }

                    $bestWordScore = max($bestWordScore, $wordScore);
                }
            }

            if ($bestWordScore > 0) {
                $matchedWordsCount++;
                $totalWordScore += $bestWordScore;

                if ($isCrossWordMatch) {
                    $crossWordMatchCount++;
                }
            }
        }

        if ($matchedWordsCount === 0) {
            return 0.0;
        }

        $coverage = $matchedWordsCount / count($queryWords);
        $averageScore = $totalWordScore / $matchedWordsCount;

        $coverageBonus = $coverage === 1.0 ? 0.3 : ($coverage >= 0.75 ? 0.15 : 0.0);

        $shortQueryPenalty = $hasShortQuery ? self::SHORT_QUERY_PENALTY : 0.0;
        $crossWordPenalty = ($crossWordMatchCount / count($queryWords)) * self::CROSS_WORD_MATCH_PENALTY_MULTI;

        $finalScore = min($averageScore * (1 + $coverage) + $coverageBonus, 1.0);
        $finalScore *= (1 - $shortQueryPenalty - $crossWordPenalty);

        return max(0.0, $finalScore);
    }

    private function isLikelyCrossWordMatch(string $queryWord, string $targetWord): bool
    {
        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);

        if (abs($queryLength - $targetLength) > 3) {
            return true;
        }

        if (!str_contains($targetWord, $queryWord) && !str_contains($queryWord, $targetWord)) {
            $commonLength = $this->getCommonSubstringLength($queryWord, $targetWord);
            $maxLength = max($queryLength, $targetLength);

            return ($commonLength / $maxLength) < 0.5;
        }

        return false;
    }

    private function getCommonSubstringLength(string $str1, string $str2): int
    {
        $maxLength = 0;
        $str1Length = strlen($str1);

        for ($i = 0; $i < $str1Length; $i++) {
            for ($j = $i + 1; $j <= $str1Length; $j++) {
                $substring = substr($str1, $i, $j - $i);
                if (str_contains($str2, $substring) && strlen($substring) > $maxLength) {
                    $maxLength = strlen($substring);
                }
            }
        }

        return $maxLength;
    }
}
