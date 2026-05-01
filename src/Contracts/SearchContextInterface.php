<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for search context that encapsulates the state of a search operation.
 *
 * Defines the contract for managing the complete lifecycle of a search request
 * including query processing, model preloading, match discovery, and result scoring.
 *
 * The search context acts as a state container that flows through all pipeline stages,
 * accumulating potential matches and intermediate results along the way.
 *
 * @package Fuzzy\Contracts
 */
interface SearchContextInterface
{
    /**
     * Retrieve a model instance from preloaded models.
     *
     * Models are preloaded to avoid N+1 queries during score calculation.
     *
     * @param string $key Model identifier in format "Type_Id" (e.g., "User_123")
     * @return object|null The model instance or null if not found
     */
    public function getModelInstance(string $key): ?object;

    /**
     * Get all model IDs from the item map.
     *
     * Returns unique identifiers for all models discovered during match discovery.
     *
     * @return array<int, string|int> Unique model identifiers
     */
    public function getAllModelIds(): array;

    /**
     * Check if the query contains multiple words.
     *
     * Used to determine whether multi-word scoring strategies should be applied.
     *
     * @return bool True if the query has multiple words, false otherwise
     */
    public function hasMultipleWords(): bool;

    /**
     * Get individual query words after normalization.
     *
     * Returns the query split into individual word tokens.
     *
     * @return array<int, string> Array of normalized query words
     */
    public function getQueryWords(): array;

    /**
     * Get the fully normalized query string.
     *
     * Returns the complete query after applying all normalization rules
     * (lowercase, accent removal, stop word filtering).
     *
     * @return string Normalized search query string
     */
    public function getNormalizedQuery(): string;

    /**
     * Get the word index from index data.
     *
     * The word index maps normalized word strings to lists of index entries
     * where that word appears.
     *
     * @return array<string, array<int, array<string, mixed>>> Word index mapping
     */
    public function getWordIndex(): array;

    /**
     * Get the item map from index data.
     *
     * The item map provides lookup capabilities for individual index entries
     * by their unique identifiers.
     *
     * @return array<string, array<string, mixed>> Item mapping data
     */
    public function getItemMap(): array;

    /**
     * Get the model index from index data.
     *
     * The model index groups index entries by model class and instance ID.
     *
     * @return array<string, array<string, array<string, mixed>>> Model index mapping
     */
    public function getModelIndex(): array;

    /**
     * Get index entries for a specific model instance.
     *
     * Retrieves all index entries belonging to a given model type and ID.
     *
     * @param string $modelType Fully qualified model class name
     * @param string $modelId Unique model instance identifier
     * @return array<string, array<string, mixed>> Index entries for the model instance
     */
    public function getIndexEntriesForModel(string $modelType, string $modelId): array;

    /**
     * Get the model class from index data.
     *
     * Returns the model class associated with the current search operation.
     *
     * @return class-string Fully qualified model class name
     */
    public function getModelClass(): string;

    /**
     * Add a potential match before scoring.
     *
     * Matches discovered during the MatchDiscoveryStage are accumulated here
     * before being processed by the ScoringStage.
     *
     * @param array<string, mixed> $match Raw match data containing index entry information
     * @return void
     */
    public function addPotentialMatch(array $match): void;

    /**
     * Get all potential matches for a specific model.
     *
     * @param string $key Model identifier in format "Type_Id" (e.g., "User_123")
     * @return array<int, array<string, mixed>> Array of potential matches for this model
     */
    public function getPotentialMatchesForModel(string $key): array;

    /**
     * Get all potential matches across all models.
     *
     * @return array<string, array<int, array<string, mixed>>> All potential matches
     *                                                  keyed by model identifier
     */
    public function getAllPotentialMatches(): array;

    /**
     * Check if a model has any potential matches.
     *
     * @param string $key Model identifier in format "Type_Id" (e.g., "User_123")
     * @return bool True if the model has at least one potential match
     */
    public function hasPotentialMatches(string $key): bool;
}
