<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\ValueObjects;

use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\SearchQuery;

final class SearchQueryTest extends TestCase
{
    private StringNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StringNormalizer();
    }

    public function test_create_with_empty_query(): void
    {
        // Arrange: Create search query with empty string
        $query = SearchQuery::create(
            query: '',
            normalizer: $this->normalizer
        );

        // Assert: Query should be empty with no words
        $this->assertSame('', $query->originalQuery);
        $this->assertSame('', $query->normalizedQuery);
        $this->assertSame([], $query->words);
        $this->assertFalse($query->isMultiWord);
        $this->assertTrue($query->isEmpty());
    }

    public function test_create_with_single_word(): void
    {
        // Arrange: Create search query with single word
        $query = SearchQuery::create(
            query: 'hello',
            normalizer: $this->normalizer
        );

        // Assert: Query should contain single word with no normalization needed
        $this->assertSame('hello', $query->originalQuery);
        $this->assertSame('hello', $query->normalizedQuery);
        $this->assertSame(['hello'], $query->words);
        $this->assertFalse($query->isMultiWord);
        $this->assertFalse($query->isEmpty());
    }

    public function test_create_with_multiple_words(): void
    {
        // Arrange: Create search query with multiple words
        $query = SearchQuery::create(
            query: 'hello world test',
            normalizer: $this->normalizer
        );

        // Assert: Query should contain all words and be marked as multi-word
        $this->assertSame('hello world test', $query->originalQuery);
        $this->assertSame('hello world test', $query->normalizedQuery);
        $this->assertSame(['hello', 'world', 'test'], $query->words);
        $this->assertTrue($query->isMultiWord);
        $this->assertFalse($query->isEmpty());
    }

    public function test_create_with_special_characters(): void
    {
        // Arrange: Create search query with accented characters and punctuation
        $query = SearchQuery::create(
            query: 'Héllò, Wörld!',
            normalizer: $this->normalizer
        );

        // Assert: Special characters should be normalized and punctuation removed
        $this->assertSame('Héllò, Wörld!', $query->originalQuery);
        $this->assertSame('hello world', $query->normalizedQuery);
        $this->assertSame(['hello', 'world'], $query->words);
        $this->assertTrue($query->isMultiWord);
    }

    public function test_create_removes_stop_words(): void
    {
        // Arrange: Configure stop words in config
        config(['fuzzy.stop_words' => ['the', 'and', 'or']]);

        // Act: Create query containing stop words
        $query = SearchQuery::create(
            query: 'the quick brown fox and the lazy dog',
            normalizer: $this->normalizer
        );

        // Assert: Stop words should be removed from normalized query
        $this->assertStringNotContainsString('the', $query->normalizedQuery);
        $this->assertStringNotContainsString('and', $query->normalizedQuery);
        $this->assertStringContainsString('quick', $query->normalizedQuery);
        $this->assertStringContainsString('brown', $query->normalizedQuery);
    }

    public function test_is_empty(): void
    {
        // Arrange & Act: Create queries with various empty patterns
        $emptyQuery = SearchQuery::create(
            query: '',
            normalizer: $this->normalizer
        );
        $whitespaceQuery = SearchQuery::create(
            query: '   ',
            normalizer: $this->normalizer
        );
        $punctuationQuery = SearchQuery::create(
            query: '!!!',
            normalizer: $this->normalizer
        );
        $nonEmptyQuery = SearchQuery::create(
            query: 'hello',
            normalizer: $this->normalizer
        );

        // Assert: Only actual content should not be empty
        $this->assertTrue($emptyQuery->isEmpty());
        $this->assertTrue($whitespaceQuery->isEmpty());
        $this->assertTrue($punctuationQuery->isEmpty());
        $this->assertFalse($nonEmptyQuery->isEmpty());
    }

    public function test_is_multi_word(): void
    {
        // Arrange & Act: Create queries with different word counts
        $singleWordQuery = SearchQuery::create(
            query: 'hello',
            normalizer: $this->normalizer
        );
        $twoWordQuery = SearchQuery::create(
            query: 'hello world',
            normalizer: $this->normalizer
        );
        $multiWordQuery = SearchQuery::create(
            query: 'hello world test',
            normalizer: $this->normalizer
        );

        // Assert: Only queries with 2+ words should be multi-word
        $this->assertFalse($singleWordQuery->isMultiWord);
        $this->assertTrue($twoWordQuery->isMultiWord);
        $this->assertTrue($multiWordQuery->isMultiWord);
    }

    public function test_words_are_normalized(): void
    {
        // Arrange: Create query with mixed case words
        $query = SearchQuery::create(
            query: 'HELLO world TEST',
            normalizer: $this->normalizer
        );

        // Assert: All words should be lowercased in normalized form
        $this->assertSame(['hello', 'world', 'test'], $query->words);
    }

    public function test_query_with_only_stop_words(): void
    {
        // Arrange: Configure stop words
        config(['fuzzy.stop_words' => ['the', 'and']]);

        // Act: Create query with only stop words
        $fourStopWordsQuery = SearchQuery::create(
            query: 'the and the and',
            normalizer: $this->normalizer
        );
        $threeStopWordsQuery = SearchQuery::create(
            query: 'the and the',
            normalizer: $this->normalizer
        );

        // Assert: Stop words behavior should follow configured logic
        $this->assertTrue($fourStopWordsQuery->isEmpty());
        $this->assertSame('', $fourStopWordsQuery->normalizedQuery);
        $this->assertSame([], $fourStopWordsQuery->words);

        $this->assertFalse($threeStopWordsQuery->isEmpty());
        $this->assertSame('the and the', $threeStopWordsQuery->normalizedQuery);
    }

    public function test_query_with_mixed_case_and_spacing(): void
    {
        // Arrange: Create query with irregular spacing and mixed case
        $query = SearchQuery::create(
            query: '  Hello   WORLD   Test  ',
            normalizer: $this->normalizer
        );

        // Assert: Original should preserve format, normalized should be clean
        $this->assertSame('  Hello   WORLD   Test  ', $query->originalQuery);
        $this->assertSame('hello world test', $query->normalizedQuery);
        $this->assertSame(['hello', 'world', 'test'], $query->words);
    }
}
