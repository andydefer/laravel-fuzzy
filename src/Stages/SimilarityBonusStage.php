<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Fuzzy\Data\SearchResultData;
use Closure;

class SimilarityBonusStage
{
    private const CONSECUTIVE_BONUS_MULTIPLIER = [
        2 => 1.1,  // 2 caractères consécutifs: +10%
        3 => 1.3,  // 3 caractères consécutifs: +30%
        4 => 1.6,  // 4 caractères consécutifs: +60%
        5 => 2.0,  // 5+ caractères consécutifs: +100%
    ];

    private const WORD_POSITION_BONUS = [
        'first' => 1.3,   // Premier mot: +30%
        'middle' => 1.1,  // Mot du milieu: +10%
        'last' => 1.2,    // Dernier mot: +20%
    ];

    private const COMMON_WORD_BONUS = [
        'saint' => 1.5,
        'clinique' => 1.4,
        'hospital' => 1.4,
        'medical' => 1.3,
        'center' => 1.3,
        'centre' => 1.3,
        'polyclinique' => 1.6,
        'cabinet' => 1.3,
    ];

    private const COMMON_SUFFIX_BONUS = [
        'ing' => 1.2,
        'ion' => 1.2,
        'ment' => 1.1,
        'ique' => 1.3,
        'iste' => 1.2,
        'oire' => 1.2,
        'euse' => 1.2,
    ];

    public function handle(SearchContext $context, Closure $next)
    {
        if (empty($context->results)) {
            return $next($context);
        }

        $enhancedResults = [];

        foreach ($context->results as $key => $result) {
            $enhancedScore = $this->enhanceScore($context, $result);

            if ($enhancedScore >= $context->options->minScore) {
                $result->score = $enhancedScore;
                $enhancedResults[$key] = $result;
            }
        }

        $context->results = $enhancedResults;
        return $next($context);
    }

    private function enhanceScore(SearchContext $context, SearchResultData $result): float
    {
        $baseScore = $result->score;
        $model = $result->item;

        if (!$model) {
            return $baseScore;
        }

        // Récupérer toutes les valeurs indexées pour ce modèle DEPUIS LE CONTEXTE
        $indexEntries = $this->getIndexEntriesForModel($context, $result->modelType, $model->getIndexableId());

        if (empty($indexEntries)) {
            return $baseScore;
        }

        $bestEnhancedScore = $baseScore;

        foreach ($context->queryWords as $queryWord) {
            foreach ($indexEntries as $entry) {
                if (!isset($entry['normalized_words'])) {
                    continue;
                }

                foreach ($entry['normalized_words'] as $targetWord) {
                    $targetWord = (string) $targetWord;

                    // Calculer la similarité de base
                    $wordSimilarity = $context->similarityCalculator->calculateWordSimilarity($queryWord, $targetWord);

                    if ($wordSimilarity > 0.1) {
                        $enhancedSimilarity = $this->applySimilarityBonuses($queryWord, $targetWord, $wordSimilarity);

                        // Appliquer le poids du champ
                        $fieldWeight = $entry['weight'] ?? 1.0;
                        $fieldEnhancedScore = $enhancedSimilarity * $fieldWeight;

                        // Bonus pour position dans le mot
                        $fieldName = $entry['field'] ?? 'name';
                        $positionBonus = $this->calculateWordPositionBonus($targetWord, $fieldName);
                        $fieldEnhancedScore *= $positionBonus;

                        // Bonus pour mots communs
                        $commonWordBonus = $this->calculateCommonWordBonus($targetWord);
                        $fieldEnhancedScore *= $commonWordBonus;

                        $bestEnhancedScore = max($bestEnhancedScore, $fieldEnhancedScore);
                    }
                }
            }
        }

        // Bonus pour correspondance multi-mots
        if (count($context->queryWords) > 1) {
            $multiWordBonus = $this->calculateMultiWordBonus($context, $indexEntries);
            $bestEnhancedScore = min($bestEnhancedScore * $multiWordBonus, 1.0);
        }

        return min($bestEnhancedScore, 1.0);
    }

    private function getIndexEntriesForModel(SearchContext $context, string $modelType, $modelId): array
    {
        $indexEntries = [];

        // Parcourir TOUT l'index de mots pour trouver les entrées de ce modèle
        foreach ($context->wordIndex as $word => $matches) {
            foreach ($matches as $match) {
                if ($match['indexable_type'] === $modelType && $match['indexable_id'] == $modelId) {
                    // Éviter les doublons
                    $key = $match['field'] . '_' . ($match['original_value'] ?? '');
                    if (!isset($indexEntries[$key])) {
                        $indexEntries[$key] = $match;
                    }
                }
            }
        }

        return array_values($indexEntries);
    }


