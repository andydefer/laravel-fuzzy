<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Settings
    |--------------------------------------------------------------------------
    |
    | When searchable_models is empty, the package will automatically
    | discover models in the app/Models directory.
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
    | Field Weights
    |--------------------------------------------------------------------------
    |
    | Weight multipliers for different field types.
    | Higher weight = more important in search results.
    |
    */
    'field_weights' => [
        'name' => 1.0,
        'title' => 0.9,
        'email' => 0.8,
        'description' => 0.7,
        'content' => 0.6,
        'default' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stop Words
    |--------------------------------------------------------------------------
    |
    | Common words to ignore during search.
    | These words are removed from queries and not indexed.
    |
    */
    'stop_words' => [
        'the',
        'and',
        'or',
        'a',
        'an',
        'in',
        'on',
        'at',
        'to',
        'for',
        'of',
        'with',
        'by',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'do',
        'does',
        'did',
        'but',
        'if',
        'then',
        'else',
        'when',
        'where',
        'why',
        'how',
        'all',
        'any',
        'both',
        'each',
        'few',
        'more',
        'most',
        'other',
        'some',
        'such',
        'no',
        'nor',
        'not',
        'only',
        'own',
        'same',
        'so',
        'than',
        'too',
        'very',
        'can',
        'will',
        'just',
        'should',
        'now',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Search Options
    |--------------------------------------------------------------------------
    |
    | Default options for search queries.
    | These can be overridden per query.
    |
    */
    'default_options' => [
        'min_score' => 0.1,
        'max_results' => 20,
        'fuzzy' => true,
        'threshold' => 0.3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the search index.
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
    | Similarity Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for similarity calculations.
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
        'consecutive_bonus' => [
            2 => 1.05,
            3 => 1.15,
            4 => 1.30,
            5 => 1.50,
        ],
    ],
];
