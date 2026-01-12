<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\ValueObjects;

use Fuzzy\Tests\TestCase;
use Fuzzy\ValueObjects\IndexData;

final class IndexDataTest extends TestCase
{
    public function test_from_array_with_empty_data(): void
    {
        $indexData = IndexData::fromArray([]);

        $this->assertSame([], $indexData->wordIndex);
        $this->assertSame([], $indexData->itemMap);
        $this->assertSame([], $indexData->modelIndex);
    }

    public function test_from_array_with_data(): void
    {
        $data = [
            'wordIndex' => ['test' => [['id' => 1]]],
            'itemMap' => ['User_1' => ['id' => 1]],
            'modelIndex' => ['User_1' => [['id' => 1]]],
        ];

        $indexData = IndexData::fromArray($data);

        $this->assertSame(['test' => [['id' => 1]]], $indexData->wordIndex);
        $this->assertSame(['User_1' => ['id' => 1]], $indexData->itemMap);
        $this->assertSame(['User_1' => [['id' => 1]]], $indexData->modelIndex);
    }

    public function test_get_entries_for_model(): void
    {
        $data = [
            'modelIndex' => [
                'User_1' => [['id' => 1, 'name' => 'John']],
                'User_2' => [['id' => 2, 'name' => 'Jane']],
            ],
        ];

        $indexData = IndexData::fromArray($data);

        $entries1 = $indexData->getEntriesForModel('User', '1');
        $entries2 = $indexData->getEntriesForModel('User', '2');
        $entries3 = $indexData->getEntriesForModel('User', '3'); // Non-existent

        $this->assertSame([['id' => 1, 'name' => 'John']], $entries1);
        $this->assertSame([['id' => 2, 'name' => 'Jane']], $entries2);
        $this->assertSame([], $entries3);
    }

    public function test_get_word_index(): void
    {
        $data = [
            'wordIndex' => ['hello' => [1], 'world' => [2]],
        ];

        $indexData = IndexData::fromArray($data);

        $this->assertSame(['hello' => [1], 'world' => [2]], $indexData->getWordIndex());
    }

    public function test_get_item_map(): void
    {
        $data = [
            'itemMap' => ['User_1' => ['id' => 1], 'User_2' => ['id' => 2]],
        ];

        $indexData = IndexData::fromArray($data);

        $this->assertSame(['User_1' => ['id' => 1], 'User_2' => ['id' => 2]], $indexData->getItemMap());
    }

    public function test_get_model_index(): void
    {
        $data = [
            'modelIndex' => ['User_1' => [1], 'User_2' => [2]],
        ];

        $indexData = IndexData::fromArray($data);

        $this->assertSame(['User_1' => [1], 'User_2' => [2]], $indexData->getModelIndex());
    }

    public function test_get_model_class_from_empty_item_map(): void
    {
        $indexData = IndexData::fromArray([]);

        $this->assertSame('', $indexData->getModelClass());
    }

    public function test_get_model_class(): void
    {
        $data = [
            'itemMap' => ['User_1' => ['indexable_type' => 'App\\Models\\User']],
        ];

        $indexData = IndexData::fromArray($data);

        $this->assertSame('App\\Models\\User', $indexData->getModelClass());
    }

    public function test_with_partial_data(): void
    {
        // Test with only some keys present
        $data = [
            'wordIndex' => ['test' => [1]],
            // itemMap and modelIndex missing
        ];

        $indexData = IndexData::fromArray($data);

        $this->assertSame(['test' => [1]], $indexData->wordIndex);
        $this->assertSame([], $indexData->itemMap);
        $this->assertSame([], $indexData->modelIndex);
    }
}