    private function applySimilarityBonuses(string $queryWord, string $targetWord, float $baseSimilarity): float
    {
        $enhancedSimilarity = $baseSimilarity;

        // 1. Bonus pour caractères consécutifs
        $consecutiveBonus = $this->calculateConsecutiveCharactersBonus($queryWord, $targetWord);
        $enhancedSimilarity *= $consecutiveBonus;

        // 2. Bonus pour sous-chaînes longues
        $substringBonus = $this->calculateLongSubstringBonus($queryWord, $targetWord);
        $enhancedSimilarity *= $substringBonus;

        // 3. Bonus pour caractères similaires (indépendamment de l'ordre)
        $similarCharsBonus = $this->calculateSimilarCharactersBonus($queryWord, $targetWord);
        $enhancedSimilarity = $enhancedSimilarity * 0.7 + $similarCharsBonus * 0.3;

        // 4. Bonus pour fautes de frappe courantes (germoin -> germain)
        $typoBonus = $this->calculateCommonTypoBonus($queryWord, $targetWord);
        $enhancedSimilarity = min($enhancedSimilarity + $typoBonus, 1.0);

        // 5. Bonus pour suffixes communs
        $suffixBonus = $this->calculateCommonSuffixBonus($queryWord, $targetWord);
        $enhancedSimilarity *= $suffixBonus;

        return min($enhancedSimilarity, 1.0);
    }

    /**
     * Bonus pour caractères consécutifs
     * Exemple: "galoise" vs "giloise" -> "loise" (4 caractères consécutifs)
     */
    private function calculateConsecutiveCharactersBonus(string $queryWord, string $targetWord): float
    {
        $queryLength = strlen($queryWord);
        $targetLength = strlen($targetWord);
        $maxConsecutive = 0;

        // Chercher la plus longue sous-chaîne consécutive commune
        for ($i = 0; $i < $queryLength; $i++) {
            for ($j = $i + 2; $j <= $queryLength; $j++) { // Au moins 2 caractères
                $substring = substr($queryWord, $i, $j - $i);
                if (str_contains($targetWord, $substring)) {
                    $maxConsecutive = max($maxConsecutive, strlen($substring));
                }
            }
        }

        if ($maxConsecutive >= 2) {
            return self::CONSECUTIVE_BONUS_MULTIPLIER[min($maxConsecutive, 5)] ?? 1.0;
        }

        return 1.0;
    }

    /**
     * Bonus pour sous-chaînes longues
     */
    private function calculateLongSubstringBonus(string $queryWord, string $targetWord): float
    {
        $longestSubstring = $this->findLongestCommonSubstring($queryWord, $targetWord);
        $length = strlen($longestSubstring);

        if ($length >= 4) {
            // 4+ caractères identiques: gros bonus
            return 1.0 + ($length * 0.1);
        }

        return 1.0;
    }

    /**
     * Bonus pour caractères similaires (peu importe l'ordre)
     */
    private function calculateSimilarCharactersBonus(string $queryWord, string $targetWord): float
    {
        $queryChars = array_unique(str_split(strtolower($queryWord)));
        $targetChars = array_unique(str_split(strtolower($targetWord)));

        $commonChars = array_intersect($queryChars, $targetChars);
        $totalUniqueChars = count(array_unique(array_merge($queryChars, $targetChars)));

        if ($totalUniqueChars === 0) {
            return 0.0;
        }

        $similarity = count($commonChars) / $totalUniqueChars;

        // Bonus si beaucoup de caractères communs
        if (count($commonChars) >= 3) {
            $similarity *= 1.2;
        }

        return min($similarity, 1.0);
    }

