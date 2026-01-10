<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;
use Illuminate\Support\Collection;

/**
 * Score aggregation and unified calculation stage.
 *
 * This stage combines scores from different similarity algorithms,
 * applies field weights and bonuses to produce a final score.
 */
class ScoreAggregationStage
{
    private const FIELD_WEIGHTS = [
        'name' => 1.3,
        'title' => 1.2,
        'email' => 1.0,
        'description' => 0.8,
        'content' => 0.7,
        'default' => 0.6,
    ];

    private const CONSECUTIVE_BONUS = [
        2 => 1.05,
        3 => 1.15,
        4 => 1.30,
        5 => 1.50,
    ];

    /**
     * Calculates final scores by aggregating different similarity metrics.
     *
     * @param SearchContext $context Context containing preliminary results
     * @param Closure $next Next stage in the pipeline
     * @return mixed Results with calculated final scores
     */
    public function handle(SearchContext $context, Closure $next)
    {
        if (empty($context->results)) {
            return $next($context);
        }

        $finalResults = [];

        foreach ($context->results as $key => $result) {
            if ($result === null) {
                continue;
            }

            $finalScore = $this->computeFinalScoreForResult($context, $result);

            if ($finalScore >= $context->options->minScore) {
                $result->score = $finalScore;
                $finalResults[$key] = $result;
            }
        }

        $context->finalResults = collect($finalResults);
        return $next($context);
    }

    /**
     * Calculates the final score for a given result.
     *
     * @param SearchContext $context Search context
     * @param SearchResultData $result Result to evaluate
     * @return float Final score normalized between 0 and 1
     */
    private function computeFinalScoreForResult(SearchContext $context, SearchResultData $result): float
    {
        $indexEntries = $this->getIndexEntriesForModel(
            $context,
            $result->modelType,
            $result->item->getIndexableId()
        );

        if (empty($indexEntries)) {
            return 0.0;
        }

        $finalScore = $this->calculateUnifiedScore($context, $indexEntries, $result->matchedValue);
        return min(max($finalScore, 0.0), 1.0);
    }

