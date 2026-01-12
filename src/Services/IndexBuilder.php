<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;

/**
 * Service for building and maintaining search indexes for searchable models.
 *
 * Handles creation, updating, and management of inverted indexes for fuzzy search.
 */
class IndexBuilder
{
    /**
     * @param StringNormalizer $normalizer Service for text normalization and processing
     */
    public function __construct(
        private readonly StringNormalizer $normalizer
    ) {}

    /**
     * Index all searchable fields of a model instance.
     *
     * Processes each searchable field defined in the model and creates/updates
     * corresponding index entries.
     *
     * @param MustFuzzySearch $model The searchable model instance to index
     * @return void
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();
        $searchableFields = $model->getSearchableFields();

        foreach ($searchableFields as $field) {
            $fieldValue = $model->getAttribute($field);

            if ($fieldValue !== null) {
                $this->indexField(
                    modelType: $modelType,
                    modelId: $modelId,
                    field: $field,
                    value: (string) $fieldValue
                );
            }
        }
    }

    /**
     * Index a specific field value for a model.
     *
     * Creates or updates an index entry for a single field, processing the value
     * into searchable words with appropriate weighting.
     *
     * @param string $modelType Fully qualified class name of the model
     * @param mixed $modelId The model's primary key value
     * @param string $field The field name being indexed
     * @param string $value The field value to index
     * @return void
     */
    public function indexField(string $modelType, mixed $modelId, string $field, string $value): void
    {
        $normalizedValue = $this->normalizer->normalize($value);

        if ($this->isEmptyValue($normalizedValue)) {
            return;
        }

        $words = $this->normalizer->splitIntoWords($normalizedValue);

        if (empty($words)) {
            return;
        }

        $fieldWeight = $this->calculateFieldWeight($field);

        FuzzyIndex::updateOrCreate(
            [
                'indexable_type' => $modelType,
                'indexable_id' => $modelId,
                'field' => $field,
            ],
            [
                'original_value' => $value,
                'normalized_value' => $normalizedValue,
                'words' => $words,
                'weight' => $fieldWeight,
                'metadata' => $this->generateFieldMetadata($value, $normalizedValue, $words),
            ]
        );
    }

    /**
     * Calculate the importance weight for a field based on configuration.
     *
     * Higher weights give more importance to matches in this field during scoring.
     *
     * @param string $field The field name to calculate weight for
     * @return float Weight between 0.0 and 1.0
     */
    public function calculateFieldWeight(string $field): float
    {
        $configuredWeights = config('fuzzy.scoring.field_weights', [
            'name' => 1.0,
            'title' => 0.9,
            'email' => 0.8,
            'description' => 0.7,
            'default' => 0.5,
        ]);

        return $configuredWeights[$field] ?? $configuredWeights['default'];
    }

    /**
     * Index multiple models in batch.
     *
     * Efficiently indexes an array of models in a single operation.
     *
     * @param array<MustFuzzySearch|Model> $models Array of models to index
     * @return void
     */
    public function batchIndex(array $models): void
    {
        foreach ($models as $model) {
            if ($model instanceof MustFuzzySearch) {
                $this->indexModel($model);
            }
        }
    }

    /**
     * Check if a normalized value should be considered empty for indexing.
     *
     * @param string $normalizedValue The normalized string value
     * @return bool True if the value is considered empty for indexing
     */
    private function isEmptyValue(string $normalizedValue): bool
    {
        return $normalizedValue === '' || $normalizedValue === '0';
    }

    /**
     * Generate metadata for an indexed field.
     *
     * @param string $originalValue The original field value
     * @param string $normalizedValue The normalized field value
     * @param array<int, string> $words Array of extracted words
     * @return array<string, mixed> Metadata array
     */
    private function generateFieldMetadata(string $originalValue, string $normalizedValue, array $words): array
    {
        return [
            'word_count' => count($words),
            'value_length' => strlen($originalValue),
            'normalized_length' => strlen($normalizedValue),
        ];
    }
}