    /**
     * Bonus pour fautes de frappe courantes
     */
    private function calculateCommonTypoBonus(string $queryWord, string $targetWord): float
    {
        $commonTypos = [
            // Voyelles
            'ai' => ['ei', 'ay', 'ey'],
            'ei' => ['ai', 'ey', 'ay'],
            'oi' => ['oy', 'ai'],
            'oy' => ['oi', 'ai'],
            'ou' => ['u', 'oo'],
            'an' => ['en', 'on'],
            'en' => ['an', 'on'],
            'on' => ['an', 'en'],
            'in' => ['yn', 'en'],
            'yn' => ['in', 'en'],

            // Consonnes
            'ph' => ['f'],
            'f' => ['ph'],
            'th' => ['t', 'd'],
            'ch' => ['sh', 'k'],
            'sh' => ['ch'],
            'gn' => ['n', 'ng'],
            'ng' => ['gn', 'n'],

            // Doubles lettres
            'ss' => ['s', 'c'],
            'tt' => ['t'],
            'll' => ['l'],
            'mm' => ['m'],
            'nn' => ['n'],
            'pp' => ['p'],
            'rr' => ['r'],
        ];

        $queryWord = strtolower($queryWord);
        $targetWord = strtolower($targetWord);

        foreach ($commonTypos as $correct => $typos) {
            // Vérifier si on a une faute dans la requête
            if (str_contains($queryWord, $correct) && !str_contains($targetWord, $correct)) {
                foreach ($typos as $typo) {
                    if (str_contains($targetWord, $typo)) {
                        return 0.3; // Bon bonus pour faute courante
                    }
                }
            }

            // Ou dans la cible
            if (str_contains($targetWord, $correct) && !str_contains($queryWord, $correct)) {
                foreach ($typos as $typo) {
                    if (str_contains($queryWord, $typo)) {
                        return 0.3;
                    }
                }
            }
        }

        return 0.0;
    }

    /**
     * Bonus pour suffixes communs (clinique, médical, etc.)
     */
    private function calculateCommonSuffixBonus(string $queryWord, string $targetWord): float
    {
        foreach (self::COMMON_SUFFIX_BONUS as $suffix => $bonus) {
            if (str_ends_with($targetWord, $suffix) && strlen($queryWord) >= 3) {
                // Vérifier si la requête a un suffixe similaire
                $querySuffix = substr($queryWord, -min(3, strlen($queryWord)));
                if ($this->areSuffixesSimilar($querySuffix, $suffix)) {
                    return $bonus;
                }
            }
        }

        return 1.0;
    }

    private function areSuffixesSimilar(string $suffix1, string $suffix2): bool
    {
        if ($suffix1 === $suffix2) {
            return true;
        }

        // Calculer la similarité entre suffixes
        $similarity = $this->calculateLevenshteinSimilarity($suffix1, $suffix2);
        return $similarity >= 0.6;
    }

    /**
     * Bonus pour position dans le mot
     */
    private function calculateWordPositionBonus(string $word, string $field): float
    {
        // Les noms en premier ont plus de poids
        if ($field === 'name' || $field === 'title') {
            return self::WORD_POSITION_BONUS['first'];
        }

        // Les descriptions ont moins de poids
        if ($field === 'description' || $field === 'content') {
            return self::WORD_POSITION_BONUS['middle'];
        }

        return 1.0;
    }

    /**
     * Bonus pour mots communs (clinique, saint, etc.)
     */
    private function calculateCommonWordBonus(string $word): float
    {
        $lowerWord = strtolower($word);

        foreach (self::COMMON_WORD_BONUS as $commonWord => $bonus) {
            if ($lowerWord === $commonWord || str_contains($lowerWord, $commonWord)) {
                return $bonus;
            }
        }

        return 1.0;
    }

    /**
     * Bonus pour correspondance multi-mots
     */
    private function calculateMultiWordBonus(SearchContext $context, array $indexEntries): float
    {
        $matchedWords = 0;

        foreach ($context->queryWords as $queryWord) {
            foreach ($indexEntries as $entry) {
                foreach ($entry['normalized_words'] as $targetWord) {
                    $similarity = $context->similarityCalculator->calculateWordSimilarity($queryWord, (string) $targetWord);
                    if ($similarity > 0.3) {
                        $matchedWords++;
                        break 2; // Passe au mot suivant de la requête
                    }
                }
            }
        }

        $coverage = $matchedWords / count($context->queryWords);

        if ($coverage === 1.0) {
            return 1.5; // +50% si tous les mots correspondent
        } elseif ($coverage >= 0.75) {
            return 1.3; // +30%
        } elseif ($coverage >= 0.5) {
            return 1.1; // +10%
        }

        return 1.0;
    }

    private function findLongestCommonSubstring(string $str1, string $str2): string
    {
        $longest = '';
        $str1Length = strlen($str1);

        for ($i = 0; $i < $str1Length; $i++) {
            for ($j = $i + 1; $j <= $str1Length; $j++) {
                $substring = substr($str1, $i, $j - $i);
                if (str_contains($str2, $substring) && strlen($substring) > strlen($longest)) {
                    $longest = $substring;
                }
            }
        }

        return $longest;
    }

    private function calculateLevenshteinSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }
}
