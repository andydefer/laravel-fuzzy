<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services\Algorithms\WordSimilarity;

use Fuzzy\Config\WordSimilarityComparatorConfig;
use Fuzzy\Services\Algorithms\WordSimilarity\WordSimilarityCalculator;
use Fuzzy\Tests\TestCase;

final class WordSimilarityCalculatorTest extends TestCase
{
    private WordSimilarityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new WordSimilarityCalculator(WordSimilarityComparatorConfig::createDefault());
    }

    public function test_calculate_word_similarity_exact_match(): void
    {
        $score = $this->calculator->calculateWordSimilarity('hello', 'hello');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);
    }

    public function test_calculate_word_similarity_contained_word(): void
    {
        $score = $this->calculator->calculateWordSimilarity('cat', 'category');
        $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score);
    }

    public function test_calculate_word_similarity_similar_words(): void
    {
        $score = $this->calculator->calculateWordSimilarity('kitten', 'sitting');
        $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score);
    }

    public function test_calculate_word_similarity_completely_different(): void
    {
        $score = $this->calculator->calculateWordSimilarity('apple', 'orange');
        $this->assertGreaterThan(1.0, $score);
    }

    public function test_calculate_word_real_similarity_identical_letters(): void
    {
        $similarity = $this->calculator->calculateWordRealSimilarity('abc', 'abc');
        $this->assertEquals(1.0, $similarity);
    }

    public function test_calculate_word_real_similarity_partial_letters(): void
    {
        $similarity = $this->calculator->calculateWordRealSimilarity('abc', 'abd');
        $this->assertEquals(0.5, $similarity);
    }

    public function test_calculate_word_real_similarity_no_common_letters(): void
    {
        $similarity = $this->calculator->calculateWordRealSimilarity('abc', 'xyz');
        $this->assertEquals(0.0, $similarity);
    }

    public function test_calculate_word_similarity_with_phonetic_similarity(): void
    {
        $score = $this->calculator->calculateWordSimilarity('catherine', 'katherine');
        $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score);
        $this->assertLessThan(2.0, $score);
    }

    public function test_calculate_word_similarity_with_length_difference(): void
    {
        $score = $this->calculator->calculateWordSimilarity('longword', 'short');
        $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score);
    }

    public function test_calculate_word_real_similarity_with_mixed_case(): void
    {
        $similarity = $this->calculator->calculateWordRealSimilarity('AbC', 'aBc');
        // Après normalisation, les deux deviennent 'abc' -> similarité parfaite
        $this->assertEquals(1.0, $similarity);
    }

    public function test_calculate_word_real_similarity_with_repeated_letters(): void
    {
        $similarity = $this->calculator->calculateWordRealSimilarity('aaa', 'aab');
        $this->assertEquals(0.5, $similarity);
    }

    public function test_calculate_word_similarity_with_empty_words(): void
    {
        $score = $this->calculator->calculateWordSimilarity('', '');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);

        $score2 = $this->calculator->calculateWordSimilarity('hello', '');
        $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score2);
    }

    public function test_calculate_word_similarity_single_letter(): void
    {
        $score = $this->calculator->calculateWordSimilarity('a', 'a');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $score);

        $score2 = $this->calculator->calculateWordSimilarity('a', 'b');
        $this->assertGreaterThan(FUZZY_DISTANCE_IDENTICAL, $score2);
    }

    public function test_calculate_word_real_similarity_with_complex_words(): void
    {
        $similarity = $this->calculator->calculateWordRealSimilarity('mississippi', 'missouri');
        $this->assertGreaterThan(0.4, $similarity);
        $this->assertLessThan(0.8, $similarity);
    }
}
