<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;
use Illuminate\Database\Eloquent\Model;

/**
 * Standardized search result with relevance scoring and match details.
 *
 * Provides consistent API responses for fuzzy search results with optional
 * custom formatting and detailed match information.
 *
 * @package Fuzzy\Data
 */
class SearchResultData extends Data
{
    /** Number of decimal places for score rounding. */
    private const SCORE_DECIMAL_PLACES = 2;

    /** Number of decimal places for relevance rounding. */
    private const RELEVANCE_DECIMAL_PLACES = 4;

    /**
     * Constructor for SearchResultData.
     *
     * @param object $item The original model or entity
     * @param float $score Relevance score (0-100)
     * @param string $modelType Class name or type identifier
     * @param string|null $matchedField Specific field that matched the search query
     * @param string|null $matchedValue Actual value that triggered the match
     * @param float|null $relevance Advanced similarity score
     */
    public function __construct(
        public object $item,
        public float $score,
        public string $modelType,
        public ?string $matchedField = null,
        public ?string $matchedValue = null,
        public ?float $relevance = null
    ) {}

    /**
     * Create a formatted search result with automatic item formatting.
     *
     * @param object $item The original model or entity
     * @param float $score Relevance score (0-100)
     * @param string $modelType Class name or type identifier
     * @param string|null $matchedField Specific field that matched the search query
     * @param string|null $matchedValue Actual value that triggered the match
     * @param float|null $relevance Advanced similarity score
     * @return self Configured SearchResultData instance
     */
    public static function create(
        object $item,
        float $score,
        string $modelType,
        ?string $matchedField = null,
        ?string $matchedValue = null,
        ?float $relevance = null
    ): self {
        $formattedItem = self::applyCustomFormatter($item);

        return new self(
            item: $formattedItem ?? $item,
            score: round($score, self::SCORE_DECIMAL_PLACES),
            modelType: $modelType,
            matchedField: $matchedField,
            matchedValue: $matchedValue,
            relevance: $relevance !== null ? round($relevance, self::RELEVANCE_DECIMAL_PLACES) : null
        );
    }

    /**
     * Create a search result from an Eloquent model.
     *
     * @param Model $model The Eloquent model instance
     * @param float $score Relevance score (0-100)
     * @param string|null $matchedField Specific field that matched
     * @param string|null $matchedValue Actual matched value
     * @param float|null $relevance Advanced similarity score
     * @return self Configured SearchResultData instance
     */
    public static function fromModel(
        Model $model,
        float $score,
        ?string $matchedField = null,
        ?string $matchedValue = null,
        ?float $relevance = null
    ): self {
        return self::create(
            item: $model,
            score: $score,
            modelType: class_basename($model),
            matchedField: $matchedField,
            matchedValue: $matchedValue,
            relevance: $relevance
        );
    }

    /**
     * Create a search result with custom formatter callback.
     *
     * @param object $item The original item
     * @param float $score Relevance score (0-100)
     * @param string $modelType Class name or type identifier
     * @param callable(object): object $formatter Custom formatting callback
     * @param string|null $matchedField Specific field that matched
     * @param string|null $matchedValue Actual matched value
     * @param float|null $relevance Advanced similarity score
     * @return self Configured SearchResultData instance
     */
    public static function withFormatter(
        object $item,
        float $score,
        string $modelType,
        callable $formatter,
        ?string $matchedField = null,
        ?string $matchedValue = null,
        ?float $relevance = null
    ): self {
        $formattedItem = $formatter($item);

        return new self(
            item: $formattedItem,
            score: round($score, self::SCORE_DECIMAL_PLACES),
            modelType: $modelType,
            matchedField: $matchedField,
            matchedValue: $matchedValue,
            relevance: $relevance !== null ? round($relevance, self::RELEVANCE_DECIMAL_PLACES) : null
        );
    }

    /**
     * Convert the search result to a standardized API response array.
     *
     * @return array{
     *     score: float,
     *     model_type: string,
     *     matched_field: string|null,
     *     matched_value: string|null,
     *     relevance: float|null,
     *     item: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'score' => round($this->score, self::SCORE_DECIMAL_PLACES),
            'model_type' => $this->modelType,
            'matched_field' => $this->matchedField,
            'matched_value' => $this->matchedValue,
            'relevance' => $this->relevance !== null ? round($this->relevance, self::RELEVANCE_DECIMAL_PLACES) : null,
            'item' => $this->formatItemForOutput(),
        ];
    }

    /**
     * Format the item for API output.
     *
     * @return array<string, mixed> Formatted item data
     */
    private function formatItemForOutput(): array
    {
        if ($this->item instanceof Data) {
            return $this->item->toArray();
        }

        if ($this->item instanceof Model) {
            return FuzzySearchableData::fromModel($this->item)->toArray();
        }

        return (array) $this->item;
    }

    /**
     * Attempt to apply custom formatting to an item.
     *
     * @param object $item The item to format
     * @return object|null Formatted item or null if no formatter available
     */
    private static function applyCustomFormatter(object $item): ?object
    {
        $formatterClass = self::extractFormatterClass($item);

        if ($formatterClass === null) {
            return null;
        }

        if (class_exists($formatterClass) && method_exists($formatterClass, 'fromModel')) {
            return $formatterClass::fromModel($item);
        }

        return null;
    }

    /**
     * Extract formatter class name from an item.
     *
     * Priority order:
     * 1. getFuzzyFormat() method
     * 2. fuzzyFormat property
     * 3. null (no formatter available)
     *
     * @param object $item The item to check for formatter
     * @return string|null Formatter class name or null
     */
    private static function extractFormatterClass(object $item): ?string
    {
        if (method_exists($item, 'getFuzzyFormat')) {
            $formatter = $item->getFuzzyFormat();
            if (is_string($formatter) && $formatter !== '') {
                return $formatter;
            }
        }

        if (property_exists($item, 'fuzzyFormat') && is_string($item->fuzzyFormat) && $item->fuzzyFormat !== '') {
            return $item->fuzzyFormat;
        }

        return null;
    }
}
