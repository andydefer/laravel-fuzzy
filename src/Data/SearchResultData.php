<?php

declare(strict_types=1);

namespace Fuzzy\Data;

use Spatie\LaravelData\Data;
use Illuminate\Database\Eloquent\Model;

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
        // Constructeur PUR : seulement initialise les propriétés
        // Aucun effet secondaire ici
    }

    /**
     * Factory method pour créer un résultat avec formatting personnalisé
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
     * Tentative de formatting personnalisé si disponible
     */
    private static function attemptCustomFormatting(object $item): ?object
    {
        // ÉTAPE 1: Essayer d'utiliser le getter getFuzzyFormat() si disponible
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
     * Récupère la classe de formateur selon la priorité:
     * 1. Méthode getFuzzyFormat() (prioritaire, pour formatage dynamique)
     * 2. Propriété fuzzyFormat (fallback statique)
     * 3. null (pas de formatage)
     */
    private static function getFormatterClassFromItem(object $item): ?string
    {
        // 1. Vérifier si le getter getFuzzyFormat() existe et retourne une valeur
        if (method_exists($item, 'getFuzzyFormat')) {
            $formatter = $item->getFuzzyFormat();
            if ($formatter && is_string($formatter)) {
                return $formatter;
            }
        }

        // 2. Fallback: vérifier la propriété fuzzyFormat
        if (property_exists($item, 'fuzzyFormat') && $item->fuzzyFormat) {
            return $item->fuzzyFormat;
        }

        // 3. Aucun formateur disponible
        return null;
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
     * Format the item for API output.
     */
    private function formatItemForOutput(): array
    {
        // Si l'item a déjà été formaté dans le factory method
        if ($this->item instanceof Data) {
            return $this->item->toArray();
        }

        // Fallback au formatting par défaut
        return FuzzySearchableData::fromModel($this->item)->toArray();
    }

    /**
     * Factory method pour créer à partir d'un modèle (méthode d'usine)
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
     * Factory method pour un formatting spécifique
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
