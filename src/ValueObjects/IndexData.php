<?php

declare(strict_types=1);

namespace Fuzzy\ValueObjects;

/**
 * Value Object encapsulant les données d'index
 */
class IndexData
{
    public function __construct(
        public array $wordIndex,
        public array $itemMap,
        public array $modelIndex
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wordIndex: $data['wordIndex'] ?? [],
            itemMap: $data['itemMap'] ?? [],
            modelIndex: $data['modelIndex'] ?? []
        );
    }

    public function getEntriesForModel(string $modelType, string $modelId): array
    {
        $key = $modelType . '_' . $modelId;
        return $this->modelIndex[$key] ?? [];
    }

    public function getWordIndex(): array
    {
        return $this->wordIndex;
    }

    public function getItemMap(): array
    {
        return $this->itemMap;
    }

    public function getModelIndex(): array
    {
        return $this->modelIndex;
    }

    public function getModelClass(): string
    {
        $firstItem = reset($this->itemMap);
        return $firstItem['indexable_type'] ?? '';
    }
}
