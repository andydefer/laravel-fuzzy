<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;

class IndexBuilder
{
    private StringNormalizer $normalizer;

    public function __construct(StringNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Index a model instance
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();
        $searchableFields = $model->getSearchableFields();

        foreach ($searchableFields as $field) {
            $value = $model->getAttribute($field);

            if ($value !== null) {
                $this->indexField($modelType, $modelId, $field, (string) $value);
            }
        }
    }

    /**
     * Index a specific field value
     */
    public function indexField(string $modelType, $modelId, string $field, string $value): void
    {
        $normalizedValue = $this->normalizer->normalize($value);

        if (empty($normalizedValue)) {
            return;
        }

        $words = $this->normalizer->splitIntoWords($normalizedValue);

        if (empty($words)) {
            return;
        }

        // Calculate weight based on field importance
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
     * Calculate weight for a field
     */
    protected function calculateFieldWeight(string $field): float
    {
        $weights = config('fuzzy.field_weights', [
            'name' => 1.0,
            'title' => 0.9,
            'email' => 0.8,
            'description' => 0.7,
            'default' => 0.5,
        ]);

        return $weights[$field] ?? $weights['default'];
    }

    /**
     * Batch index multiple models
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
