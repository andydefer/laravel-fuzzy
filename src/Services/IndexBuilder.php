<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;

/**
 * Service responsible for building and updating search indexes for searchable models.
 */
class IndexBuilder
{
    /**
     * @param StringNormalizer $normalizer Service for normalizing text data
     */
    public function __construct(
        private readonly StringNormalizer $normalizer
    ) {}

    /**
     * Index all searchable fields of a model instance.
     *
     * @param MustFuzzySearch $model The model instance to index
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();
        $searchableFields = $model->getSearchableFields();

        foreach ($searchableFields as $field) {
            $value = $model->getAttribute($field);

            if ($value !== null) {
                $this->indexField(
                    modelType: $modelType,
                    modelId: $modelId,
                    field: $field,
                    value: (string) $value
                );
            }
        }
    }

    /**
     * Index a specific field value for a model.
     *
     * @param string $modelType Fully qualified class name of the model
     * @param mixed $modelId The model's primary key
     * @param string $field The field name being indexed
     * @param string $value The field value to index
     */
    public function indexField(string $modelType, mixed $modelId, string $field, string $value): void
    {
        $normalizedValue = $this->normalizer->normalize($value);

        if ($normalizedValue === '' || $normalizedValue === '0') {
            return;
        }

        $words = $this->normalizer->splitIntoWords($normalizedValue);

        if ($words === []) {
            return;
        }

        $weight = $this->calculateFieldWeight($field);

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
                'weight' => $weight,
                'metadata' => [
                    'word_count' => count($words),
                    'value_length' => strlen($value),
                    'normalized_length' => strlen($normalizedValue),
                ],
            ]
        );
    }

    /**
     * Calculate the importance weight for a field.
     *
     * @param string $field The field name
     * @return float Weight between 0.0 and 1.0
     */
    public function calculateFieldWeight(string $field): float
    {
        $weights = config('fuzzy.scoring.field_weights', [
            'name' => 1.0,
            'title' => 0.9,
            'email' => 0.8,
            'description' => 0.7,
            'default' => 0.5,
        ]);

        return $weights[$field] ?? $weights['default'];
    }

    /**
     * Index multiple models in batch.
     *
     * @param array<MustFuzzySearch|Model> $models Array of models to index
     */
    public function batchIndex(array $models): void
    {
        foreach ($models as $model) {
            if ($model instanceof MustFuzzySearch) {
                $this->indexModel($model);
            }
        }
    }
}
