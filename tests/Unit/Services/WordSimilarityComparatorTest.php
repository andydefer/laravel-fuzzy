<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Fuzzy\Services\Algorithms\WordSimilarityComparator;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\TestCase;

/**
 * Unit tests for WordSimilarityComparator.
 */
final class WordSimilarityComparatorTest extends TestCase
{
    private WordSimilarityComparator $comparator;
    private StringNormalizer $normalizer;

    /**
     * Set up test dependencies.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StringNormalizer();
        $this->comparator = new WordSimilarityComparator(
            normalizer: $this->normalizer
        );
    }

    /**
     * Test exact matches and normalization.
     */
    public function test_exact_matches_and_normalization(): void
    {
        // Arrange: Define test cases with expected perfect scores
        $testCases = [
            ['hello world', 'hello world', 0.0],
            ['Hello World', 'hello world', 0.0],
            ['hello    world', 'hello world', 0.0],
        ];

        foreach ($testCases as [$inputA, $inputB, $expectedScore]) {
            // Act: Calculate similarity score
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Verify exact match produces zero score
            $this->assertEqualsWithDelta(
                $expectedScore,
                $score,
                0.01,
                "Failed for: '$inputA' vs '$inputB'. Got: $score, Expected: $expectedScore"
            );
        }
    }

    /**
     * Test phonetic similarities.
     */
    public function test_phonetic_similarities(): void
    {
        // Arrange: Define phonetically similar pairs with maximum score limits
        $testCases = [
            ['catherine', 'katherine', 1.75],
            ['cindy', 'sindy', 1.75],
            ['george', 'jeorge', 1.75],
            ['viktor', 'wictor', 1.75],
            ['laser', 'lazer', 1.75],
        ];

        foreach ($testCases as [$inputA, $inputB, $maxScore]) {
            // Act: Calculate similarity for phonetically similar words
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Verify score is within acceptable phonetic similarity range
            $this->assertLessThanOrEqual(
                $maxScore,
                $score,
                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
            );
            $this->assertGreaterThanOrEqual(
                0.1,
                $score,
                "Should have at least minimal penalty for non-exact match. Got: $score"
            );
        }
    }

    /**
     * Test permutations and letter swaps.
     */
    public function test_permutations_and_swaps(): void
    {
        // Arrange: Define test cases with transposed letters
        $testCases = [
            ['maman', 'maamn', 2],
            ['andy', 'anyd', 1.5],
            ['evenement', 'evneement', 0.75],
            ['form', 'from', 1.5],
        ];

        foreach ($testCases as [$inputA, $inputB, $maxScore]) {
            // Act: Calculate similarity for transposed letter strings
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Verify score reflects letter transposition penalty
            $this->assertLessThanOrEqual(
                $maxScore,
                $score,
                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
            );
            $this->assertGreaterThanOrEqual(
                0.1,
                $score,
                "Should have at least minimal penalty for non-exact match. Got: $score"
            );
        }
    }

    /**
     * Test word-based comparisons.
     */
    public function test_word_based_comparisons(): void
    {
        // Arrange: Define multi-word test cases
        $testCases = [
            ['john doe', 'doe john', 0.5],
            ['andy kani', 'andy kanilendula kaniolokobo', 2],
            ['hello world', 'goodbye universe', 10],
        ];

        foreach ($testCases as [$inputA, $inputB, $maxScore]) {
            // Act: Calculate similarity between word sequences
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Verify score respects maximum boundaries
            $this->assertLessThanOrEqual(
                $maxScore,
                $score,
                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
            );
            $this->assertGreaterThanOrEqual(
                0.1,
                $score,
                "Should have at least minimal penalty for non-exact match. Got: $score"
            );
        }
    }

    /**
     * Test length penalty contribution.
     */
    public function test_length_penalty_contribution(): void
    {
        // Arrange: Define test cases expecting length-based penalties
        $testCases = [
            ['abc', 'def', 0.1],
            ['abcd', 'abc', 0.15],
            ['abcdef', 'a', 0.2],
            ['verylongstringhere', 'short', 0.4],
        ];

        foreach ($testCases as [$inputA, $inputB, $expectedMinPenalty]) {
            // Act: Calculate similarity with length differences
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Verify minimum penalty is applied for length mismatches
            $this->assertGreaterThanOrEqual(
                $expectedMinPenalty,
                $score,
                "Score too low for: '$inputA' vs '$inputB'. Got: $score, Min: $expectedMinPenalty"
            );
        }
    }

