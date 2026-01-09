<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Fuzzy\Contracts\MustFuzzySearch;
use Spatie\LaravelData\Data;

class SearchResultData extends Data
{
    public function __construct(
        public object $item,
        public float $score,
        public string $modelType,
        public ?string $matchedField = null,
        public ?string $matchedValue = null,
    ) {}

    /**
     * Get formatted result
     */
    public function toArray(): array
    {
        $data = [
            'score' => round($this->score, 2),
            'model_type' => $this->modelType,
            'matched_field' => $this->matchedField,
            'matched_value' => $this->matchedValue,
        ];

        if ($this->item instanceof MustFuzzySearch) {
            $searchableData = $this->item->toSearchableData();
            $data['item'] = $searchableData->toArray();
        } else {
            $data['item'] = $this->item;
        }

        return $data;
    }
}
