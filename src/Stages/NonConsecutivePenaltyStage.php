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

    public function handle(SearchContext $context, Closure $next)
    {
        if (empty($context->results)) {
            return $next($context);
        }

        foreach ($context->results as $key => $result) {
            // Ne pas toucher aux scores parfaits
            if ($result->score < 1.0) {
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

        $queryLength = strlen($query);

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
}
