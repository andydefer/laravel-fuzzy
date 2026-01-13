<?php

declare(strict_types=1);

return [
    /**
     * Fuzzy search package configuration for Laravel applications
     *
     * This file allows configuration of fuzzy search behavior.
     * Some sections are protected to ensure proper operation.
     *
     * @var array<string, mixed>
     */

    /**
     * Stop words to ignore during indexing and searching
     *
     * You can add words to this list but cannot remove existing ones.
     * The base words are always present for consistency.
     *
     * Example usage:
     * // Add your custom stop words here
     * // 'your_word',
     * // 'another_word',
     *
     * @var array<int, string>
     */
    'stop_words' => [
        // Add your custom stop words here
        // 'your_word',
        // 'another_word',
    ],

    /**
     * Cache configuration for performance optimization
     *
     * Controls caching behavior to improve search performance.
     *
     * @var array<string, mixed>
     */
    'cache' => [
        'enabled' => env('FUZZY_SEARCH_CACHE_ENABLED', true),
        'prefix' => 'fuzzy_search:',
        'ttl' => [
            'search' => env('FUZZY_SEARCH_CACHE_TTL', 3600),
            'search_in_model' => env('FUZZY_SEARCH_MODEL_CACHE_TTL', 3600),
            'search_in_models' => env('FUZZY_SEARCH_MODELS_CACHE_TTL', 3600),
            'stats' => env('FUZZY_SEARCH_STATS_CACHE_TTL', 30),
        ],
        'invalidation' => [
            'on_index' => true,
            'on_update' => true,
            'on_delete' => true,
        ],
    ],

    /**
     * Auto-discovery configuration for model detection
     *
     * Automatically discovers searchable models in specified directories.
     *
     * @var array<string, mixed>
     */
    'auto_discovery' => [
        'enabled' => true,
        'directories' => [
            app_path('Models'),
        ],
        'exclude_patterns' => [
            '/^Abstract/',
            '/^Base/',
            '/Interface$/',
            '/Trait$/',
        ],
    ],

    /**
     * Default search options applied when not explicitly specified
     *
     * These settings are used as fallback when search options are not provided.
     *
     * @var array<string, mixed>
     */
    'default_options' => [
        'min_score' => 0.1,
        'max_results' => 20,
        'fuzzy' => true,
        'threshold' => 0.1,
    ],

    /**
     * Indexing configuration
     *
     * Controls how content is indexed for searching.
     *
     * @var array<string, mixed>
     */
    'index' => [
        'min_word_length' => 2,
        'max_word_length' => 50,
        'batch_size' => 100,
        'queue' => env('FUZZY_SEARCH_QUEUE', false),
        'queue_name' => env('FUZZY_SEARCH_QUEUE_NAME', 'default'),
    ],

    /**
     * Similarity algorithm configuration
     *
     * Defines thresholds and weights for different similarity algorithms.
     *
     * @var array<string, mixed>
     */
    'similarity' => [
        'min_query_length' => 2,
        'min_similarity_threshold' => 0.1,
        'algorithm_weights' => [
            'jaro_winkler' => 0.4,
            'levenshtein' => 0.3,
            'ngrams' => 0.2,
            'lcs' => 0.1,
        ],
    ],

    /**
     * Scoring configuration for result ranking
     *
     * Partially customizable scoring system for search results.
     * Note: The 'consecutive_bonus' section cannot be modified.
     *
     * @var array<string, mixed>
     */
    'scoring' => [
        /**
         * Field weights for scoring (customizable)
         *
         * You can add or modify field weights to prioritize specific fields.
         *
         * @var array<string, float>
         */
        'field_weights' => [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ],

        /**
         * Penalty configuration (customizable)
         *
         * You can adjust penalty values for specific scenarios.
         *
         * @var array<string, float>
         */
        'penalties' => [
            'short_query' => 0.5,
            'cross_word_match_multi' => 0.7,
        ],

        /**
         * Bonus configuration (customizable)
         *
         * You can adjust bonus values for specific match qualities.
         *
         * @var array<string, float>
         */
        'bonuses' => [
            'full_coverage' => 0.3,
            'high_coverage' => 0.15,
            'early_position' => 0.2,
        ],

        /**
         * Consecutive match bonus (NON-MODIFIABLE)
         *
         * WARNING: This section cannot be modified to ensure consistent scoring.
         *
         * @var array<int, float>
         */
        'consecutive_bonus' => [
            2 => 1.05,
            3 => 1.10,
            4 => 1.35,
            5 => 1.50,
        ],
    ],
];
