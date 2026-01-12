<?php

declare(strict_types=1);

use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;

return [
    /**
     * Fuzzy search pipeline configuration
     *
     * Defines the processing pipeline for search queries.
     * The order of stages is critical for proper operation.
     *
     * @var array<string, mixed>
     */
    'pipeline' => [
        'stages' => [
            NormalizeQueryStage::class,
            MatchDiscoveryStage::class,
            ScoringStage::class,
            SortAndLimitStage::class,
        ],
    ],

    /**
     * Stop words to ignore during indexing and searching
     *
     * This list can be extended via configuration but existing words
     * cannot be removed to ensure consistency.
     *
     * @var array<int, string>
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
        'over',
        'just',
        'should',
        'now',
    ],

    /**
     * Cache configuration for performance optimization
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
     * @var array<string, mixed>
     */
    'default_options' => [
        'min_score' => 0.1,
        'max_results' => 20,
        'fuzzy' => true,
        'threshold' => 0.3,
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
     * Controls how search results are scored and ranked.
     * Field weights are partially customizable.
     *
     * @var array<string, mixed>
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
            'short_query' => 0.4,
            'cross_word_match_multi' => 0.3,
        ],
        'bonuses' => [
            'full_coverage' => 0.3,
            'high_coverage' => 0.15,
            'early_position' => 0.2,
        ],
        'consecutive_bonus' => [
            2 => 1.05,
            3 => 1.15,
            4 => 1.30,
            5 => 1.50,
        ],
    ],
];