    /**
     * Retrieves all index entries for a specific model.
     *
     * @param SearchContext $context Context containing word index
     * @param string $modelType Model type
     * @param mixed $modelId Model identifier
     * @return array Index entries matching the model
     */
    private function getIndexEntriesForModel(SearchContext $context, string $modelType, $modelId): array
    {
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
     * Calculates a unified score based on multiple similarity metrics.
     *
     * @param SearchContext $context Search context
     * @param array $indexEntries Index entries for the model
     * @param string $matchedValue Original matching value
     * @return float Unified score
     */
    private function calculateUnifiedScore(SearchContext $context, array $indexEntries, string $matchedValue): float
    {
        $queryWords = $context->queryWords;
        $totalWords = count($queryWords);

        if ($totalWords === 0) {
            return 0.0;
        }

        $exactMatchScore = $this->calculateExactMatchScore($context, $matchedValue);
        if ($exactMatchScore > 0) {
            return $exactMatchScore;
        }

        $wordScores = $this->calculateWordScores($context, $queryWords, $indexEntries);

        if (empty($wordScores)) {
            return 0.0;
        }

        return $this->aggregateWordScores($wordScores, $totalWords, $matchedValue);
    }

    /**
     * Calculates score for exact phrase matches.
     *
     * @param SearchContext $context Search context
     * @param string $matchedValue Value to compare
     * @return float Exact match score or 0
     */
    private function calculateExactMatchScore(SearchContext $context, string $matchedValue): float
    {
        $normalizedMatched = $context->normalizer->normalize($matchedValue);
        $normalizedQuery = $context->normalizedQuery;

        if ($normalizedMatched === $normalizedQuery) {
            return 1.0;
        }

        if (str_contains($normalizedMatched, $normalizedQuery)) {
            return min(0.95, 0.8 + (strlen($normalizedQuery) / strlen($normalizedMatched)) * 0.2);
        }

        return 0.0;
    }

    /**
     * Calculates individual word scores for each query word.
     *
     * @param SearchContext $context Search context
     * @param array $queryWords Array of query words
     * @param array $indexEntries Index entries to evaluate
     * @return array Calculated word scores
     */
    private function calculateWordScores(SearchContext $context, array $queryWords, array $indexEntries): array
    {
        $wordScores = [];

        foreach ($queryWords as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            $bestWordScore = $this->findBestWordScore($context, $queryWord, $indexEntries);

            if ($bestWordScore['score'] > 0) {
                $wordScores[] = $bestWordScore;
            }
        }

        return $wordScores;
    }

    /**
     * Finds the best score for a single query word across all index entries.
     *
     * @param SearchContext $context Search context
     * @param string $queryWord Word to score
     * @param array $indexEntries Index entries to search
     * @return array Best score information including weight and word
     */
    private function findBestWordScore(SearchContext $context, string $queryWord, array $indexEntries): array
    {
        $bestScore = 0.0;
        $bestFieldWeight = self::FIELD_WEIGHTS['default'];

        foreach ($indexEntries as $entry) {
            $fieldWeight = self::FIELD_WEIGHTS[$entry['field']] ?? self::FIELD_WEIGHTS['default'];

            foreach ($entry['normalized_words'] as $targetWord) {
                $targetWord = (string) $targetWord;

                if (strlen($targetWord) < 2) {
                    continue;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);

                if ($similarity > 0) {
                    $enhancedScore = $this->enhanceWordScore(
                        $queryWord,
                        $targetWord,
                        $similarity,
                        $entry['field'],
                        $entry['original_value'] ?? ''
                    );

                    $weightedScore = $enhancedScore * $fieldWeight;

                    if ($weightedScore > $bestScore) {
                        $bestScore = $weightedScore;
                        $bestFieldWeight = $fieldWeight;
                    }
                }
            }
        }

        return [
            'score' => $bestScore,
            'weight' => $bestFieldWeight,
            'word' => $queryWord,
        ];
    }

    /**
     * Enhances a base similarity score with various bonuses.
     *
     * @param string $queryWord Original query word
     * @param string $targetWord Target word from index
     * @param float $baseScore Base similarity score
     * @param string $field Field name where the word was found
     * @param string $fullText Full original text value
     * @return float Enhanced score with bonuses applied
     */
    private function enhanceWordScore(
        string $queryWord,
        string $targetWord,
        float $baseScore,
        string $field,
        string $fullText
    ): float {
        $enhancedScore = $baseScore;

        $consecutiveBonus = $this->calculateConsecutiveBonus($queryWord, $targetWord);
        $enhancedScore *= $consecutiveBonus;

        $positionBonus = $this->calculatePositionBonus($targetWord, $fullText);
        $enhancedScore *= $positionBonus;

        return min($enhancedScore, 1.0);
    }

    /**
     * Calculates bonus for consecutive character matches.
     *
     * @param string $queryWord Query word
     * @param string $targetWord Target word
     * @return float Bonus multiplier (1.0 = no bonus)
     */
    private function calculateConsecutiveBonus(string $queryWord, string $targetWord): float
    {
        $queryWord = strtolower($queryWord);
        $targetWord = strtolower($targetWord);

        $maxConsecutive = $this->findLongestCommonSubstring($queryWord, $targetWord);

        if ($maxConsecutive >= 2) {
            return self::CONSECUTIVE_BONUS[min($maxConsecutive, 5)] ?? 1.0;
        }

        return 1.0;
    }

    /**
     * Finds the longest common substring between two words.
     *
     * @param string $queryWord First word
     * @param string $targetWord Second word
     * @return int Length of longest common substring
     */
    private function findLongestCommonSubstring(string $queryWord, string $targetWord): int
    {
        $maxConsecutive = 0;
        $queryLength = strlen($queryWord);

        for ($i = 0; $i < $queryLength; $i++) {
            for ($j = $i + 2; $j <= $queryLength; $j++) {
                $substring = substr($queryWord, $i, $j - $i);
                if (str_contains($targetWord, $substring)) {
                    $maxConsecutive = max($maxConsecutive, strlen($substring));
                }
            }
        }

        return $maxConsecutive;
    }

    /**
     * Calculates bonus based on word position in text.
     *
     * @param string $word Word to locate
     * @param string $fullText Full text to search
     * @return float Position bonus multiplier
     */
    private function calculatePositionBonus(string $word, string $fullText): float
    {
        $fullText = strtolower($fullText);
        $word = strtolower($word);

        $position = strpos($fullText, $word);

        if ($position === false) {
            return 1.0;
        }

        $textLength = strlen($fullText);
        $wordLength = strlen($word);
        $relativePosition = $position / max(1, $textLength - $wordLength);

        if ($relativePosition < 0.2) {
            return 1.2;
        } elseif ($relativePosition < 0.4) {
            return 1.1;
        }

        return 1.0;
    }

    /**
     * Aggregates individual word scores into a final unified score.
     *
     * @param array $wordScores Individual word scores
     * @param int $totalQueryWords Total number of query words
     * @param string $matchedValue Original matched value
     * @return float Final aggregated score
     */
    private function aggregateWordScores(array $wordScores, int $totalQueryWords, string $matchedValue): float
    {
        $totalScore = 0.0;
        $totalWeight = 0.0;
        $matchedWords = count($wordScores);

        foreach ($wordScores as $wordScore) {
            $totalScore += $wordScore['score'] * $wordScore['weight'];
            $totalWeight += $wordScore['weight'];
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        $averageScore = $totalScore / $totalWeight;

        if ($totalQueryWords > 1) {
            $coverage = $matchedWords / $totalQueryWords;
            $averageScore = $this->applyCoverageBonus($averageScore, $coverage);
        }

        return $averageScore;
    }

    /**
     * Applies coverage bonus for multi-word queries.
     *
     * @param float $baseScore Base average score
     * @param float $coverage Percentage of query words matched
     * @return float Score with coverage bonus applied
     */
    private function applyCoverageBonus(float $baseScore, float $coverage): float
    {
        if ($coverage >= 0.8) {
            return min($baseScore * 1.2, 1.0);
        } elseif ($coverage >= 0.5) {
            return min($baseScore * 1.1, 1.0);
        }

        return $baseScore;
    }
}
