<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class NonConsecutivePenaltyStage
{
    private const PENALTY_MULTIPLIERS = [
        3 => 0.5,  // Requête de 3 caractères sans match consécutif: -50%
        4 => 0.6,  // Requête de 4 caractères sans match consécutif: -40%
        5 => 0.7,  // Requête de 5 caractères sans match consécutif: -30%
        6 => 0.8,  // Requête de 6 caractères sans match consécutif: -20%
    ];

    private const MULTI_WORD_DISPERSION_SEVERE_PENALTY = 0.3; // -70% pour dispersion multi-mots
    private const MULTI_WORD_DISPERSION_MODERATE_PENALTY = 0.5; // -50% pour dispersion modérée

    public function handle(SearchContext $context, Closure $next)
    {
        if (empty($context->results)) {
            return $next($context);
        }

        foreach ($context->results as $key => $result) {
            // Ne pas pénaliser les scores très élevés (>= 0.85)
            if ($result->score < 0.85) {
                $penalizedScore = $this->calculatePenalizedScore($context, $result);

                // Ne pas descendre en dessous du minScore
                if ($penalizedScore >= $context->options->minScore) {
                    $result->score = $penalizedScore;
                } else {
                    // Supprimer le résultat si le score devient trop bas
                    unset($context->results[$key]);
                }
            }
        }

        return $next($context);
    }

    private function calculatePenalizedScore(SearchContext $context, SearchResultData $result): float
    {
        $score = $result->score;
        $query = strtolower(trim($context->query));
        $matchedValue = strtolower($result->matchedValue);

        // NE PAS PÉNALISER si c'est une correspondance exacte ou quasi-exacte
        if ($this->isExactOrNearExactMatch($query, $matchedValue, $context)) {
            return $score;
        }

        $queryLength = strlen($query);

        // Pénalité pour dispersion multi-mots (EX: "unpdm" vs "un bon produit de mode")
        $multiWordDispersionPenalty = $this->calculateMultiWordDispersionPenalty($query, $matchedValue);
        $score *= $multiWordDispersionPenalty;

        // Seulement pour les requêtes courtes (3-6 caractères)
        if ($queryLength >= 3 && $queryLength <= 6) {
            $hasConsecutiveMatch = $this->hasConsecutiveMatch($query, $matchedValue);

            if (!$hasConsecutiveMatch) {
                // Calculer la pénalité basée sur la dispersion des caractères
                $dispersionPenalty = $this->calculateDispersionPenalty($query, $matchedValue);
                $lengthPenalty = self::PENALTY_MULTIPLIERS[$queryLength] ?? 0.7;

                // Combiner les pénalités
                $combinedPenalty = min($dispersionPenalty, $lengthPenalty);
                $score *= $combinedPenalty;

                // Pénalité supplémentaire si le mot n'est pas au début
                if (!$this->isAtWordBeginning($query, $matchedValue)) {
                    $score *= 0.9; // -10% supplémentaire
                }
            }
        }

        return max($score, $context->options->minScore);
    }

    private function calculateMultiWordDispersionPenalty(string $query, string $text): float
    {
        // Diviser le texte en mots
        $words = preg_split('/[\s\-_,\.]+/', $text);

        if (count($words) <= 1) {
            return 1.0; // Pas de dispersion multi-mots si un seul mot
        }

        // Pour chaque caractère de la requête, trouver dans quel mot il apparaît
        $wordPositions = [];
        $queryChars = str_split($query);

        foreach ($queryChars as $charIndex => $char) {
            foreach ($words as $wordIndex => $word) {
                if (str_contains($word, $char)) {
                    if (!isset($wordPositions[$charIndex])) {
                        $wordPositions[$charIndex] = [];
                    }
                    $wordPositions[$charIndex][] = $wordIndex;
                }
            }
        }

        // Si aucun caractère n'est trouvé dans aucun mot, pénalité maximale
        if (empty($wordPositions)) {
            return self::MULTI_WORD_DISPERSION_SEVERE_PENALTY;
        }

        // Analyser la distribution des caractères sur les mots
        $charDistribution = [];
        foreach ($wordPositions as $charIndex => $wordIndices) {
            foreach ($wordIndices as $wordIndex) {
                $charDistribution[$wordIndex][] = $charIndex;
            }
        }

        // Compter combien de mots contiennent des caractères de la requête
        $wordsWithQueryChars = count($charDistribution);
        $totalWords = count($words);

        // Si les caractères sont dispersés sur plusieurs mots, appliquer une pénalité
        if ($wordsWithQueryChars > 1) {
            // Calculer le ratio de dispersion
            $dispersionRatio = $wordsWithQueryChars / min($totalWords, strlen($query));

            // Plus la dispersion est grande, plus la pénalité est sévère
            if ($dispersionRatio > 0.8) {
                // Très dispersé (ex: "unpdm" sur 5 mots)
                return self::MULTI_WORD_DISPERSION_SEVERE_PENALTY;
            } elseif ($dispersionRatio > 0.5) {
                // Modérément dispersé
                return self::MULTI_WORD_DISPERSION_MODERATE_PENALTY;
            } elseif ($dispersionRatio > 0.3) {
                // Légèrement dispersé
                return 0.7; // -30%
            }
        }

        return 1.0; // Pas de pénalité
    }

    private function isExactOrNearExactMatch(string $query, string $text, SearchContext $context): bool
    {
        // Vérifier si la requête correspond exactement à la valeur
        if ($query === $text) {
            return true;
        }

        // Vérifier si la requête est contenue dans la valeur (match partiel)
        if (str_contains($text, $query)) {
            return true;
        }

        // Vérifier si c'est une correspondance de phrase complète
        $normalizedText = $this->normalizeForComparison($text);
        if ($context->normalizedQuery === $normalizedText) {
            return true;
        }

        // Vérifier les mots qui commencent par la requête
        $words = preg_split('/[\s\-_,\.]+/', $text);
        foreach ($words as $word) {
            if (str_starts_with($word, $query) && strlen($query) >= 3) {
                return true;
            }
        }

        return false;
    }

    private function hasConsecutiveMatch(string $query, string $text): bool
    {
        // Vérifier la présence de sous-chaînes consécutives
        for ($len = strlen($query); $len >= 3; $len--) {
            for ($i = 0; $i <= strlen($query) - $len; $i++) {
                $substring = substr($query, $i, $len);
                if (str_contains($text, $substring)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function calculateDispersionPenalty(string $query, string $text): float
    {
        // Trouver les positions des caractères de la requête dans le texte
        $positions = [];
        $queryChars = str_split($query);

        foreach ($queryChars as $char) {
            $pos = strpos($text, $char);
            if ($pos !== false) {
                $positions[] = $pos;
            }
        }

        // Si tous les caractères ne sont pas trouvés, pénalité maximale
        if (count($positions) < strlen($query)) {
            return 0.4; // -60%
        }

        // Calculer l'écart-type des positions (mesure de dispersion)
        if (count($positions) > 1) {
            $mean = array_sum($positions) / count($positions);
            $sumSquares = 0;

            foreach ($positions as $pos) {
                $sumSquares += pow($pos - $mean, 2);
            }

            $stdDev = sqrt($sumSquares / count($positions));

            // Plus l'écart-type est grand, plus les caractères sont dispersés
            if ($stdDev > 5) {
                return 0.5; // -50%
            } elseif ($stdDev > 3) {
                return 0.6; // -40%
            } elseif ($stdDev > 2) {
                return 0.7; // -30%
            }
        }

        return 0.8; // -20% par défaut
    }

    private function isAtWordBeginning(string $query, string $text): bool
    {
        $words = preg_split('/[\s\-_,\.]+/', $text);

        foreach ($words as $word) {
            if (str_starts_with($word, substr($query, 0, 3))) {
                return true;
            }

            // Vérifier les 3 premiers caractères avec une certaine tolérance
            $wordStart = substr($word, 0, min(4, strlen($word)));
            $queryStart = substr($query, 0, min(3, strlen($query)));

            $similarity = similar_text($wordStart, $queryStart) / max(strlen($wordStart), strlen($queryStart));
            if ($similarity >= 0.7) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize string for comparison (same as StringNormalizer)
     */
    private function normalizeForComparison(string $str): string
    {
        if (empty($str)) {
            return '';
        }

        return (string) \Illuminate\Support\Str::of($str)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s_-]/', '')
            ->replaceMatches('/\s+/', ' ')
            ->toString();
    }
}
