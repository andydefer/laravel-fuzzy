<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Searchable Models
    |--------------------------------------------------------------------------
    |
    | List of models that implement MustFuzzySearch interface.
    | These models will be indexed and searchable.
    |
    */
    'searchable_models' => [
        // Example:
        // App\Models\User::class,
        // App\Models\Product::class,
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
        'min_query_length_for_strict_match' => 4,
        'length_difference_penalty_factor' => 0.3,
        'short_match_penalty' => 0.5,
        'cross_word_match_penalty' => 0.4,
        'min_similarity_threshold' => 0.2,
    ],
];
