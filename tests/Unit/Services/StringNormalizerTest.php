<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Services\StringNormalizer;
use Fuzzy\Tests\TestCase;

final class StringNormalizerTest extends TestCase
{
    private StringNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StringNormalizer();
        // Note: Les stop words sont maintenant internes au package
        // On ne peut plus les configurer via config()
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

    /**
     * Test basic query normalization.
     * Note: 'hello' est un stop word, donc il est supprimé.
     * Utilisons 'php' qui n'est pas un stop word.
     */
    public function test_normalize_query_basic(): void
    {
        $input = 'php laravel';
        $expected = 'php laravel';
        $result = $this->normalizer->normalizeQuery($input);
        $this->assertSame($expected, $result);
    }

    public function test_normalize_query_removes_stop_words(): void
    {
        $input = 'the quick brown fox jumps over the lazy dog';
        $result = $this->normalizer->normalizeQuery($input);
        $words = explode(' ', $result);
        $this->assertNotContains('the', $words);
        $this->assertNotContains('over', $words);
        $this->assertContains('quick', $words);
        $this->assertContains('brown', $words);
    }

    public function test_normalize_query_does_not_remove_stop_words_for_short_queries(): void
    {
        $input = 'the cat';
        $result = $this->normalizer->normalizeQueryWithLengthLimit($input);
        $this->assertSame('the cat', $result);
    }

    public function test_extract_keywords(): void
    {
        $input = 'the quick brown fox jumps over the lazy dog';
        $keywords = $this->normalizer->extractKeywords($input, 3);
        $this->assertCount(3, $keywords);
        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('over', $keywords);
    }

    /**
     * Test keyword extraction respects limit.
     * Note: Les chiffres 'one', 'two', etc. peuvent être filtrés.
     * Utilisons des mots normaux.
     */
    public function test_extract_keywords_with_limit(): void
    {
        $input = 'php laravel symfony react vue javascript typescript';
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

    public function test_protected_fields_preserve_stop_words(): void
    {
        $protectedFields = ['name', 'email'];
        $this->normalizer->setProtectedFields($protectedFields);

        $input = 'Jean de La Fontaine';
        $this->normalizer->setCurrentField('name');
        $result = $this->normalizer->normalizeQuery($input);

        $this->assertEquals('jean de la fontaine', $result);

        $this->normalizer->setCurrentField(null);
        $this->normalizer->setProtectedFields([]);
    }

    /**
     * Test that non-protected fields remove stop words.
     * Note: Pour que les mots français soient supprimés, il faudrait utiliser la locale française.
     * Ce test utilise l'anglais par défaut, donc les mots français ne sont PAS des stop words.
     */
    public function test_non_protected_fields_remove_stop_words(): void
    {
        $this->normalizer->setProtectedFields(['name', 'email']);

        // Utiliser des mots anglais qui SONT des stop words
        $input = 'the cat and the dog are in the house';
        $this->normalizer->setCurrentField('description');
        $result = $this->normalizer->normalizeQuery($input);

        // 'the', 'and', 'are', 'in' sont supprimés, reste 'cat dog house'
        $this->assertEquals('cat dog house', $result);

        $this->normalizer->setCurrentField(null);
        $this->normalizer->setProtectedFields([]);
    }

    /**
     * Test that normalizeForField respects protected field configuration.
     */
    public function test_normalize_for_field_respects_protected_status(): void
    {
        $this->normalizer->setProtectedFields(['full_name']);

        $value = 'John and Jane Doe';

        // Champ protégé : les stop words sont conservés
        $resultProtected = $this->normalizer->normalizeForField($value, 'full_name');
        $this->assertEquals('john and jane doe', $resultProtected);

        // Champ non protégé : les stop words sont supprimés
        $resultNonProtected = $this->normalizer->normalizeForField($value, 'description');
        $this->assertEquals('john jane doe', $resultNonProtected);

        $this->normalizer->setProtectedFields([]);
    }

    public function test_should_preserve_stop_words(): void
    {
        $this->normalizer->setProtectedFields(['name', 'email', 'username']);

        $this->assertTrue($this->normalizer->shouldPreserveStopWords('name'));
        $this->assertTrue($this->normalizer->shouldPreserveStopWords('email'));
        $this->assertTrue($this->normalizer->shouldPreserveStopWords('username'));
        $this->assertFalse($this->normalizer->shouldPreserveStopWords('description'));
        $this->assertFalse($this->normalizer->shouldPreserveStopWords('content'));

        $this->normalizer->setProtectedFields([]);
    }

    /**
     * Test that email addresses are normalized (special chars removed).
     * C'est le comportement normal : normalize() supprime @ . + etc.
     */
    public function test_email_field_normalization(): void
    {
        $email = 'john.doe+test@example.com';
        $result = $this->normalizer->normalize($email);
        // Les caractères spéciaux sont supprimés par normalize()
        $this->assertEquals('johndoetestexamplecom', $result);
    }

    public function test_name_with_multiple_stop_words(): void
    {
        $this->normalizer->setProtectedFields(['name']);

        $name = 'Charles de Gaulle et Jean de La Fontaine';
        $this->normalizer->setCurrentField('name');
        $result = $this->normalizer->normalizeQuery($name);

        $this->assertEquals('charles de gaulle et jean de la fontaine', $result);

        $this->normalizer->setCurrentField(null);
        $this->normalizer->setProtectedFields([]);
    }

    public function test_get_current_field(): void
    {
        $this->assertNull($this->normalizer->getCurrentField());
        $this->normalizer->setCurrentField('name');
        $this->assertEquals('name', $this->normalizer->getCurrentField());
        $this->normalizer->setCurrentField(null);
    }

    public function test_set_protected_fields_returns_self(): void
    {
        $result = $this->normalizer->setProtectedFields(['name', 'email']);
        $this->assertSame($this->normalizer, $result);
    }

    public function test_set_current_field_returns_self(): void
    {
        $result = $this->normalizer->setCurrentField('name');
        $this->assertSame($this->normalizer, $result);
        $this->normalizer->setCurrentField(null);
    }

    public function test_get_protected_fields(): void
    {
        $protectedFields = ['name', 'email', 'username'];
        $this->normalizer->setProtectedFields($protectedFields);
        $this->assertEquals($protectedFields, $this->normalizer->getProtectedFields());
        $this->normalizer->setProtectedFields([]);
    }
}
