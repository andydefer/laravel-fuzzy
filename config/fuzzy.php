<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Stop Words Configuration
    |--------------------------------------------------------------------------
    |
    | Words to ignore during indexing and searching.
    | You may add custom stop words, but core stop words are always included.
    |
    */

    'stop_words' => [
        // Add custom stop words here
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Control caching behavior for improved search performance.
    |
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

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Automatically discover searchable models in specified directories.
    |
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

    /*
    |--------------------------------------------------------------------------
    | Default Search Options
    |--------------------------------------------------------------------------
    |
    | Fallback search options used when not explicitly provided.
    |
    */

    'default_options' => [
        'min_score' => 0.1,
        'max_results' => 20,
        'fuzzy' => true,
        'threshold' => 0.1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Indexing Configuration
    |--------------------------------------------------------------------------
    |
    | Control how content is indexed for searching.
    |
    */

    'index' => [
        'min_word_length' => 2,
        'max_word_length' => 50,
        'batch_size' => 100,
        'queue' => env('FUZZY_SEARCH_QUEUE', false),
        'queue_name' => env('FUZZY_SEARCH_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Similarity Algorithm Configuration
    |--------------------------------------------------------------------------
    |
    | Define thresholds and weights for different similarity algorithms.
    |
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

    /*
    |--------------------------------------------------------------------------
    | Scoring Configuration
    |--------------------------------------------------------------------------
    |
    | Partially customizable scoring system for search result ranking.
    | The 'consecutive_bonus' section cannot be modified.
    |
    */

    'scoring' => [
        'field_weights' => [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ],

        'penalties' => [
            'short_query' => 0.5,
            'cross_word_match_multi' => 0.7,
        ],

        'bonuses' => [
            'full_coverage' => 0.3,
            'high_coverage' => 0.15,
            'early_position' => 0.2,
        ],

        'consecutive_bonus' => [
            2 => 1.05,
            3 => 1.10,
            4 => 1.35,
            5 => 1.50,
        ],
    ],
];
