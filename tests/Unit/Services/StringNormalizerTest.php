<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\TestCase;

final class StringNormalizerTest extends TestCase
{
    private StringNormalizer $normalizer;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StringNormalizer();
    }

    /**
     * Test that empty or whitespace-only strings are normalized to empty strings.
     */
    public function test_normalize_empty_string(): void
    {
        // Arrange: Prepare empty and whitespace-only strings
        $emptyString = '';
        $whitespaceString = '   ';

        // Act: Normalize the strings
        $emptyResult = $this->normalizer->normalize($emptyString);
        $whitespaceResult = $this->normalizer->normalize($whitespaceString);

        // Assert: Both should return empty strings
        $this->assertSame('', $emptyResult);
        $this->assertSame('', $whitespaceResult);
    }

    /**
     * Test basic string normalization with mixed case and numbers.
     */
    public function test_normalize_basic_string(): void
    {
        // Arrange: Create a string with mixed case and numbers
        $input = 'Hello World! 123';
        $expected = 'hello world 123';

        // Act: Normalize the string
        $result = $this->normalizer->normalize($input);

        // Assert: Should lowercase, remove punctuation, keep numbers
        $this->assertSame($expected, $result);
    }

    /**
     * Test normalization removes special characters and normalizes accented characters.
     */
    public function test_normalize_with_special_characters(): void
    {
        // Arrange: String with accented characters and special symbols
        $input = 'Héllò Wörld@#$%';
        $expected = 'hello world';

        // Act: Normalize the string
        $result = $this->normalizer->normalize($input);

        // Assert: Should remove special characters and normalize accents
        $this->assertSame($expected, $result);
    }

    /**
     * Test normalization trims and collapses multiple spaces.
     */
    public function test_normalize_with_extra_spaces(): void
    {
        // Arrange: String with leading/trailing and multiple spaces
        $input = '  Hello    World  ';
        $expected = 'hello world';

        // Act: Normalize the string
        $result = $this->normalizer->normalize($input);

        // Assert: Should trim and collapse spaces to single spaces
        $this->assertSame($expected, $result);
    }

    /**
     * Test normalization preserves dashes and underscores.
     */
    public function test_normalize_preserves_dash_and_underscore(): void
    {
        // Arrange: String with dash and underscore
        $input = 'hello-world_test';
        $expected = 'hello-world_test';

        // Act: Normalize the string
        $result = $this->normalizer->normalize($input);

        // Assert: Should preserve dash and underscore characters
        $this->assertSame($expected, $result);
    }

    /**
     * Test splitting empty string returns empty array.
     */
    public function test_split_into_words_empty(): void
    {
        // Arrange: Empty string
        $input = '';

        // Act: Split into words
        $result = $this->normalizer->splitIntoWords($input);

        // Assert: Should return empty array
        $this->assertSame([], $result);
    }

    /**
     * Test splitting basic string into words.
     */
    public function test_split_into_words_basic(): void
    {
        // Arrange: Simple space-separated string
        $input = 'hello world test';
        $expected = ['hello', 'world', 'test'];

        // Act: Split into words
        $result = $this->normalizer->splitIntoWords($input);

        // Assert: Should split by spaces
        $this->assertSame($expected, $result);
    }

    /**
     * Test splitting string with dash and underscore separators.
     */
    public function test_split_into_words_with_dash_underscore(): void
    {
        // Arrange: String with dash, underscore, and space separators
        $input = 'hello-world_test example';
        $expected = ['hello', 'world', 'test', 'example'];

        // Act: Split into words
        $result = $this->normalizer->splitIntoWords($input);

        // Assert: Should split by dash, underscore, and space
        $this->assertSame($expected, $result);
    }

    /**
     * Test splitting string with multiple consecutive spaces.
     */
    public function test_split_into_words_with_multiple_spaces(): void
    {
        // Arrange: String with multiple spaces between words
        $input = 'hello   world   test';
        $expected = ['hello', 'world', 'test'];

        // Act: Split into words
        $result = $this->normalizer->splitIntoWords($input);

        // Assert: Should handle multiple spaces correctly
        $this->assertSame($expected, $result);
    }

    /**
     * Test basic query normalization without stop word removal.
     */
    public function test_normalize_query_basic(): void
    {
        // Arrange: Simple query without stop words
        $input = 'hello world';
        $expected = 'hello world';

        // Act: Normalize query
        $result = $this->normalizer->normalizeQuery($input);

        // Assert: Should return normalized but unchanged for this input
        $this->assertSame($expected, $result);
    }

    /**
     * Test query normalization removes common stop words.
     */
    public function test_normalize_query_removes_stop_words(): void
    {
        // Arrange: Query containing multiple stop words
        $input = 'the quick brown fox jumps over the lazy dog';

        // Act: Normalize query
        $result = $this->normalizer->normalizeQuery($input);

        // Assert: Should remove stop words and keep content words
        $words = explode(' ', $result);
        $this->assertNotContains('the', $words);
        $this->assertNotContains('over', $words);
        $this->assertContains('quick', $words);
        $this->assertContains('brown', $words);
        $this->assertContains('fox', $words);
        $this->assertContains('jumps', $words);
        $this->assertContains('lazy', $words);
        $this->assertContains('dog', $words);
    }

    /**
     * Test short queries preserve stop words for context.
     */
    public function test_normalize_query_does_not_remove_stop_words_for_short_queries(): void
    {
        // Arrange: Very short query with stop word
        $input = 'the cat';

        // Act: Normalize query
        $result = $this->normalizer->normalizeQuery($input);

        // Assert: Should keep stop words for short queries
        $this->assertSame('the cat', $result);
    }

    /**
     * Test keyword extraction with limit and stop word filtering.
     */
    public function test_extract_keywords(): void
    {
        // Arrange: Query with stop words and content words
        $input = 'the quick brown fox jumps over the lazy dog';
        $expectedKeywords = ['brown', 'dog', 'fox'];

        // Act: Extract keywords with limit
        $keywords = $this->normalizer->extractKeywords($input, 3);

        // Assert: Should return limited number of keywords without stop words
        $this->assertCount(3, $keywords);
        $this->assertSame($expectedKeywords, $keywords);
    }

    /**
     * Test keyword extraction respects the limit parameter.
     */
    public function test_extract_keywords_with_limit(): void
    {
        // Arrange: String with many words
        $input = 'one two three four five six seven eight nine ten';

        // Act: Extract keywords with specific limit
        $keywords = $this->normalizer->extractKeywords($input, 5);

        // Assert: Should return exactly the limit number of keywords
        $this->assertCount(5, $keywords);
    }

    /**
     * Test keyword extraction removes all stop words.
     */
    public function test_extract_keywords_removes_stop_words(): void
    {
        // Arrange: String containing only stop words
        $input = 'the and or a an in on at';

        // Act: Extract keywords
        $keywords = $this->normalizer->extractKeywords($input, 10);

        // Assert: Should return empty array when only stop words present
        $this->assertEmpty($keywords);
    }

    /**
     * Test keyword extraction removes short words.
     */
    public function test_extract_keywords_removes_short_words(): void
    {
        // Arrange: Mix of short and longer words
        $input = 'a be cat do egg';

        // Act: Extract keywords
        $keywords = $this->normalizer->extractKeywords($input);

        // Assert: Should keep longer words, remove short ones
        $this->assertContains('cat', $keywords);
        $this->assertContains('egg', $keywords);
        $this->assertNotContains('a', $keywords);
        $this->assertNotContains('be', $keywords);
        $this->assertNotContains('do', $keywords);
    }
}
