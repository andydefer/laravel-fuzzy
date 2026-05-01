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
        $query = SearchQuery::create(
            query: '',
            normalizer: $this->normalizer
        );

        $this->assertSame('', $query->originalQuery);
        $this->assertSame('', $query->normalizedQuery);
        $this->assertSame([], $query->words);
        $this->assertFalse($query->isMultiWord);
        $this->assertTrue($query->isEmpty());
    }

    /**
     * Test avec un mot qui n'est PAS un stop word.
     * 'php' n'est pas dans la liste des stop words.
     */
    public function test_create_with_single_word(): void
    {
        $query = SearchQuery::create(
            query: 'php',
            normalizer: $this->normalizer
        );

        $this->assertSame('php', $query->originalQuery);
        $this->assertSame('php', $query->normalizedQuery);
        $this->assertSame(['php'], $query->words);
        $this->assertFalse($query->isMultiWord);
        $this->assertFalse($query->isEmpty());
    }

    /**
     * Test avec des mots qui ne sont PAS des stop words.
     * 'php', 'laravel', 'symfony' ne sont pas dans la liste des stop words.
     */
    public function test_create_with_multiple_words(): void
    {
        $query = SearchQuery::create(
            query: 'php laravel symfony',
            normalizer: $this->normalizer
        );

        $this->assertSame('php laravel symfony', $query->originalQuery);
        $this->assertSame('php laravel symfony', $query->normalizedQuery);
        $this->assertSame(['php', 'laravel', 'symfony'], $query->words);
        $this->assertTrue($query->isMultiWord);
        $this->assertFalse($query->isEmpty());
    }

    /**
     * Test avec des caractères spéciaux.
     * 'php' et 'laravel' ne sont pas des stop words.
     */
    public function test_create_with_special_characters(): void
    {
        $query = SearchQuery::create(
            query: 'PhpP, Laravel!',
            normalizer: $this->normalizer
        );

        $this->assertSame('PhpP, Laravel!', $query->originalQuery);
        $this->assertSame('phpp laravel', $query->normalizedQuery);
        $this->assertSame(['phpp', 'laravel'], $query->words);
        $this->assertTrue($query->isMultiWord);
    }

    public function test_create_removes_stop_words(): void
    {
        $query = SearchQuery::create(
            query: 'the quick brown fox and the lazy dog',
            normalizer: $this->normalizer
        );

        // Les stop words 'the' et 'and' sont supprimés
        $this->assertStringNotContainsString('the', $query->normalizedQuery);
        $this->assertStringNotContainsString('and', $query->normalizedQuery);
        $this->assertStringContainsString('quick', $query->normalizedQuery);
        $this->assertStringContainsString('brown', $query->normalizedQuery);
        $this->assertStringContainsString('fox', $query->normalizedQuery);
        $this->assertStringContainsString('lazy', $query->normalizedQuery);
        $this->assertStringContainsString('dog', $query->normalizedQuery);
    }

    public function test_is_empty(): void
    {
        $emptyQuery = SearchQuery::create(query: '', normalizer: $this->normalizer);
        $whitespaceQuery = SearchQuery::create(query: '   ', normalizer: $this->normalizer);
        $punctuationQuery = SearchQuery::create(query: '!!!', normalizer: $this->normalizer);
        $nonEmptyQuery = SearchQuery::create(query: 'php', normalizer: $this->normalizer);

        $this->assertTrue($emptyQuery->isEmpty());
        $this->assertTrue($whitespaceQuery->isEmpty());
        $this->assertTrue($punctuationQuery->isEmpty());
        $this->assertFalse($nonEmptyQuery->isEmpty());
    }

    public function test_is_multi_word(): void
    {
        $singleWordQuery = SearchQuery::create(query: 'php', normalizer: $this->normalizer);
        $twoWordQuery = SearchQuery::create(query: 'php laravel', normalizer: $this->normalizer);
        $multiWordQuery = SearchQuery::create(query: 'php laravel symfony', normalizer: $this->normalizer);

        $this->assertFalse($singleWordQuery->isMultiWord);
        $this->assertTrue($twoWordQuery->isMultiWord);
        $this->assertTrue($multiWordQuery->isMultiWord);
    }

    public function test_words_are_normalized(): void
    {
        $query = SearchQuery::create(query: 'PHP LARAVEL SYMFONY', normalizer: $this->normalizer);

        $this->assertSame(['php', 'laravel', 'symfony'], $query->words);
    }

    public function test_query_with_only_stop_words(): void
    {
        // 'the and the and' ne contient que des stop words -> devient vide
        $fourStopWordsQuery = SearchQuery::create(
            query: 'the and the and',
            normalizer: $this->normalizer
        );

        $threeStopWordsQuery = SearchQuery::create(
            query: 'the and the',
            normalizer: $this->normalizer
        );

        $this->assertTrue($fourStopWordsQuery->isEmpty());
        $this->assertSame('', $fourStopWordsQuery->normalizedQuery);
        $this->assertSame([], $fourStopWordsQuery->words);

        $this->assertTrue($threeStopWordsQuery->isEmpty());
        $this->assertSame('', $threeStopWordsQuery->normalizedQuery);
        $this->assertSame([], $threeStopWordsQuery->words);
    }

    public function test_query_with_mixed_case_and_spacing(): void
    {
        $query = SearchQuery::create(
            query: '  PHP   LARAVEL   SYMFONY  ',
            normalizer: $this->normalizer
        );

        $this->assertSame('  PHP   LARAVEL   SYMFONY  ', $query->originalQuery);
        $this->assertSame('php laravel symfony', $query->normalizedQuery);
        $this->assertSame(['php', 'laravel', 'symfony'], $query->words);
    }

    public function test_query_with_length_limit_keeps_stop_words_for_short_queries(): void
    {
        // Test spécifique pour normalizeQueryWithLengthLimit
        $query = SearchQuery::create(
            query: 'the and the',
            normalizer: $this->normalizer
        );

        // Avec normalizeQuery standard, les stop words sont retirés
        $this->assertTrue($query->isEmpty());

        // Avec normalizeQueryWithLengthLimit, les stop words sont conservés pour les requêtes courtes
        $normalizedQuery = $this->normalizer->normalizeQueryWithLengthLimit('the and the');
        $this->assertSame('the and the', $normalizedQuery);
    }
}
