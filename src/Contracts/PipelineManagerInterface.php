<?php

declare(strict_types=1);

namespace Fuzzy\Contracts;

/**
 * Interface for pipeline manager that orchestrates search processing stages.
 *
 * Defines the contract for managing the pipeline of search operations,
 * including normalization, match discovery, scoring, and result processing.
 *
 * The pipeline follows a sequential stage architecture where each stage
 * transforms the search context and passes it to the next stage.
 *
 * @package Fuzzy\Contracts
 */
interface PipelineManagerInterface
{
    /**
     * Process the search context through the pipeline stages.
     *
     * Executes a series of pipeline stages (normalization, match discovery,
     * scoring, relevance scoring, sorting, and limiting) on the search context
     * to transform it into structured search results.
     *
     * Each stage receives the context, modifies it (adding potential matches,
     * calculating scores, etc.), and passes it to the next stage.
     *
     * @param SearchContextInterface $context The search context containing query,
     *                                        search options, and index data
     * @return array<int, array<string, mixed>> Array of processed search results
     *                                          with scores and metadata
     */
    public function process(SearchContextInterface $context): array;
}
