<?php

declare(strict_types=1);

namespace Fuzzy\ValueObjects;

/**
 * Encapsulates index data structure for fuzzy search
 *
 * This value object represents the complete index structure containing:
 * - Word index: mapping of words to document references
 * - Item map: mapping of document IDs to their metadata
 * - Model index: organization of documents by model type and ID
 */
class IndexData
{
    /**
     * @param array<string, array<int, array<string, mixed>>> $wordIndex Mapping of words to document references
     * @param array<int, array<string, mixed>> $itemMap Mapping of document IDs to metadata
     * @param array<string, array<int, mixed>> $modelIndex Organization by model type and ID
     */
    public function __construct(
        public array $wordIndex,
        public array $itemMap,
        public array $modelIndex
    ) {}

    /**
     * Creates an IndexData instance from an array representation
     *
     * @param array{
     *     wordIndex?: array<string, array<int, array<string, mixed>>>,
     *     itemMap?: array<int, array<string, mixed>>,
     *     modelIndex?: array<string, array<int, mixed>>
     * } $data Array representation of index data
     * @return self New IndexData instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wordIndex: $data['wordIndex'] ?? [],
            itemMap: $data['itemMap'] ?? [],
            modelIndex: $data['modelIndex'] ?? []
        );
    }

    /**
     * Retrieves index entries for a specific model instance
     *
     * @param string $modelType Fully qualified model class name
     * @param string $modelId Model instance identifier
     * @return array<int, mixed> Array of index entries for the model
     */
    public function getEntriesForModel(string $modelType, string $modelId): array
    {
        $key = $modelType . '_' . $modelId;
        return $this->modelIndex[$key] ?? [];
    }

    /**
     * Gets the complete word index
     *
     * @return array<string, array<int, array<string, mixed>>> Word to document mapping
     */
    public function getWordIndex(): array
    {
        return $this->wordIndex;
    }

    /**
     * Gets the complete item map
     *
     * @return array<int, array<string, mixed>> Document ID to metadata mapping
     */
    public function getItemMap(): array
    {
        return $this->itemMap;
    }

    /**
     * Gets the complete model index
     *
     * @return array<string, array<int, mixed>> Model organization mapping
     */
    public function getModelIndex(): array
    {
        return $this->modelIndex;
    }

    /**
     * Determines the model class from the indexed data
     *
     * @return string Fully qualified model class name or empty string if no data
     */
    public function getModelClass(): string
    {
        $firstItem = reset($this->itemMap);
        return $firstItem['indexable_type'] ?? '';
    }
}
