<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\ContextualNormalizerInterface;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Database\Eloquent\Model;

/**
 * Service for building and maintaining search indexes for searchable models.
 *
 * Handles creation, updating, and management of inverted indexes for fuzzy search.
 * Respects protected fields to preserve stop words in names, emails, etc.
 *
 * @package Fuzzy\Services
 */
class IndexBuilder
{
    /**
     * @var ContextualNormalizerInterface Service for text normalization and processing
     */
    private ContextualNormalizerInterface $normalizer;

    /**
     * Constructor.
     *
     * @param ContextualNormalizerInterface $normalizer Service for context-aware text normalization
     */
    public function __construct(
        ContextualNormalizerInterface $normalizer
    ) {
        $this->normalizer = $normalizer;
    }

    /**
     * Index all searchable fields of a model instance.
     *
     * Processes each searchable field defined in the model and creates/updates
     * corresponding index entries. Respects protected fields configuration
     * from the model to preserve stop words where appropriate.
     *
     * @param MustFuzzySearch $model The searchable model instance to index
     * @return void
     */
    public function indexModel(MustFuzzySearch $model): void
    {
        $modelType = get_class($model);
        $modelId = $model->getIndexableId();
        $searchableFields = $model->getSearchableFields();
        $protectedFields = $model->getProtectedFields();

        // Set protected fields on the normalizer for this indexing operation
        $this->normalizer->setProtectedFields($protectedFields);

        foreach ($searchableFields as $field) {
            $fieldValue = $this->getFieldValue($model, $field);

            if ($fieldValue !== null) {
                $this->indexField(
                    modelType: $modelType,
                    modelId: $modelId,
                    field: $field,
                    value: (string) $fieldValue
                );
            }
        }

        // Reset protected fields after indexing
        $this->normalizer->setProtectedFields([]);
    }

    /**
     * Get the value of a field from a model.
     *
     * Supports both Eloquent models (with getAttribute) and plain objects
     * (with public properties or getters).
     *
     * @param MustFuzzySearch $model The model instance
     * @param string $field The field name
     * @return mixed The field value or null if not found
     */
    private function getFieldValue(MustFuzzySearch $model, string $field): mixed
    {
        // Try Eloquent's getAttribute method
        if (method_exists($model, 'getAttribute')) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            return $model->getAttribute($field);
        }

        // Try direct property access
        if (property_exists($model, $field)) {
            return $model->{$field};
        }

        // Try getter method (get{Field} or {field})
        $getterMethods = [
            'get' . ucfirst($field),
            'get' . $field,
            $field,
        ];

        foreach ($getterMethods as $method) {
            if (method_exists($model, $method)) {
                return $model->$method();
            }
        }

        // Try array access if model implements ArrayAccess
        if ($model instanceof \ArrayAccess && isset($model[$field])) {
            return $model[$field];
        }

        // Field not found
        return null;
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
        // Use contextual normalization that respects protected fields
        $normalizedValue = $this->normalizer->normalizeForField($value, $field);

        if ($this->isEmptyValue($normalizedValue)) {
            return;
        }

        $words = $this->normalizer->splitIntoWords($normalizedValue);

        if (empty($words)) {
            return;
        }

        $fieldWeight = $this->calculateFieldWeight($field);
        $preservesStopWords = $this->normalizer->shouldPreserveStopWords($field);

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
                'metadata' => $this->generateFieldMetadata($value, $normalizedValue, $words, $preservesStopWords),
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
     * @param bool $preservesStopWords Whether stop words were preserved
     * @return array<string, mixed> Metadata array
     */
    private function generateFieldMetadata(string $originalValue, string $normalizedValue, array $words, bool $preservesStopWords = false): array
    {
        return [
            'word_count' => count($words),
            'value_length' => strlen($originalValue),
            'normalized_length' => strlen($normalizedValue),
            'preserves_stop_words' => $preservesStopWords,
        ];
    }
}
