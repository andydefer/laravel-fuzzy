<?php
// helpers.php - Constantes globales pour éviter les magic numbers

declare(strict_types=1);

// ============================================
// CONSTANTES DE BASE POUR LES COMPARAISONS
// ============================================

if (!defined('FUZZY_SCORE_IDENTICAL')) {
    define('FUZZY_SCORE_IDENTICAL', 1.0);
}

if (!defined('FUZZY_SCORE_NONE')) {
    define('FUZZY_SCORE_NONE', 0.0);
}

// ============================================
// CONSTANTES POUR LA LONGUEUR DES MOTS
// ============================================


if (!defined('FUZZY_BASE_FACTOR')) {
    define('FUZZY_BASE_FACTOR', 1.0);
}


if (!defined('FUZZY_DISTANCE_IDENTICAL')) {
    define('FUZZY_DISTANCE_IDENTICAL', 0.0);
}
