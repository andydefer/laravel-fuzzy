<?php

declare(strict_types=1);

namespace LaravelFuzzy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FuzzyIndex extends Model
{
    protected $table = 'fuzzy_index';

    protected $fillable = [
        'indexable_type',
        'indexable_id',
        'field',
        'original_value',
        'normalized_value',
        'words',
        'weight',
        'metadata',
    ];

    protected $casts = [
        'words' => 'array',
        'metadata' => 'array',
        'weight' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for specific model type
     */
    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('indexable_type', $modelType);
    }

    /**
     * Scope for specific model instance
     */
    public function scopeForModelInstance(Builder $query, string $modelType, $modelId): Builder
    {
        return $query->where('indexable_type', $modelType)
            ->where('indexable_id', $modelId);
    }

    /**
     * Scope for field
     */
    public function scopeForField(Builder $query, string $field): Builder
    {
        return $query->where('field', $field);
    }

    /**
     * Scope with word
     */
    public function scopeWithWord(Builder $query, string $word): Builder
    {
        return $query->whereJsonContains('words', $word);
    }

    /**
     * Scope with normalized value
     */
    public function scopeWithNormalizedValue(Builder $query, string $value): Builder
    {
        return $query->where('normalized_value', 'like', "%{$value}%");
    }

    /**
     * Get the indexable model
     */
    public function indexable()
    {
        return $this->morphTo();
    }
}
