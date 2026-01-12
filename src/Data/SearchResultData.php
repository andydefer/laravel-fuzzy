<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a single search result with relevance score and match details.
 *
 * This data object standardizes fuzzy search results for consistent API responses,
 * providing scoring information and optional custom formatting capabilities.
 */
class SearchResultData extends Data
{
    /**
     * Creates a new search result instance.
     *
     * @param object $item The original model or entity found in search
     * @param float $score Relevance score indicating match quality (0-100)
     * @param string $modelType Class name or type identifier of the item
     * @param string|null $matchedField Specific field that matched the search query
     * @param string|null $matchedValue Actual value that triggered the match
     */
    public function __construct(
        public object $item,
        public float $score,
        public string $modelType,
        public ?string $matchedField = null,
        public ?string $matchedValue = null,
    ) {}

    /**
     * Factory method to create a search result with automatic formatting.
     *
     * @param object $item The original model or entity
     * @param float $score Relevance score
     * @param string $modelType Class name or type identifier
     * @param string|null $matchedField Specific field that matched
     * @param string|null $matchedValue Actual matched value
     * @return self Formatted search result
     */
    public static function create(
        object $item,
        float $score,
        string $modelType,
        ?string $matchedField = null,
        ?string $matchedValue = null
    ): self {
        $formattedItem = self::attemptCustomFormatting($item);

        return new self(
            item: $formattedItem ?? $item,
            score: round($score, 2),
            modelType: $modelType,
            matchedField: $matchedField,
            matchedValue: $matchedValue
        );
    }

    /**
     * Attempts to apply custom formatting to the item if a formatter is defined.
     *
     * @param object $item The item to format
     * @return object|null Formatted item or null if no formatter available
     */
    private static function attemptCustomFormatting(object $item): ?object
    {
        $formatterClass = self::getFormatterClassFromItem($item);

        if (!$formatterClass) {
            return null;
        }

        if (class_exists($formatterClass) && method_exists($formatterClass, 'fromModel')) {
            return $formatterClass::fromModel($item);
        }

        return null;
    }

    /**
     * Retrieves the formatter class from an item using priority order.
     *
     * Priority:
     * 1. getFuzzyFormat() method (dynamic formatting)
     * 2. fuzzyFormat property (static fallback)
     * 3. null (no formatting)
     *
     * @param object $item The item to check for formatter
     * @return string|null Formatter class name or null
     */
    private static function getFormatterClassFromItem(object $item): ?string
    {
        if (method_exists($item, 'getFuzzyFormat')) {
            $formatter = $item->getFuzzyFormat();
            if ($formatter && is_string($formatter)) {
                return $formatter;
            }
        }

        if (property_exists($item, 'fuzzyFormat') && $item->fuzzyFormat) {
            return $item->fuzzyFormat;
        }

        return null;
    }

    /**
     * Converts the search result to a standardized array for API responses.
     *
     * @return array{
     *     score: float,
     *     model_type: string,
     *     matched_field: string|null,
     *     matched_value: string|null,
     *     item: array
     * }
     */
    public function toArray(): array
    {
        return [
            'score' => round($this->score, 2),
            'model_type' => $this->modelType,
            'matched_field' => $this->matchedField,
            'matched_value' => $this->matchedValue,
            'item' => $this->formatItemForOutput(),
        ];
    }

    /**
     * Formats the item for API output.
     *
     * @return array Formatted item data
     */
    private function formatItemForOutput(): array
    {
        if ($this->item instanceof Data) {
            return $this->item->toArray();
        }

        return FuzzySearchableData::fromModel($this->item)->toArray();
    }

    /**
     * Factory method to create from an Eloquent model.
     *
     * @param Model $model The Eloquent model instance
     * @param float $score Relevance score
     * @param string|null $matchedField Specific field that matched
     * @param string|null $matchedValue Actual matched value
     * @return self Search result from model
     */
    public static function fromModel(
        Model $model,
        float $score,
        ?string $matchedField = null,
        ?string $matchedValue = null
    ): self {
        return self::create(
            item: $model,
            score: round($score, 2),
            modelType: class_basename($model),
            matchedField: $matchedField,
            matchedValue: $matchedValue
        );
    }

    /**
     * Factory method with custom formatter callback.
     *
     * @param object $item The original item
     * @param float $score Relevance score
     * @param string $modelType Class name or type identifier
     * @param callable $formatter Custom formatting callback
     * @param string|null $matchedField Specific field that matched
     * @param string|null $matchedValue Actual matched value
     * @return self Formatted search result
     */
    public static function withFormatter(
        object $item,
        float $score,
        string $modelType,
        callable $formatter,
        ?string $matchedField = null,
        ?string $matchedValue = null
    ): self {
        $formattedItem = $formatter($item);

        return new self(
            item: $formattedItem,
            score: round($score, 2),
            modelType: $modelType,
            matchedField: $matchedField,
            matchedValue: $matchedValue
        );
    }
}