    /**
     * Test sigma parameter affects word distance.
     */
    public function test_sigma_parameter_affects_word_distance(): void
    {
        // Arrange: Define input with variations
        $inputA = 'andy kani';
        $inputB = 'adny kina';

        // Act: Calculate scores with different sigma values
        $scoreSigma1 = $this->comparator->compare($inputA, $inputB, 1.0);
        $scoreSigma2 = $this->comparator->compare($inputA, $inputB, 2.0);
        $scoreSigma05 = $this->comparator->compare($inputA, $inputB, 0.5);

        // Assert: Verify sigma parameter correctly scales similarity scores
        $this->assertGreaterThan(
            $scoreSigma1,
            $scoreSigma2,
            "Sigma=2.0 ($scoreSigma2) should give higher score than Sigma=1.0 ($scoreSigma1)"
        );
        $this->assertLessThan(
            $scoreSigma1,
            $scoreSigma05,
            "Sigma=0.5 ($scoreSigma05) should give lower score than Sigma=1.0 ($scoreSigma1)"
        );
    }

    /**
     * Test edge cases.
     */
    public function test_edge_cases(): void
    {
        // Arrange & Act & Assert: Empty strings should produce zero score
        $this->assertEqualsWithDelta(0.0, $this->comparator->compare('', ''), 0.01);

        // Act: Compare string with empty string
        $emptyScore = $this->comparator->compare('hello', '');

        // Assert: Non-zero but bounded score for empty comparison
        $this->assertGreaterThan(0.0, $emptyScore);
        $this->assertLessThan(4.0, $emptyScore);

        // Arrange & Act & Assert: Single character exact match
        $this->assertEqualsWithDelta(0.0, $this->comparator->compare('a', 'a'), 0.01);

        // Act: Compare different single characters
        $singleCharScore = $this->comparator->compare('a', 'b');

        // Assert: Non-zero score for different characters
        $this->assertGreaterThanOrEqual(0.1, $singleCharScore);

        // Arrange: Create long repetitive strings
        $longString1 = str_repeat('abc ', 100);
        $longString2 = str_repeat('acb ', 100);

        // Act: Calculate similarity for long strings
        $score = $this->comparator->compare($longString1, $longString2);

        // Assert: Score remains within reasonable bounds
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThan(11.0, $score);

        // Arrange & Act & Assert: Special characters normalize to empty strings
        $this->assertEqualsWithDelta(0.0, $this->comparator->compare('!@#$%', '!@#$%'), 0.01);
    }

    /**
     * Test match and delete algorithm.
     */
    public function test_match_and_delete_algorithm(): void
    {
        // Act: Calculate similarity with repeated letter variations
        $score1 = $this->comparator->compare('banana', 'baanna');
        $score2 = $this->comparator->compare('banana', 'bannaa');

        // Assert: Scores for repeated letters are low but non-zero
        $this->assertLessThan(1.75, $score1);
        $this->assertLessThan(1.75, $score2);
        $this->assertGreaterThan(0.1, $score1);
        $this->assertGreaterThan(0.1, $score2);

        // Act: Test palindrome exact match
        $score3 = $this->comparator->compare('level', 'level');

        // Assert: Palindrome exact match produces zero score
        $this->assertEqualsWithDelta(0.0, $score3, 0.01);
    }

    /**
     * Test dynamic penalty ceiling.
     */
    public function test_dynamic_penalty_ceiling(): void
    {
        // Arrange: Create strings with letters far apart
        $inputA = 'a' . str_repeat('x', 50) . 'b';
        $inputB = 'b' . str_repeat('y', 50) . 'a';

        // Act: Calculate similarity for distant letter positions
        $score = $this->comparator->compare($inputA, $inputB);

        // Assert: Dynamic penalty prevents unreasonable score inflation
        $this->assertGreaterThan(0.0, $score);
        $this->assertLessThan(7.5, $score);
    }

    /**
     * Test with realistic names and variations.
     */
    public function test_realistic_name_variations(): void
    {
        // Arrange: Define realistic name variations
        $testCases = [
            ['Jean-Pierre Dupont', 'Jean Pierre Dupont', 0.5],
            ['Van Der Waals', 'Van derwaals', 2.5],
            ['St. John', 'Saint John', 0.8],
        ];

        foreach ($testCases as [$inputA, $inputB, $maxScore]) {
            // Act: Calculate similarity for name variations
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Score respects maximum boundaries for name variations
            $this->assertLessThanOrEqual(
                $maxScore,
                $score,
                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
            );
        }
    }

