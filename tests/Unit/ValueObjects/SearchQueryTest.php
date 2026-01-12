<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\ValueObjects;

use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\SearchQuery;
use Fuzzy\Services\StringNormalizer;

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
        $query = SearchQuery::create('', $this->normalizer);

        $this->assertSame('', $query->originalQuery);
        $this->assertSame('', $query->normalizedQuery);
        $this->assertSame([], $query->words);
        $this->assertFalse($query->isMultiWord);
        $this->assertTrue($query->isEmpty());
    }

    public function test_create_with_single_word(): void
    {
        $query = SearchQuery::create('hello', $this->normalizer);

        $this->assertSame('hello', $query->originalQuery);
        $this->assertSame('hello', $query->normalizedQuery);
        $this->assertSame(['hello'], $query->words);
        $this->assertFalse($query->isMultiWord);
        $this->assertFalse($query->isEmpty());
    }

    public function test_create_with_multiple_words(): void
    {
        $query = SearchQuery::create('hello world test', $this->normalizer);

        $this->assertSame('hello world test', $query->originalQuery);
        $this->assertSame('hello world test', $query->normalizedQuery);
        $this->assertSame(['hello', 'world', 'test'], $query->words);
        $this->assertTrue($query->isMultiWord);
        $this->assertFalse($query->isEmpty());
    }

    public function test_create_with_special_characters(): void
    {
        $query = SearchQuery::create('Héllò, Wörld!', $this->normalizer);

        $this->assertSame('Héllò, Wörld!', $query->originalQuery);
        $this->assertSame('hello world', $query->normalizedQuery);
        $this->assertSame(['hello', 'world'], $query->words);
        $this->assertTrue($query->isMultiWord);
    }

    public function test_create_removes_stop_words(): void
    {
        config(['fuzzy.stop_words' => ['the', 'and', 'or']]);

        $query = SearchQuery::create('the quick brown fox and the lazy dog', $this->normalizer);

        $this->assertStringNotContainsString('the', $query->normalizedQuery);
        $this->assertStringNotContainsString('and', $query->normalizedQuery);
        $this->assertStringContainsString('quick', $query->normalizedQuery);
        $this->assertStringContainsString('brown', $query->normalizedQuery);
    }

    public function test_is_empty(): void
    {
        $query1 = SearchQuery::create('', $this->normalizer);
        $this->assertTrue($query1->isEmpty());

        $query2 = SearchQuery::create('   ', $this->normalizer);
        $this->assertTrue($query2->isEmpty());

        $query3 = SearchQuery::create('!!!', $this->normalizer);
        $this->assertTrue($query3->isEmpty());

        $query4 = SearchQuery::create('hello', $this->normalizer);
        $this->assertFalse($query4->isEmpty());
    }

    public function test_is_multi_word(): void
    {
        $query1 = SearchQuery::create('hello', $this->normalizer);
        $this->assertFalse($query1->isMultiWord);

        $query2 = SearchQuery::create('hello world', $this->normalizer);
        $this->assertTrue($query2->isMultiWord);

        $query3 = SearchQuery::create('hello world test', $this->normalizer);
        $this->assertTrue($query3->isMultiWord);
    }

    public function test_words_are_normalized(): void
    {
        $query = SearchQuery::create('HELLO world TEST', $this->normalizer);

        $this->assertSame(['hello', 'world', 'test'], $query->words);
    }

    public function test_query_with_only_stop_words(): void
    {
        config(['fuzzy.stop_words' => ['the', 'and']]);

        // Avec la logique actuelle, "the and the" (3 mots) n'est PAS filtré
        // Il faut 4+ mots pour que les stop words soient supprimés

        // Tester avec 4 stop words
        $query = SearchQuery::create('the and the and', $this->normalizer);
        $this->assertTrue($query->isEmpty());
        $this->assertSame('', $query->normalizedQuery);
        $this->assertSame([], $query->words);

        // Tester que 3 stop words ne sont PAS filtrés (comportement attendu)
        $query2 = SearchQuery::create('the and the', $this->normalizer);
        $this->assertFalse($query2->isEmpty());
        $this->assertSame('the and the', $query2->normalizedQuery);
    }

    public function test_query_with_mixed_case_and_spacing(): void
    {
        $query = SearchQuery::create('  Hello   WORLD   Test  ', $this->normalizer);

        $this->assertSame('  Hello   WORLD   Test  ', $query->originalQuery);
        $this->assertSame('hello world test', $query->normalizedQuery);
        $this->assertSame(['hello', 'world', 'test'], $query->words);
    }
}
