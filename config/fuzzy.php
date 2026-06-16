<?php

declare(strict_types=1);

return [

    /**
     * --------------------------------------------------------------------------
     * Fuzzy Search Package Configuration
     * --------------------------------------------------------------------------
     *
     * This file contains the configuration for the Fuzzy Search package.
     * All values can be customized to fine-tune the search behavior,
     * performance, and scoring logic.
     */

    /**
     * --------------------------------------------------------------------------
     * Cache Configuration
     * --------------------------------------------------------------------------
     *
     * Controls caching behavior to improve search performance.
     */
    'cache' => [

        /**
         * Enable/disable caching globally.
         * Impact: When disabled, every search recomputes results (slower but always fresh).
         */
        'enabled' => env('FUZZY_SEARCH_CACHE_ENABLED', true),

        /**
         * Prefix for all cache keys to avoid collisions.
         * Impact: Allows multiple applications to share the same cache store without conflicts.
         */
        'prefix' => 'fuzzy_search:',

        /**
         * Time-to-live (TTL) settings for different cache types in seconds.
         * Impact: Higher TTL improves performance but may return stale results.
         */
        'ttl' => [
            'search' => env('FUZZY_SEARCH_CACHE_TTL', 3600),
            'search_in_model' => env('FUZZY_SEARCH_MODEL_CACHE_TTL', 3600),
            'search_in_models' => env('FUZZY_SEARCH_MODELS_CACHE_TTL', 3600),
            'stats' => env('FUZZY_SEARCH_STATS_CACHE_TTL', 30),
        ],

        /**
         * Cache invalidation triggers.
         * Impact: When enabled, cache is automatically cleared when data changes.
         */
        'invalidation' => [
            'on_index' => true,
            'on_update' => true,
            'on_delete' => true,
        ],
    ],

    /**
     * --------------------------------------------------------------------------
     * Default Search Options
     * --------------------------------------------------------------------------
     *
     * Default values applied to all searches when not explicitly overridden.
     */
    'default_options' => [

        /**
         * Minimum relevance score for a result to be included (0.0 to 1.0).
         * Impact: Higher values return fewer but more relevant results.
         */
        'min_score' => 0.4,

        /**
         * Maximum number of results to return.
         * Impact: Limits result set size for performance and usability.
         */
        'max_results' => 20,

        /**
         * Enable/disable fuzzy matching.
         * Impact: When disabled, only exact and word matches are considered (faster but less flexible).
         */
        'fuzzy' => true,

        /**
         * Similarity threshold for fuzzy matches (0.0 to 1.0).
         * Impact: Higher values require closer matches, reducing false positives.
         */
        'threshold' => 0.4,
    ],

    /**
     * --------------------------------------------------------------------------
     * Index Configuration
     * --------------------------------------------------------------------------
     *
     * Controls how content is indexed for searching.
     */
    'index' => [

        /**
         * Minimum word length to index (characters).
         * Impact: Shorter words are ignored to reduce index size and noise.
         */
        'min_word_length' => 3,

        /**
         * Maximum word length to index (characters).
         * Impact: Longer words are truncated to this length to save memory.
         */
        'max_word_length' => 50,

        /**
         * Number of records to process per batch during indexing.
         * Impact: Smaller batches use less memory but are slower to complete.
         */
        'batch_size' => 100,

        /**
         * Queue indexing operations.
         * Impact: When true, indexing is pushed to queues for background processing.
         */
        'queue' => env('FUZZY_SEARCH_QUEUE', false),

        /**
         * Queue name for indexing operations.
         * Impact: Specifies which queue worker should process indexing jobs.
         */
        'queue_name' => env('FUZZY_SEARCH_QUEUE_NAME', 'default'),
    ],

    /**
     * --------------------------------------------------------------------------
     * Search Pipeline Configuration
     * --------------------------------------------------------------------------
     *
     * Defines custom stages that are executed BEFORE the default pipeline.
     * All custom stages must implement StageInterface.
     */
    'pipeline' => [
        // Add your custom stage classes here
    ],

    /**
     * --------------------------------------------------------------------------
     * Longest Common Substring (LCS) Algorithm Configuration
     * --------------------------------------------------------------------------
     *
     * Configuration spécifique à l'algorithme LCS qui trouve la plus longue
     * sous-chaîne commune entre deux mots.
     */
    'lcs' => [

        /**
         * Index de base pour l'initialisation des tableaux et la vérification des chaînes vides.
         * Impact: Détermine la valeur de départ pour les indices (généralement 0).
         */
        'base_index' => 0,

        /**
         * Valeur d'incrémentation pour les correspondances de caractères consécutifs.
         * Impact: Augmente le compteur de correspondance de cette valeur (généralement 1).
         */
        'match_increment' => 1,

        /**
         * Poids de l'algorithme LCS dans le calcul composite (0.0 à 1.0).
         * Impact: Plus le poids est élevé, plus l'influence de LCS sur le score final est grande.
         */
        'weight' => null,
    ],

    /**
     * --------------------------------------------------------------------------
     * Levenshtein Algorithm Configuration
     * --------------------------------------------------------------------------
     *
     * Configuration spécifique à l'algorithme Levenshtein qui mesure la distance
     * d'édition entre deux chaînes.
     */
    'levenshtein' => [

        /**
         * Longueur représentant une chaîne vide.
         * Impact: Détermine quand une chaîne est considérée comme vide (généralement 0).
         */
        'empty_string_length' => 0,

        /**
         * Seuil de distance pour appliquer une pénalité (nombre de caractères).
         * Impact: Les distances supérieures à ce seuil déclenchent une pénalité progressive.
         */
        'distance_penalty_threshold' => 3,

        /**
         * Facteur de pénalité de base pour la distance de Levenshtein.
         * Impact: Valeur de base multipliée par la distance pour calculer la pénalité.
         */
        'penalty_factor_base' => 0.7,

        /**
         * Réduction de pénalité par unité de distance.
         * Impact: À chaque point de distance supplémentaire, la pénalité diminue de cette valeur.
         */
        'penalty_reduction_per_distance' => 0.1,

        /**
         * Seuil pour le bonus de correspondance proche (nombre de caractères).
         * Impact: Les distances inférieures ou égales à ce seuil reçoivent un bonus.
         */
        'close_match_bonus_threshold' => 2,

        /**
         * Longueur minimale de mot pour bénéficier du bonus de correspondance proche.
         * Impact: Les mots plus courts que cette longueur ne reçoivent pas le bonus.
         */
        'min_length_for_bonus' => 4,

        /**
         * Bonus pour les correspondances proches.
         * Impact: Ajoute ce pourcentage de bonus aux correspondances très proches.
         */
        'close_match_bonus' => 0.1,

        /**
         * Poids de l'algorithme Levenshtein dans le calcul composite (0.0 à 1.0).
         * Impact: Plus le poids est élevé, plus l'influence de Levenshtein sur le score final est grande.
         */
        'weight' => null,
    ],

    /**
     * --------------------------------------------------------------------------
     * Prefix Algorithm Configuration
     * --------------------------------------------------------------------------
     *
     * Configuration spécifique à l'algorithme de préfixe qui compare le début
     * des chaînes pour les correspondances.
     */
    'prefix' => [

        /**
         * Longueur minimale de préfixe pour la correspondance (caractères).
         * Impact: Les préfixes plus courts que cette longueur sont ignorés.
         */
        'min_prefix_length' => 3,

        /**
         * Score de base pour les correspondances de préfixe.
         * Impact: Score attribué avant application du multiplicateur variable.
         */
        'prefix_base_score' => 0.4,

        /**
         * Multiplicateur variable pour l'ajustement du score de préfixe.
         * Impact: Multiplie le ratio de préfixe pour calculer la composante variable.
         */
        'prefix_variable_multiplier' => 0.3,

        /**
         * Plafond de score maximum pour les correspondances de préfixe.
         * Impact: Empêche les scores de préfixe de dépasser cette valeur.
         */
        'prefix_max_score' => 0.6,

        /**
         * Poids de l'algorithme de préfixe dans le calcul composite (0.0 à 1.0).
         * Impact: Plus le poids est élevé, plus l'influence du préfixe sur le score final est grande.
         */
        'weight' => null,
    ],

    /**
     * --------------------------------------------------------------------------
     * Regex Patterns for String Normalization
     * --------------------------------------------------------------------------
     *
     * Patterns d'expressions régulières utilisés par SimilarityCalculator pour
     * normaliser les chaînes avant comparaison.
     */
    'regex' => [

        /**
         * Pattern pour supprimer les caractères spéciaux.
         * Impact: Garde uniquement les lettres, chiffres et espaces.
         */
        'remove_special_chars' => '/[^a-z0-9\s]/i',

        /**
         * Pattern pour remplacer les espaces multiples par un seul espace.
         * Impact: Normalise les espaces pour éviter les doublons.
         */
        'collapse_spaces' => '/\s+/',

        /**
         * Pattern pour diviser les mots.
         * Impact: Sépare sur les espaces, tirets, underscores, virgules et points.
         */
        'word_splitter' => '/[\s\-_,\.]+/',
    ],

    /**
     * --------------------------------------------------------------------------
     * Base Similarity Configuration
     * --------------------------------------------------------------------------
     *
     * Shared parameters used across multiple similarity algorithms.
     */
    'similarity' => [

        /**
         * Minimum query length required for similarity calculation.
         * Impact: Shorter queries are ignored to avoid meaningless searches.
         */
        'min_query_length' => 3,

        /**
         * Weights for each similarity algorithm in composite scoring.
         * Impact: Higher weights give more importance to that algorithm's score.
         */
        'algorithm_weights' => [
            'longest_common_substring' => 0.7,
            'levenshtein' => 0.25,
            'prefix' => 0.05,
        ],

        /**
         * Threshold for applying coverage bonus (0.0 to 1.0).
         * Impact: When coverage ratio exceeds this, a bonus is applied to the score.
         */
        'coverage_bonus_threshold' => 0.5,

        /**
         * Multiplier for coverage bonus when threshold is met.
         * Impact: Higher values give larger bonuses for good coverage.
         */
        'coverage_bonus_multiplier' => 0.15,

        /**
         * Multiplier for low coverage penalty.
         * Impact: When coverage is low, scores are multiplied by this factor.
         */
        'low_coverage_multiplier' => 1.5,

        /**
         * Ratio threshold for high containment detection (0.0 to 1.0).
         * Impact: When containment ratio exceeds this, special high scoring is applied.
         */
        'containment_high_ratio' => 0.8,

        /**
         * Score when query is contained in target above high ratio.
         * Impact: Higher values give more weight to contained matches.
         */
        'containment_query_in_target_high_score' => 0.95,

        /**
         * Score when target is contained in query above high ratio.
         * Impact: Higher values give more weight to when target is inside query.
         */
        'containment_target_in_query_high_score' => 0.9,

        /**
         * Base score for query-in-target containment (below high ratio).
         * Impact: Starting score before containment ratio multiplier is applied.
         */
        'containment_base_score_query_in_target' => 0.75,

        /**
         * Base score for target-in-query containment (below high ratio).
         * Impact: Starting score before containment ratio multiplier is applied.
         */
        'containment_base_score_target_in_query' => 0.65,

        /**
         * Multiplier for query-in-target score adjustment.
         * Impact: Controls how much containment ratio increases the score.
         */
        'containment_multiplier_query_in_target' => 0.2,

        /**
         * Multiplier for target-in-query score adjustment.
         * Impact: Controls how much containment ratio increases the score.
         */
        'containment_multiplier_target_in_query' => 0.25,

        /**
         * Maximum score cap for query-in-target matches.
         * Impact: Prevents scores from exceeding this value for containment matches.
         */
        'containment_max_score_query_in_target' => 0.9,

        /**
         * Maximum score cap for target-in-query matches.
         * Impact: Prevents scores from exceeding this value for containment matches.
         */
        'containment_max_score_target_in_query' => 0.85,

        /**
         * High containment ratio threshold (0.0 to 1.0).
         * Impact: Determines what ratio qualifies as "high" containment for scoring.
         */
        'containment_ratio_high' => 0.8,

        /**
         * Medium containment ratio threshold (0.0 to 1.0).
         * Impact: Determines what ratio qualifies as "medium" containment for scoring.
         */
        'containment_ratio_medium' => 0.5,

        /**
         * Multiplier for high containment matches.
         * Impact: Higher values give larger scores to high-containment matches.
         */
        'containment_high_multiplier' => 1.8,

        /**
         * Multiplier for medium containment matches.
         * Impact: Higher values give larger scores to medium-containment matches.
         */
        'containment_medium_multiplier' => 2.5,

        /**
         * Penalty per unmatched letter.
         * Impact: Higher values penalize unmatched letters more heavily.
         */
        'unmatched_letter_penalty' => 0.35,

        /**
         * Maximum score cap.
         * Impact: Prevents scores from exceeding this value.
         */
        'max_score_cap' => 7.0,

        /**
         * Penalty per character for word mismatches.
         * Impact: Higher values penalize character differences more.
         */
        'word_penalty_per_char' => 0.10,

        /**
         * Multiplier for length-based penalties.
         * Impact: Amplifies penalties based on string length differences.
         */
        'length_penalty_multiplier' => 0.04,

        /**
         * Minimum penalty value to avoid zero penalties.
         * Impact: Ensures non-exact matches always have at least this penalty.
         */
        'minimal_penalty' => 0.7,

        /**
         * Penalty for fuzzy matches.
         * Impact: Applied when matches are not exact but still considered valid.
         */
        'match_fuzziness_penalty' => 0.4,

        /**
         * Minimum ratio for word matches (0.0 to 1.0).
         * Impact: Words with similarity below this are considered non-matching.
         */
        'min_word_match_ratio' => 0.90,

        /**
         * Threshold for short word detection.
         * Impact: Words shorter than this are filtered out from matching.
         */
        'short_word_threshold' => 3,

        /**
         * Threshold for very bad matches.
         * Impact: Scores above this trigger an additional penalty.
         */
        'very_bad_match_threshold' => 3.0,

        /**
         * Penalty for very bad matches.
         * Impact: Added to scores above the very bad match threshold.
         */
        'very_bad_match_penalty' => 1.5,

        /**
         * Strictness factor increase per word.
         * Impact: For multi-word queries, each word adds this factor to strictness.
         */
        'strictness_factor_per_word' => 0.05,

        /**
         * Threshold for real similarity detection (0.0 to 1.0).
         * Impact: Similarity below this triggers additional real similarity checks.
         */
        'real_similarity_threshold' => 0.50,

        /**
         * Base penalty when real similarity is below threshold.
         * Impact: Applied as a base before multiplying by the similarity multiplier.
         */
        'real_similarity_base_penalty' => 1.5,

        /**
         * Multiplier for real similarity penalty.
         * Impact: Amplifies the penalty based on low real similarity.
         */
        'real_similarity_multiplier' => 1.5,

        /**
         * Threshold for low similarity detection (0.0 to 1.0).
         * Impact: Similarity below this triggers an additional penalty.
         */
        'low_similarity_threshold' => 0.45,

        /**
         * Penalty for low similarity matches.
         * Impact: Applied when similarity falls below the low similarity threshold.
         */
        'low_similarity_penalty' => 2.0,

        /**
         * Threshold for basic similarity fallback (0.0 to 1.0).
         * Impact: When basic similarity is below this, fallback scoring is used.
         */
        'basic_similarity_threshold' => 0.2,

        /**
         * Fallback value for very low basic similarity.
         * Impact: Used when basic similarity is very low to provide a reasonable score.
         */
        'basic_similarity_fallback' => 2.5,

        /**
         * Penalty for length differences between strings.
         * Impact: Multiplied by the length difference and added to score.
         */
        'length_difference_penalty' => 0.1,

        /**
         * Reduction factor for phonetic similarity.
         * Impact: When words sound similar, scores are multiplied by this factor.
         */
        'phonetic_reduction_factor' => 0.6,

        /**
         * Threshold for low global similarity (0.0 to 1.0).
         * Impact: When global similarity is below this, fallback scoring is used.
         */
        'low_global_similarity_threshold' => 0.25,

        /**
         * Fallback penalty for low global similarity.
         * Impact: Applied when global similarity is very low.
         */
        'low_global_similarity_fallback' => 1.5,

        /**
         * Search window size for local matching (characters).
         * Impact: Larger windows find more matches but are slower.
         */
        'search_window_size' => 2,

        /**
         * Penalty when match distance is zero.
         * Impact: Applied when letters match at exactly the same position.
         */
        'match_distance_zero_penalty' => 0.1,

        /**
         * Maximum ceiling for score adjustments.
         * Impact: Caps how much distance can affect the penalty calculation.
         */
        'max_ceiling' => 2.5,

        /**
         * Divisor for ceiling calculations.
         * Impact: Used to normalize distance values against string length.
         */
        'ceiling_divisor' => 2.5,

        /**
         * Base value for penalty adjustment.
         * Impact: Starting point for calculating position-based penalties.
         */
        'penalty_adjustment_base' => 0.6,

        /**
         * Maximum adjusted penalty value.
         * Impact: Caps the maximum penalty that can be applied after adjustments.
         */
        'max_adjusted_penalty' => 3.0,

        /**
         * Context radius for phonetic analysis (characters).
         * Impact: Number of characters on each side to consider for context matching.
         */
        'phonetic_context_radius' => 2,

        /**
         * Reduction for exact context matches.
         * Impact: Amount subtracted from penalty when contexts match exactly.
         */
        'phonetic_reduction_exact_context' => 0.12,

        /**
         * Reduction for similar context matches.
         * Impact: Amount subtracted from penalty when contexts are similar (>70%).
         */
        'phonetic_reduction_similar_context' => 0.08,

        /**
         * Threshold for phonetic similarity percentage (0-100).
         * Impact: Contexts with similarity above this are considered "similar".
         */
        'phonetic_similarity_percent_threshold' => 70.0,

        /**
         * Penalty for imperfect matches.
         * Impact: Applied per non-exact matched letter pair.
         */
        'imperfect_match_penalty' => 0.1,

        /**
         * Multiplier for unmatched letter penalties.
         * Impact: Amplifies the effect of unmatched letter penalties.
         */
        'unmatched_letter_multiplier' => 2.5,
    ],

    /**
     * --------------------------------------------------------------------------
     * Word Similarity Comparator Configuration
     * --------------------------------------------------------------------------
     *
     * Fine-tuning for the advanced lexical similarity algorithm.
     * These parameters control letter-by-letter matching, phonetic similarity,
     * and dynamic penalty calculations.
     */
    'word_similarity' => [
        'score_multiplier' => 100,
        'sigma' => 1.0,
        'high_containment_ratio' => 0.8,
        'medium_containment_ratio' => 0.5,
        'min_length_for_division' => 1,
        'base_increment' => 1,
        'start_index' => 0,
        'empty_text_penalty_factor' => 100,
        'max_score_cap' => 10.0,
        'unmatched_letter_penalty' => 0.35,
        'unmatched_letter_multiplier' => 2.5,
        'word_penalty_per_char' => 0.10,
        'length_penalty_multiplier' => 0.04,
        'minimal_penalty' => 0.7,
        'match_fuzziness_penalty' => 0.4,
        'min_word_match_ratio' => 0.90,
        'short_word_threshold' => 3,
        'very_bad_match_threshold' => 3.0,
        'very_bad_match_penalty' => 1.5,
        'strictness_factor_per_word' => 0.05,
        'real_similarity_threshold' => 0.50,
        'real_similarity_base_penalty' => 1.5,
        'real_similarity_multiplier' => 1.5,
        'low_similarity_threshold' => 0.45,
        'low_similarity_penalty' => 2.0,
        'basic_similarity_threshold' => 0.2,
        'basic_similarity_fallback' => 2.5,
        'length_difference_penalty' => 0.1,
        'phonetic_reduction_factor' => 0.6,
        'low_global_similarity_threshold' => 0.25,
        'low_global_similarity_fallback' => 1.5,
        'search_window_size' => 2,
        'match_distance_zero_penalty' => 0.1,
        'max_ceiling' => 2.5,
        'ceiling_divisor' => 2.5,
        'penalty_adjustment_base' => 0.6,
        'max_adjusted_penalty' => 3.0,
        'phonetic_context_radius' => 2,
        'phonetic_reduction_exact_context' => 0.12,
        'phonetic_reduction_similar_context' => 0.08,
        'phonetic_similarity_percent_threshold' => 70.0,
        'imperfect_match_penalty' => 0.1,
        'containment_ratio_high' => 0.8,
        'containment_ratio_medium' => 0.5,
        'containment_high_multiplier' => 1.8,
        'containment_medium_multiplier' => 2.5,
    ],

    /**
     * --------------------------------------------------------------------------
     * Relevance Scoring Configuration
     * --------------------------------------------------------------------------
     *
     * Controls how final relevance scores are calculated and normalized.
     * These settings affect the end-user result ranking.
     */
    'relevance_scoring' => [
        'penalty' => 10.0,
        'default_max_results' => 20,
        'original_score_weight' => 0.7,
        'relevance_score_weight' => 0.3,
        'max_normalized_relevance' => 100.0,
        'min_normalized_relevance' => 0.0,
        'normalization_factor' => 10.0,
    ],

    /**
     * --------------------------------------------------------------------------
     * Scoring Configuration
     * --------------------------------------------------------------------------
     *
     * Controls scoring behavior including coverage bonuses and field weights.
     */
    'scoring' => [

        /**
         * Coverage Bonus Configuration
         * ------------------------------------------------
         * Defines thresholds and bonuses for query word coverage.
         * When a search matches a high percentage of query words, additional
         * bonus points are added to the final score.
         */
        'coverage_bonus' => [

            /**
             * Minimum coverage ratio to apply full bonus (0.0 to 1.0).
             * Example: 0.75 means 75% of query words must match to get full bonus.
             * Impact: Higher thresholds make full bonus harder to achieve.
             */
            'full_threshold' => 0.75,

            /**
             * Minimum coverage ratio to apply high bonus (0.0 to 1.0).
             * Example: 0.50 means 50% of query words must match to get high bonus.
             * Impact: Lower thresholds make bonuses more accessible.
             */
            'high_threshold' => 0.50,

            /**
             * Bonus score added when coverage meets or exceeds full threshold.
             * Example: 0.30 adds 30% of base score as bonus.
             * Impact: Higher values reward full coverage more generously.
             */
            'full_bonus' => 0.30,

            /**
             * Bonus score added when coverage meets or exceeds high threshold.
             * Example: 0.15 adds 15% of base score as bonus.
             * Impact: Higher values reward partial coverage more generously.
             */
            'high_bonus' => 0.15,
        ],

        /**
         * Field weights for scoring (1.0 = normal importance).
         * Impact: Higher weights make matches in that field more important for ranking.
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
         * Consecutive match bonuses.
         * Impact: Longer consecutive character matches receive higher bonuses.
         */
        'consecutive_bonus' => [
            2 => 1.05,
            3 => 1.10,
            4 => 1.35,
            5 => 1.50,
        ],

        /**
         * Penalty values for low-quality matches.
         * Impact: Higher penalties reduce scores more aggressively.
         */
        'penalties' => [
            'short_query' => 0.4,
        ],

        /**
         * Bonus values for high-quality matches.
         * Impact: Higher bonuses increase scores for desirable match characteristics.
         *
         * @deprecated Use 'coverage_bonus' section instead for coverage-related bonuses
         */
        'bonuses' => [
            'early_position' => 0.2,
            'full_coverage' => 0.3,
            'high_coverage' => 0.15,
        ],
        'min_consecutive_length' => 2,
        'max_consecutive_bonus_key' => 5,
        'early_position_threshold' => 0.2,
        'mid_position_threshold' => 0.4,
        'mid_position_bonus' => 1.1,
        'short_query_threshold' => 4,
        'min_substring_end_offset' => 2,
        'min_available_space' => 1,
    ],

    /**
     * --------------------------------------------------------------------------
     * Match Discovery Configuration
     * --------------------------------------------------------------------------
     *
     * Controls how potential matches are discovered in the index.
     * These settings affect search performance and recall.
     */
    'match_discovery' => [
        'cache_ttl' => 300,
        'small_index_threshold' => 1000,
        'high_threshold' => 0.7,
        'max_length_difference' => 2,
        'small_word_length' => 3,
        'medium_word_length' => 6,
        'max_checks_per_query' => 500,
        'max_trigram_candidates' => 100,
        'max_contained_checks' => 200,
        'max_cache_entries' => 20,
        'cache_cleanup_probability' => 100,
        'small_word_offset' => 3,
        'medium_word_offset' => 2,
        'large_word_offset' => 1,
        'min_word_length' => 2,
        'min_trigram_length' => 3,
    ],

];
