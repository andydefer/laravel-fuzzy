<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Tests\TestCase;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class SimilarityCalculatorTest extends TestCase
{
    private SimilarityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SimilarityCalculator();
    }

    public function test_word_similarity_exact_match(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->calculator->calculateWordSimilarity('hello', 'hello'), PHP_FLOAT_EPSILON);
    }

    public function test_word_similarity_case_insensitive(): void
    {
        $similarity = $this->calculator->calculateWordSimilarity('HELLO', 'hello');
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
    }

    public function test_word_similarity_empty_strings(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->calculator->calculateWordSimilarity('', 'hello'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $this->calculator->calculateWordSimilarity('hello', ''), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $this->calculator->calculateWordSimilarity('', ''), PHP_FLOAT_EPSILON);
    }

    public function test_word_similarity_with_contained_word(): void
    {
        // "cat" is contained in "category"
        $similarity = $this->calculator->calculateWordSimilarity('cat', 'category');
        $this->assertGreaterThan(0.7, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }

    public function test_word_similarity_with_similar_words(): void
    {
        $similarity = $this->calculator->calculateWordSimilarity('kitten', 'sitting');
        $this->assertGreaterThan(0.0, $similarity);
        $this->assertLessThan(1.0, $similarity);
    }

    public function test_word_similarity_with_completely_different_words(): void
    {
        $similarity = $this->calculator->calculateWordSimilarity('apple', 'orange');
        $this->assertLessThan(0.5, $similarity);
    }

    public function test_calculate_similarity_exact_match(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->calculator->calculateSimilarity('hello world', 'hello world'), PHP_FLOAT_EPSILON);
    }

    public function test_calculate_similarity_similar_phrases(): void
    {
        $similarity = $this->calculator->calculateSimilarity('quick brown fox', 'fast brown fox');
        $this->assertGreaterThan(0.5, $similarity);
    }

    public function test_calculate_similarity_completely_different(): void
    {
        $similarity = $this->calculator->calculateSimilarity('hello world', 'goodbye universe');
        $this->assertLessThan(0.3, $similarity);
    }

    public function test_calculate_similarity_with_short_words(): void
    {
        $similarity = $this->calculator->calculateSimilarity('a b c', 'x y z');
        $this->assertEqualsWithDelta(0.0, $similarity, PHP_FLOAT_EPSILON);
    }

    public function test_calculate_similarity_with_special_characters(): void
    {
        $similarity = $this->calculator->calculateSimilarity('hello-world', 'hello world');
        $this->assertGreaterThan(0.8, $similarity);
    }

    public function test_add_custom_algorithm(): void
    {
        $mockAlgorithm = $this->createMock(SimilarityAlgorithmInterface::class);
        $mockAlgorithm->method('calculate')->willReturn(0.5);
        $mockAlgorithm->method('getName')->willReturn('test');
        $mockAlgorithm->method('getWeight')->willReturn(0.5);

        $this->calculator->addAlgorithm($mockAlgorithm);

        // Test that the algorithm was added (no easy way to verify internally,
        // but we can test that it doesn't break)
        $similarity = $this->calculator->calculateWordSimilarity('test', 'test');
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
    }

    public function test_similarity_with_spaces_and_punctuation(): void
    {
        $similarity = $this->calculator->calculateSimilarity(
            'Hello, World! How are you?',
            'hello world how are you'
        );
        $this->assertGreaterThan(0.9, $similarity);
    }

    public function test_similarity_provides_consistent_results(): void
    {
        $similarity1 = $this->calculator->calculateSimilarity('test', 'test');
        $similarity2 = $this->calculator->calculateSimilarity('test', 'test');

        $this->assertSame($similarity1, $similarity2);
    }

    public function test_similarity_boundaries(): void
    {
        $similarity = $this->calculator->calculateSimilarity('a', 'b');
        $this->assertGreaterThanOrEqual(0.0, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }
}
