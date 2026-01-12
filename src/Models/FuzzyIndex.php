<?php

declare(strict_types=1);

namespace Fuzzy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a search index entry for fuzzy search functionality
 *
 * This model stores indexed data for searchable models, including original and normalized
 * field values, word breakdowns, and weighting information for relevance scoring.
 * Each entry is associated with a specific field of a searchable model instance.
 */
class FuzzyIndex extends Model
{
    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'fuzzy_index';

    /**
     * The attributes that are mass assignable
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
     * The attributes that should be cast
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
     * Scope query to a specific model type
     *
     * @param Builder $query The Eloquent query builder
     * @param string $modelType The fully qualified model class name
     * @return Builder The scoped query builder
     */
    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('indexable_type', $modelType);
    }

    /**
     * Scope query to a specific model instance
     *
     * @param Builder $query The Eloquent query builder
     * @param string $modelType The fully qualified model class name
     * @param int|string $modelId The identifier of the model instance
     * @return Builder The scoped query builder
     */
    public function scopeForModelInstance(Builder $query, string $modelType, int|string $modelId): Builder
    {
        return $query
            ->where('indexable_type', $modelType)
            ->where('indexable_id', $modelId);
    }

    /**
     * Scope query to a specific field
     *
     * @param Builder $query The Eloquent query builder
     * @param string $field The field name to filter by
     * @return Builder The scoped query builder
     */
    public function scopeForField(Builder $query, string $field): Builder
    {
        return $query->where('field', $field);
    }

    /**
     * Scope query to entries containing a specific word
     *
     * @param Builder $query The Eloquent query builder
     * @param string $word The word to search for in the JSON array
     * @return Builder The scoped query builder
     */
    public function scopeWithWord(Builder $query, string $word): Builder
    {
        return $query->whereJsonContains('words', $word);
    }

    /**
     * Scope query to entries with normalized values containing the given substring
     *
     * @param Builder $query The Eloquent query builder
     * @param string $value The substring to search for in normalized values
     * @return Builder The scoped query builder
     */
    public function scopeWithNormalizedValue(Builder $query, string $value): Builder
    {
        return $query->where('normalized_value', 'like', sprintf('%%%s%%', $value));
    }

    /**
     * Get the polymorphic relationship to the indexable model
     *
     * @return MorphTo The morphTo relationship
     */
    public function indexable(): MorphTo
    {
        return $this->morphTo();
    }
}
