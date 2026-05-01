<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services\Algorithms\WordSimilarity;

use Fuzzy\Config\WordSimilarityComparatorConfig;
use Fuzzy\Services\Algorithms\WordSimilarity\LetterDistanceCalculator;
use Fuzzy\Tests\TestCase;

final class LetterDistanceCalculatorTest extends TestCase
{
    private LetterDistanceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new LetterDistanceCalculator(WordSimilarityComparatorConfig::createDefault());
    }

    public function test_calculate_letter_distance_identical_strings(): void
    {
        $distance = $this->calculator->calculateLetterDistance('hello', 'hello');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
    }

    public function test_calculate_letter_distance_similar_strings(): void
    {
        $distance = $this->calculator->calculateLetterDistance('hello', 'hallo');
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(1.0, $distance);
    }

    public function test_calculate_letter_distance_completely_different(): void
    {
        $distance = $this->calculator->calculateLetterDistance('abc', 'xyz');
        $this->assertGreaterThan(0, $distance);
    }

    public function test_calculate_letter_distance_with_repeated_letters(): void
    {
        $distance = $this->calculator->calculateLetterDistance('banana', 'baanna');
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(2.0, $distance);
    }

    public function test_calculate_letter_distance_with_transposed_letters(): void
    {
        $distance = $this->calculator->calculateLetterDistance('maman', 'maamn');
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(2.0, $distance);
    }

    public function test_calculate_letter_distance_with_long_strings(): void
    {
        $longString1 = str_repeat('abc', 100);
        $longString2 = str_repeat('acb', 100);

        $distance = $this->calculator->calculateLetterDistance($longString1, $longString2);

        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(200.0, $distance);
    }

    public function test_calculate_letter_distance_with_different_lengths(): void
    {
        $distance = $this->calculator->calculateLetterDistance('longword', 'short');
        $this->assertGreaterThan(0, $distance);
    }

    public function test_calculate_letter_distance_with_single_characters(): void
    {
        $distanceSame = $this->calculator->calculateLetterDistance('a', 'a');
        $distanceDiff = $this->calculator->calculateLetterDistance('a', 'b');

        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distanceSame);
        $this->assertGreaterThan(0, $distanceDiff);
    }

    public function test_calculate_letter_distance_with_phonetic_reduction(): void
    {
        $distance = $this->calculator->calculateLetterDistance('catherine', 'katherine');
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(1.5, $distance);
    }

    public function test_calculate_letter_distance_with_matching_letters(): void
    {
        $distance = $this->calculator->calculateLetterDistance('hello', 'hexxo');
        $this->assertGreaterThan(0, $distance);
    }

    public function test_calculate_letter_distance_with_no_matching_letters(): void
    {
        $distance = $this->calculator->calculateLetterDistance('abc', 'def');
        $this->assertGreaterThan(0, $distance);
    }

    public function test_calculate_letter_distance_with_single_character_matching(): void
    {
        $distance = $this->calculator->calculateLetterDistance('a', 'a');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);
    }

    public function test_calculate_letter_distance_with_empty_strings(): void
    {
        $distance = $this->calculator->calculateLetterDistance('', '');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);

        $distance2 = $this->calculator->calculateLetterDistance('abc', '');
        $this->assertGreaterThan(0, $distance2);
    }

    public function test_calculate_letter_distance_with_accented_characters(): void
    {
        $distance = $this->calculator->calculateLetterDistance('café', 'cafe');
        $this->assertGreaterThanOrEqual(0, $distance);
    }

    public function test_calculate_letter_distance_with_numbers(): void
    {
        $distance = $this->calculator->calculateLetterDistance('123', '123');
        $this->assertEquals(FUZZY_DISTANCE_IDENTICAL, $distance);

        $distance2 = $this->calculator->calculateLetterDistance('123', '124');
        $this->assertGreaterThan(0, $distance2);
    }
}
