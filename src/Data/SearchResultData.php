<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;

/**
 * Represents a single search result with its relevance score and match details.
 *
 * This data object wraps search results from fuzzy search operations, providing
 * standardized formatting and scoring information for consistent API responses.
 */
class SearchResultData extends Data
{
    /**
     * @param object $item The original model/entity found in the search
     * @param float $score The relevance score (0-100) indicating match quality
     * @param string $modelType The class name or type identifier of the item
     * @param string|null $matchedField The specific field that matched the search query
     * @param string|null $matchedValue The actual value that triggered the match
     */
    public function __construct(
        public object $item,
        public float $score,
        public string $modelType,
        public ?string $matchedField = null,
        public ?string $matchedValue = null,
    ) {
        if ($this->attemptCustomFormatting()) {
            $this->item = $this->attemptCustomFormatting();
        }
    }

    /**
     * Convert the search result to a standardized array format for API responses.
     *
     * Applies custom formatting if defined on the item, otherwise uses default formatting.
     * Ensures consistent structure across all search result types.
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
     * Format the item for API output, respecting custom formatters when available.
     *
     * @return array The formatted item data
     */
    private function formatItemForOutput(): array
    {
        $formattedItem = $this->attemptCustomFormatting()
            ?? FuzzySearchableData::fromModel($this->item);

        return $formattedItem instanceof Data
            ? $formattedItem->toArray()
            : $formattedItem;
    }

    /**
     * Attempt to apply custom formatting if the item defines a formatter.
     *
     * @return mixed|null The custom formatted data or null if no custom formatter exists
     */
    private function attemptCustomFormatting(): mixed
    {
        if (!property_exists($this->item, 'fuzzyFormat') || !$this->item->fuzzyFormat) {
            return null;
        }

        $formatterClass = $this->item->fuzzyFormat;

        if (class_exists($formatterClass) && method_exists($formatterClass, 'fromModel')) {
            return $formatterClass::fromModel($this->item);
        }

        return null;
    }
}
