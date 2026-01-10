<?php

declare(strict_types=1);

namespace Fuzzy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a search index entry for fuzzy search functionality.
 *
 * Stores normalized search data and metadata for searchable models.
 * Each entry contains original and normalized field values,
 * word breakdowns, and weighting information for relevance scoring.
 */
class FuzzyIndex extends Model
{
    /**
     * The database table name.
     *
     * @var string
     */
    protected $table = 'fuzzy_index';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'words' => 'array',
        'metadata' => 'array',
        'weight' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope the query to a specific model type.
     *
     * @param Builder $query
     * @param string $modelType
     * @return Builder
     */
    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('indexable_type', $modelType);
    }

    /**
     * Scope the query to a specific model instance.
     *
     * @param Builder $query
     * @param string $modelType
     * @param int|string $modelId
     * @return Builder
     */
    public function scopeForModelInstance(Builder $query, string $modelType, $modelId): Builder
    {
        return $query->where('indexable_type', $modelType)
            ->where('indexable_id', $modelId);
    }

    /**
     * Scope the query to a specific field.
     *
     * @param Builder $query
     * @param string $field
     * @return Builder
     */
    public function scopeForField(Builder $query, string $field): Builder
    {
        return $query->where('field', $field);
    }

    /**
     * Scope the query to entries containing a specific word.
     *
     * @param Builder $query
     * @param string $word
     * @return Builder
     */
    public function scopeWithWord(Builder $query, string $word): Builder
    {
        return $query->whereJsonContains('words', $word);
    }

    /**
     * Scope the query to entries with normalized values containing the given string.
     *
     * @param Builder $query
     * @param string $value
     * @return Builder
     */
    public function scopeWithNormalizedValue(Builder $query, string $value): Builder
    {
        return $query->where('normalized_value', 'like', "%{$value}%");
    }

    /**
     * Get the polymorphic relationship to the indexable model.
     *
     * @return MorphTo
     */
    public function indexable(): MorphTo
    {
        return $this->morphTo();
    }
}