    /**
     * Test that score is always non-negative.
     */
    public function test_score_always_non_negative(): void
    {
        // Arrange: Define diverse test cases
        $randomTests = [
            ['abcdef', 'ghijkl'],
            ['123456', '789012'],
            ['mixed123', '456mixed'],
            ['with spaces', 'withoutspaces'],
            ['UPPERCASE', 'lowercase'],
        ];

        foreach ($randomTests as [$inputA, $inputB]) {
            // Act: Calculate similarity for random pairs
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Score is always within valid range
            $this->assertGreaterThanOrEqual(0.0, $score);
            $this->assertLessThan(9.0, $score);
        }
    }

    /**
     * Test that exact matches return zero.
     */
    public function test_exact_matches_return_zero(): void
    {
        // Arrange: Define exact match test cases
        $exactCases = [
            ['test', 'test'],
            ['TEST', 'test'],
            ['Test123', 'test123'],
            ['hello world', 'hello world'],
        ];

        foreach ($exactCases as [$inputA, $inputB]) {
            // Act: Calculate similarity for exact matches
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Exact matches always produce zero score
            $this->assertEqualsWithDelta(
                0.0,
                $score,
                0.01,
                "Exact match should return 0 for: '$inputA' vs '$inputB'. Got: $score"
            );
        }
    }

    /**
     * Test substring relationships.
     */
    public function test_substring_relationships(): void
    {
        // Arrange: Define substring test cases
        $testCases = [
            ['dupont', 'martin dupont', 1.2],
            ['dupont', 'dupont martin', 1.2],
            ['john', 'john doe smith', 1.2],
            ['test', 'testing', 1.5],
        ];

        foreach ($testCases as [$inputA, $inputB, $maxScore]) {
            // Act: Calculate similarity for substring relationships
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Substring matches produce reasonable scores
            $this->assertLessThanOrEqual(
                $maxScore,
                $score,
                "Score too high for: '$inputA' vs '$inputB'. Got: $score, Max: $maxScore"
            );
            $this->assertGreaterThan(0.0, $score);
        }
    }

    /**
     * Test normalization consistency.
     */
    public function test_normalization_consistency(): void
    {
        // Arrange: Strings that should normalize to same value
        $inputA = 'ÉLÉVÉ';
        $inputB = 'eleve';

        // Act: Calculate similarity after normalization
        $score = $this->comparator->compare($inputA, $inputB);

        // Assert: Normalized strings should match exactly
        $this->assertEqualsWithDelta(
            0.0,
            $score,
            0.01,
            "Strings should match after StringNormalizer normalization"
        );
    }

    /**
     * Test sigma parameter effect is noticeable.
     */
    public function test_sigma_effect_is_noticeable(): void
    {
        // Arrange: Define multi-word test input
        $inputA = 'john doe smith';
        $inputB = 'john doe';

        // Act: Calculate scores with different sigma values
        $scoreSigma05 = $this->comparator->compare($inputA, $inputB, 0.5);
        $scoreSigma1 = $this->comparator->compare($inputA, $inputB, 1.0);
        $scoreSigma2 = $this->comparator->compare($inputA, $inputB, 2.0);

        // Calculate differences
        $difference1 = $scoreSigma1 - $scoreSigma05;
        $difference2 = $scoreSigma2 - $scoreSigma1;

        // Assert: Sigma parameter produces noticeable score differences
        $this->assertGreaterThan(0.02, $difference1, "Sigma should have noticeable effect (diff1: $difference1)");
        $this->assertGreaterThan(0.02, $difference2, "Sigma should have noticeable effect (diff2: $difference2)");
    }

    /**
     * Test that algorithm is case insensitive.
     */
    public function test_case_insensitive(): void
    {
        // Arrange: Define case variation test cases
        $testCases = [
            ['Test', 'test'],
            ['TEST CASE', 'test case'],
            ['MixedCase123', 'mixedcase123'],
        ];

        foreach ($testCases as [$inputA, $inputB]) {
            // Act: Calculate similarity with case variations
            $score = $this->comparator->compare($inputA, $inputB);

            // Assert: Case variations should not affect similarity score
            $this->assertEqualsWithDelta(
                0.0,
                $score,
                0.01,
                "Case should be ignored for: '$inputA' vs '$inputB'. Got: $score"
            );
        }
    }
}
