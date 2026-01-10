<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Fuzzy\Contracts\MustFuzzySearch;
use Spatie\LaravelData\Data;

/**
 * Data object representing a single search result with relevance metadata.
 *
 * Contains the matched item along with scoring information and details about
 * what caused the match (field and value).
 */
class SearchResultData extends Data
{
    /**
     * @param object $item The matched item (model or other object)
     * @param float $score Relevance score (0.0 to 1.0)
     * @param string $modelType Type/class of the matched item
     * @param string|null $matchedField Field name where the match occurred
     * @param string|null $matchedValue Value that matched the search query
     */
    public function __construct(
        public object $item,
        public float $score,
        public string $modelType,
        public ?string $matchedField = null,
        public ?string $matchedValue = null,
    ) {}

    /**
     * Convert to array format suitable for API responses.
     *
     * If the item implements MustFuzzySearch, uses its searchable representation.
     * Otherwise, includes the item directly.
     *
     * @return array Structured array with result data
     */
    public function toArray(): array
    {
        $resultData = [
            'score' => round($this->score, 2),
            'model_type' => $this->modelType,
            'matched_field' => $this->matchedField,
            'matched_value' => $this->matchedValue,
        ];

        if ($this->item instanceof MustFuzzySearch) {
            $searchableData = $this->item->toSearchableData();
            $resultData['item'] = $searchableData->toArray();
        } else {
            $resultData['item'] = $this->item;
        }

        return $resultData;
    }
}
