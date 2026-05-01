<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services\Algorithms\WordSimilarity;

use Fuzzy\Config\WordSimilarityComparatorConfig;
use Fuzzy\Services\Algorithms\WordSimilarity\WordMatchScorer;
use Fuzzy\Tests\TestCase;

final class WordMatchScorerTest extends TestCase
{
    private WordMatchScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new WordMatchScorer(WordSimilarityComparatorConfig::createDefault());
    }

    public function test_calculate_query_based_score_with_empty_text_words(): void
    {
        $queryWords = ['hello', 'world'];
        $textWords = [];
        $sigma = 1.0;

        $score = $this->scorer->calculateQueryBasedScore($queryWords, $textWords, $sigma);

        $this->assertGreaterThan(0, $score);
    }

    public function test_calculate_query_based_score_with_valid_words(): void
    {
        $queryWords = ['hello', 'world'];
        $textWords = ['hello', 'world'];
        $sigma = 1.0;

        $score = $this->scorer->calculateQueryBasedScore($queryWords, $textWords, $sigma);

        $this->assertGreaterThanOrEqual(0, $score);
    }

    public function test_calculate_query_based_score_with_sigma_scaling(): void
    {
        $queryWords = ['hello', 'world'];
        $textWords = ['hello', 'world'];
        $sigmaLow = 0.5;
        $sigmaHigh = 2.0;

        $scoreLow = $this->scorer->calculateQueryBasedScore($queryWords, $textWords, $sigmaLow);
        $scoreHigh = $this->scorer->calculateQueryBasedScore($queryWords, $textWords, $sigmaHigh);

        // Sigma plus élevé devrait donner un score plus grand (distance plus grande)
        $this->assertGreaterThanOrEqual($scoreLow, $scoreHigh);
    }

    public function test_calculate_query_based_score_with_no_best_scores(): void
    {
        $queryWords = [];
        $textWords = ['hello', 'world'];
        $sigma = 1.0;

        $score = $this->scorer->calculateQueryBasedScore($queryWords, $textWords, $sigma);

        $this->assertGreaterThanOrEqual(0, $score);
    }

    public function test_calculate_query_based_score_with_very_bad_matches(): void
    {
        $queryWords = ['xyz123'];
        $textWords = ['abcdef'];
        $sigma = 1.0;

        $score = $this->scorer->calculateQueryBasedScore($queryWords, $textWords, $sigma);

        $this->assertGreaterThanOrEqual(0, $score);
    }
}
