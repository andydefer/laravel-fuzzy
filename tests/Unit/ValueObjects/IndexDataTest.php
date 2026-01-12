<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\ValueObjects;

use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\IndexData;

final class IndexDataTest extends TestCase
{
    public function test_from_array_with_empty_data(): void
    {
        // Arrange: Create IndexData with empty array
        $indexData = IndexData::fromArray([]);

        // Assert: All properties should be empty arrays
        $this->assertSame([], $indexData->wordIndex);
        $this->assertSame([], $indexData->itemMap);
        $this->assertSame([], $indexData->modelIndex);
    }

    public function test_from_array_with_data(): void
    {
        // Arrange: Define complete dataset
        $data = [
            'wordIndex' => ['test' => [['id' => 1]]],
            'itemMap' => ['User_1' => ['id' => 1]],
            'modelIndex' => ['User_1' => [['id' => 1]]],
        ];

        // Act: Create IndexData from array
        $indexData = IndexData::fromArray($data);

        // Assert: All properties should match the input data
        $this->assertSame(['test' => [['id' => 1]]], $indexData->wordIndex);
        $this->assertSame(['User_1' => ['id' => 1]], $indexData->itemMap);
        $this->assertSame(['User_1' => [['id' => 1]]], $indexData->modelIndex);
    }

    public function test_get_entries_for_model(): void
    {
        // Arrange: Create IndexData with model index entries
        $data = [
            'modelIndex' => [
                'User_1' => [['id' => 1, 'name' => 'John']],
                'User_2' => [['id' => 2, 'name' => 'Jane']],
            ],
        ];

        $indexData = IndexData::fromArray($data);

        // Act & Assert: Test retrieving entries for existing models
        $entries1 = $indexData->getEntriesForModel('User', '1');
        $this->assertSame([['id' => 1, 'name' => 'John']], $entries1);

        $entries2 = $indexData->getEntriesForModel('User', '2');
        $this->assertSame([['id' => 2, 'name' => 'Jane']], $entries2);

        // Act & Assert: Test retrieving entries for non-existent model
        $entries3 = $indexData->getEntriesForModel('User', '3');
        $this->assertSame([], $entries3);
    }

    public function test_get_word_index(): void
    {
        // Arrange: Create IndexData with word index
        $data = [
            'wordIndex' => ['hello' => [1], 'world' => [2]],
        ];

        $indexData = IndexData::fromArray($data);

        // Act: Get word index
        $wordIndex = $indexData->getWordIndex();

        // Assert: Word index should match the input data
        $this->assertSame(['hello' => [1], 'world' => [2]], $wordIndex);
    }

    public function test_get_item_map(): void
    {
        // Arrange: Create IndexData with item map
        $data = [
            'itemMap' => ['User_1' => ['id' => 1], 'User_2' => ['id' => 2]],
        ];

        $indexData = IndexData::fromArray($data);

        // Act: Get item map
        $itemMap = $indexData->getItemMap();

        // Assert: Item map should match the input data
        $this->assertSame(['User_1' => ['id' => 1], 'User_2' => ['id' => 2]], $itemMap);
    }

    public function test_get_model_index(): void
    {
        // Arrange: Create IndexData with model index
        $data = [
            'modelIndex' => ['User_1' => [1], 'User_2' => [2]],
        ];

        $indexData = IndexData::fromArray($data);

        // Act: Get model index
        $modelIndex = $indexData->getModelIndex();

        // Assert: Model index should match the input data
        $this->assertSame(['User_1' => [1], 'User_2' => [2]], $modelIndex);
    }

    public function test_get_model_class_from_empty_item_map(): void
    {
        // Arrange: Create IndexData with empty data
        $indexData = IndexData::fromArray([]);

        // Act: Get model class from empty item map
        $modelClass = $indexData->getModelClass();

        // Assert: Should return empty string when no item map exists
        $this->assertSame('', $modelClass);
    }

    public function test_get_model_class(): void
    {
        // Arrange: Create IndexData with item map containing model class
        $data = [
            'itemMap' => ['User_1' => ['indexable_type' => 'App\\Models\\User']],
        ];

        $indexData = IndexData::fromArray($data);

        // Act: Get model class
        $modelClass = $indexData->getModelClass();

        // Assert: Should return the model class from the first item
        $this->assertSame('App\\Models\\User', $modelClass);
    }

    public function test_with_partial_data(): void
    {
        // Arrange: Create IndexData with only some keys present
        $data = [
            'wordIndex' => ['test' => [1]],
            // itemMap and modelIndex intentionally missing
        ];

        // Act: Create IndexData from partial data
        $indexData = IndexData::fromArray($data);

        // Assert: Present keys should have values, missing keys should be empty arrays
        $this->assertSame(['test' => [1]], $indexData->wordIndex);
        $this->assertSame([], $indexData->itemMap);
        $this->assertSame([], $indexData->modelIndex);
    }
}
