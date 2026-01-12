<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Tests\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class SimilarityCalculatorTest extends TestCase
{
    private SimilarityCalculator $calculator;

    /**
     * Set up test dependencies.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SimilarityCalculator();
    }

    /**
     * Test exact word match similarity calculation.
     */
    public function test_word_similarity_exact_match(): void
    {
        // Arrange: Two identical words
        $word1 = 'hello';
        $word2 = 'hello';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateWordSimilarity($word1, $word2);

        // Assert: Should return perfect similarity
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
    }

    /**
     * Test word similarity calculation with different cases.
     */
    public function test_word_similarity_case_insensitive(): void
    {
        // Arrange: Same word with different cases
        $uppercaseWord = 'HELLO';
        $lowercaseWord = 'hello';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateWordSimilarity($uppercaseWord, $lowercaseWord);

        // Assert: Should return perfect similarity (case-insensitive)
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
    }

    /**
     * Test word similarity calculation with empty strings.
     */
    public function test_word_similarity_empty_strings(): void
    {
        // Arrange: Various empty string combinations
        $emptyString = '';
        $nonEmptyString = 'hello';

        // Act & Assert: Empty vs non-empty should return zero similarity
        $this->assertEqualsWithDelta(0.0, $this->calculator->calculateWordSimilarity($emptyString, $nonEmptyString), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $this->calculator->calculateWordSimilarity($nonEmptyString, $emptyString), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $this->calculator->calculateWordSimilarity($emptyString, $emptyString), PHP_FLOAT_EPSILON);
    }

    /**
     * Test word similarity when one word is contained within another.
     */
    public function test_word_similarity_with_contained_word(): void
    {
        // Arrange: Word that is contained within another
        $shortWord = 'cat';
        $longWord = 'category';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateWordSimilarity($shortWord, $longWord);

        // Assert: Should have high similarity but not perfect
        $this->assertGreaterThan(0.7, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }

    /**
     * Test word similarity calculation with similar words.
     */
    public function test_word_similarity_with_similar_words(): void
    {
        // Arrange: Similar but not identical words
        $word1 = 'kitten';
        $word2 = 'sitting';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateWordSimilarity($word1, $word2);

        // Assert: Should have moderate similarity
        $this->assertGreaterThan(0.0, $similarity);
        $this->assertLessThan(1.0, $similarity);
    }

    /**
     * Test word similarity calculation with completely different words.
     */
    public function test_word_similarity_with_completely_different_words(): void
    {
        // Arrange: Two unrelated words
        $word1 = 'apple';
        $word2 = 'orange';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateWordSimilarity($word1, $word2);

        // Assert: Should have low similarity
        $this->assertLessThan(0.5, $similarity);
    }

    /**
     * Test phrase similarity calculation with exact match.
     */
    public function test_calculate_similarity_exact_match(): void
    {
        // Arrange: Two identical phrases
        $phrase1 = 'hello world';
        $phrase2 = 'hello world';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Should return perfect similarity
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
    }

    /**
     * Test phrase similarity calculation with similar phrases.
     */
    public function test_calculate_similarity_similar_phrases(): void
    {
        // Arrange: Phrases with one word difference
        $phrase1 = 'quick brown fox';
        $phrase2 = 'fast brown fox';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Should have high similarity
        $this->assertGreaterThan(0.5, $similarity);
    }

    /**
     * Test phrase similarity calculation with completely different phrases.
     */
    public function test_calculate_similarity_completely_different(): void
    {
        // Arrange: Completely different phrases
        $phrase1 = 'hello world';
        $phrase2 = 'goodbye universe';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Should have very low similarity
        $this->assertLessThan(0.3, $similarity);
    }

    /**
     * Test phrase similarity calculation with short words.
     */
    public function test_calculate_similarity_with_short_words(): void
    {
        // Arrange: Phrases with single-letter words
        $phrase1 = 'a b c';
        $phrase2 = 'x y z';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Should return zero similarity
        $this->assertEqualsWithDelta(0.0, $similarity, PHP_FLOAT_EPSILON);
    }

    /**
     * Test phrase similarity calculation with special characters.
     */
    public function test_calculate_similarity_with_special_characters(): void
    {
        // Arrange: Phrases with hyphen vs space
        $phrase1 = 'hello-world';
        $phrase2 = 'hello world';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Should have high similarity
        $this->assertGreaterThan(0.8, $similarity);
    }

    /**
     * Test adding a custom similarity algorithm.
     */
    public function test_add_custom_algorithm(): void
    {
        // Arrange: Create mock algorithm
        $mockAlgorithm = $this->createMock(SimilarityAlgorithmInterface::class);
        $mockAlgorithm->method('calculate')->willReturn(0.5);
        $mockAlgorithm->method('getName')->willReturn('test');
        $mockAlgorithm->method('getWeight')->willReturn(0.5);

        // Act: Add custom algorithm
        $this->calculator->addAlgorithm($mockAlgorithm);

        // Assert: Calculator should still function correctly
        $similarity = $this->calculator->calculateWordSimilarity('test', 'test');
        $this->assertEqualsWithDelta(1.0, $similarity, PHP_FLOAT_EPSILON);
    }

    /**
     * Test similarity calculation with spaces and punctuation.
     */
    public function test_similarity_with_spaces_and_punctuation(): void
    {
        // Arrange: Phrases with punctuation and different formatting
        $phrase1 = 'Hello, World! How are you?';
        $phrase2 = 'hello world how are you';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Should have very high similarity despite formatting differences
        $this->assertGreaterThan(0.9, $similarity);
    }

    /**
     * Test that similarity calculation provides consistent results.
     */
    public function test_similarity_provides_consistent_results(): void
    {
        // Arrange: Same input for two calculations
        $phrase1 = 'test';
        $phrase2 = 'test';

        // Act: Calculate similarity twice
        $similarity1 = $this->calculator->calculateSimilarity($phrase1, $phrase2);
        $similarity2 = $this->calculator->calculateSimilarity($phrase1, $phrase2);

        // Assert: Results should be identical
        $this->assertSame($similarity1, $similarity2);
    }

    /**
     * Test that similarity values stay within valid boundaries.
     */
    public function test_similarity_boundaries(): void
    {
        // Arrange: Two different single-letter words
        $word1 = 'a';
        $word2 = 'b';

        // Act: Calculate similarity
        $similarity = $this->calculator->calculateSimilarity($word1, $word2);

        // Assert: Similarity should be between 0 and 1 inclusive
        $this->assertGreaterThanOrEqual(0.0, $similarity);
        $this->assertLessThanOrEqual(1.0, $similarity);
    }
}
