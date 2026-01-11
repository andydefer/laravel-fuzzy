<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Tests\TestCase;
use Fuzzy\Services\StringNormalizer;

class StringNormalizerTest extends TestCase
{
    private StringNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StringNormalizer();
    }

    public function test_normalize_empty_string(): void
    {
        $this->assertSame('', $this->normalizer->normalize(''));
        $this->assertSame('', $this->normalizer->normalize('   '));
    }

    public function test_normalize_basic_string(): void
    {
        $input = 'Hello World! 123';
        $expected = 'hello world 123';
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    public function test_normalize_with_special_characters(): void
    {
        $input = 'Héllò Wörld@#$%';
        $expected = 'hello world';
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    public function test_normalize_with_extra_spaces(): void
    {
        $input = '  Hello    World  ';
        $expected = 'hello world';
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    public function test_normalize_preserves_dash_and_underscore(): void
    {
        $input = 'hello-world_test';
        $expected = 'hello-world_test';
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    public function test_split_into_words_empty(): void
    {
        $this->assertSame([], $this->normalizer->splitIntoWords(''));
    }

    public function test_split_into_words_basic(): void
    {
        $input = 'hello world test';
        $expected = ['hello', 'world', 'test'];
        $this->assertSame($expected, $this->normalizer->splitIntoWords($input));
    }

    public function test_split_into_words_with_dash_underscore(): void
    {
        $input = 'hello-world_test example';
        $expected = ['hello', 'world', 'test', 'example'];
        $this->assertSame($expected, $this->normalizer->splitIntoWords($input));
    }

    public function test_split_into_words_with_multiple_spaces(): void
    {
        $input = 'hello   world   test';
        $expected = ['hello', 'world', 'test'];
        $this->assertSame($expected, $this->normalizer->splitIntoWords($input));
    }

    public function test_normalize_query_basic(): void
    {
        $input = 'hello world';
        $expected = 'hello world';
        $this->assertSame($expected, $this->normalizer->normalizeQuery($input));
    }

    public function test_normalize_query_removes_stop_words(): void
    {
        $input = 'the quick brown fox jumps over the lazy dog';
        $normalized = $this->normalizer->normalizeQuery($input);

        // Should remove 'the', 'over'
        $words = explode(' ', $normalized);
        $this->assertNotContains('the', $words);
        $this->assertNotContains('over', $words);
        $this->assertContains('quick', $words);
        $this->assertContains('brown', $words);
    }

    public function test_normalize_query_does_not_remove_stop_words_for_short_queries(): void
    {
        $input = 'the cat';
        $this->assertSame('the cat', $this->normalizer->normalizeQuery($input));
    }

    public function test_extract_keywords(): void
    {
        $input = 'the quick brown fox jumps over the lazy dog';
        $keywords = $this->normalizer->extractKeywords($input, 3);

        $this->assertCount(3, $keywords);


        $this->assertEquals(['brown', 'dog', 'fox'], $keywords);

        // Alternative: Vérifier sans ordre spécifique
        $expectedPossible = ['brown', 'dog', 'fox', 'jumps', 'lazy', 'quick'];
        foreach ($keywords as $keyword) {
            $this->assertContains($keyword, $expectedPossible);
        }
    }
    public function test_extract_keywords_with_limit(): void
    {
        $input = 'one two three four five six seven eight nine ten';
        $keywords = $this->normalizer->extractKeywords($input, 5);

        $this->assertCount(5, $keywords);
    }

    public function test_extract_keywords_removes_stop_words(): void
    {
        $input = 'the and or a an in on at';
        $keywords = $this->normalizer->extractKeywords($input, 10);

        $this->assertEmpty($keywords);
    }

    public function test_extract_keywords_removes_short_words(): void
    {
        $input = 'a be cat do egg';
        $keywords = $this->normalizer->extractKeywords($input);

        $this->assertContains('cat', $keywords);
        $this->assertContains('egg', $keywords);
        $this->assertNotContains('a', $keywords);
        $this->assertNotContains('be', $keywords);
        $this->assertNotContains('do', $keywords);
    }
}
