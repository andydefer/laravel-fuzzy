<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration de la recherche floue
    |--------------------------------------------------------------------------
    |
    | Ce fichier vous permet de configurer le comportement de la recherche floue.
    | Certaines sections sont protégées pour assurer le bon fonctionnement.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Stop Words (AJOUTABLE SEULEMENT)
    |--------------------------------------------------------------------------
    |
    | Vous pouvez ajouter des mots à ignorer mais pas en supprimer.
    | Les mots de base sont toujours présents.
    |
    */
    'stop_words' => [
        // Ajoutez vos mots ici
        // 'votre_mot',
        // 'autre_mot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration du cache
    |--------------------------------------------------------------------------
    |
    | Contrôle le comportement de mise en cache.
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
    | Auto-discovery
    |--------------------------------------------------------------------------
    |
    | Configuration de la découverte automatique des modèles.
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
    | Options par défaut
    |--------------------------------------------------------------------------
    |
    | Paramètres par défaut pour les recherches.
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
    | Configuration de l'index
    |--------------------------------------------------------------------------
    |
    | Paramètres pour la construction de l'index.
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
    | Similarité
    |--------------------------------------------------------------------------
    |
    | Configuration des algorithmes de similarité.
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
    | Scoring (PARTIELLEMENT MODIFIABLE)
    |--------------------------------------------------------------------------
    |
    | Configuration du système de scoring. Certaines parties sont protégées.
    |
    */
    'scoring' => [
        'field_weights' => [
            // Vous pouvez ajouter ou modifier les poids de champs
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ],
        'penalties' => [
            // Vous pouvez ajuster les pénalités
            'short_query' => 0.4,
            'cross_word_match_multi' => 0.3,
        ],
        'bonuses' => [
            // Vous pouvez ajuster les bonus
            'full_coverage' => 0.3,
            'high_coverage' => 0.15,
            'early_position' => 0.2,
        ],
        // ATTENTION: 'consecutive_bonus' ne peut pas être modifié
    ],
];
