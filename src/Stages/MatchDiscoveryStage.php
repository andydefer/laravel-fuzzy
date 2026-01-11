<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\SearchContext;
use Closure;

class MatchDiscoveryStage
{
    // Cache amélioré avec expiration
    private static array $cachedOptimizedIndexes = [];
    private static array $cacheTimestamps = [];
    private const CACHE_TTL = 300; // 5 minutes

    public function handle(SearchContext $context, Closure $next)
    {
        if ($context->query->isEmpty()) {
            return $next($context);
        }

        // OPTIMISATION CRITIQUE : Détection précoce des requêtes à un mot avec match exact
        $hasExactMatches = $this->discoverExactMatches($context);

        // Si la requête est un mot unique ET qu'on a trouvé des matches exacts
        // ET que la recherche floue est activée, on doit réévaluer
        if (!$context->hasMultipleWords() && $hasExactMatches && $context->options->fuzzy) {
            // Pour les requêtes à un mot avec match exact, on applique une logique spéciale
            $this->handleSingleWordWithExactMatch($context);
        } else {
            // Logique normale pour les autres cas
            $this->discoverWordMatches($context);

            // Recherche floue optimisée (si activée)
            if ($context->options->fuzzy) {
                $this->discoverFuzzyMatchesOptimized($context);
            }

            // Découverte multi-mots (si nécessaire)
            if ($context->hasMultipleWords()) {
                $this->discoverMultiWordMatches($context);
            }
        }

        return $next($context);
    }

    /**
     * Découvre les matches exacts (requête complète).
     * RETOURNE : true si des matches exacts ont été trouvés
     */
    private function discoverExactMatches(SearchContext $context): bool
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        if (isset($wordIndex[$normalizedQuery])) {
            foreach ($wordIndex[$normalizedQuery] as $match) {
                $context->addPotentialMatch($match);
            }
            return true;
        }

        return false;
    }

    /**
     * Gestion spéciale pour les requêtes à un mot avec match exact
     * OPTIMISATION : Évite les recherches floues redondantes tout en gardant les optimisations
     */
    private function handleSingleWordWithExactMatch(SearchContext $context): void
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        // Pour une requête à un mot avec match exact, on applique une stratégie hybride :
        // 1. On garde les matches exacts (déjà ajoutés)
        // 2. On cherche UNIQUEMENT les matches flous TRÈS proches (threshold élevé)
        // 3. On évite la recherche floue générique O(n²)

        if (count($wordIndex) < 1000) {
            // Pour petits index, on peut chercher les matches très similaires
            $this->discoverVeryCloseMatches($context, $normalizedQuery, $wordIndex);
        } else {
            // Pour grands index, on utilise l'optimisation mais avec un threshold plus élevé
            $this->discoverCloseMatchesOptimized($context, $normalizedQuery);
        }
    }

    /**
     * Cherche uniquement les matches TRÈS proches (pour requêtes à un mot avec match exact)
     * OPTIMISATION : Threshold plus élevé + recherche ciblée
     */
    private function discoverVeryCloseMatches(SearchContext $context, string $queryWord, array $wordIndex): void
    {
        // Augmente le threshold pour ne garder que les matches très similaires
        $highThreshold = max($context->options->threshold, 0.7);

        foreach ($wordIndex as $indexedWord => $matches) {
            // Skip si trop court
            if (strlen((string)$indexedWord) < 2) {
                continue;
            }

            // OPTIMISATION : Filtre rapide par longueur similaire
            $queryLength = strlen($queryWord);
            $indexedLength = strlen((string)$indexedWord);
            if (abs($queryLength - $indexedLength) > 2) {
                continue;
            }

            // OPTIMISATION : Filtre par première lettre
            if ($queryWord[0] !== ((string)$indexedWord)[0]) {
                continue;
            }

            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                (string) $indexedWord
            );

            if ($similarity >= $highThreshold) {
                foreach ($matches as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Version optimisée pour les grands index (requêtes à un mot avec match exact)
     */
    private function discoverCloseMatchesOptimized(SearchContext $context, string $queryWord): void
    {
        $wordIndex = $context->getWordIndex();
        $optimizedIndexes = $this->getOrBuildOptimizedIndexes($wordIndex);

        // Augmente le threshold pour ne garder que les matches très similaires
        $highThreshold = max($context->options->threshold, 0.7);

        // Utilise uniquement la stratégie la plus précise (première lettre + longueur)
        // avec un threshold élevé
        $this->findMatchesByFirstCharAndLengthOptimized(
            $queryWord,
            $optimizedIndexes['byFirstChar'],
            $optimizedIndexes['byLength'],
            $context,
            $highThreshold // Threshold personnalisé
        );
    }

    /**
     * Découvre les matches par mot individuel.
     */
    private function discoverWordMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();

        foreach ($context->getQueryWords() as $queryWord) {
            // Ignorer les mots trop courts
            if (strlen($queryWord) < 2) {
                continue;
            }

            // Si la requête est un mot unique ET qu'on l'a déjà traité en exact match, on saute
            if (!$context->hasMultipleWords() && $queryWord === $context->getNormalizedQuery()) {
                continue;
            }

            if (isset($wordIndex[$queryWord])) {
                foreach ($wordIndex[$queryWord] as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Découvre les matches flous OPTIMISÉS.
     * Complexité : O(k) où k = quelques centaines, indépendant de la taille de l'index
     */
    private function discoverFuzzyMatchesOptimized(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();

        if (empty($wordIndex)) {
            return;
        }

        // OPTIMISATION : Si l'index est petit (< 1000 mots), on peut utiliser la méthode simple
        if (count($wordIndex) < 1000) {
            $this->discoverFuzzyMatchesSimple($context, $wordIndex);
            return;
        }

        // Pour les grands index, utiliser l'optimisation
        $optimizedIndexes = $this->getOrBuildOptimizedIndexes($wordIndex);

        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            // OPTIMISATION : Skip si ce mot est déjà traité en exact (pour requêtes multi-mots)
            if ($context->hasMultipleWords() && isset($wordIndex[$queryWord])) {
                continue;
            }

            // STRATÉGIE À 3 NIVEAUX (du plus rapide au plus précis) :

            // 1. CHERCHER LES CONTENANTS (très rapide, haute précision)
            $this->findContainedMatchesOptimized(
                $queryWord,
                $optimizedIndexes['byLength'],
                $context
            );

            // 2. CHERCHER PAR TRIGRAMS (optimisé)
            $this->findMatchesByTrigrams(
                $queryWord,
                $optimizedIndexes['trigramIndex'],
                $context
            );

            // 3. CHERCHER PAR PREMIÈRE LETTRE + LONGUEUR (méthode principale optimisée)
            $this->findMatchesByFirstCharAndLengthOptimized(
                $queryWord,
                $optimizedIndexes['byFirstChar'],
                $optimizedIndexes['byLength'],
                $context,
                $context->options->threshold // Threshold normal
            );
        }
    }

    /**
     * Version simple pour petits index (moins de 1000 mots).
     */
    private function discoverFuzzyMatchesSimple(SearchContext $context, array $wordIndex): void
    {
        foreach ($context->getQueryWords() as $queryWord) {
            if (strlen($queryWord) < 2) {
                continue;
            }

            // OPTIMISATION : Skip si ce mot est déjà traité en exact (pour requêtes multi-mots)
            if ($context->hasMultipleWords() && isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex as $indexedWord => $matches) {
                if (strlen((string)$indexedWord) < 2) {
                    continue;
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    (string) $indexedWord
                );

                if ($similarity >= $context->options->threshold) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Construit ou récupère les index optimisés depuis le cache.
     * OPTIMISÉ : Construction unique par index
     */
    private function getOrBuildOptimizedIndexes(array $wordIndex): array
    {
        $cacheKey = md5(serialize(array_keys($wordIndex)));
        $now = time();

        // Vérifier le cache avec expiration
        if (
            isset(self::$cachedOptimizedIndexes[$cacheKey]) &&
            isset(self::$cacheTimestamps[$cacheKey]) &&
            ($now - self::$cacheTimestamps[$cacheKey]) < self::CACHE_TTL
        ) {
            return self::$cachedOptimizedIndexes[$cacheKey];
        }

        // Construire les index optimisés
        $byLength = [];
        $byFirstChar = [];
        $trigramIndex = [];

        foreach ($wordIndex as $word => $matches) {
            // 🔥 CORRECTION DU BUG : Conversion explicite en string
            $word = (string) $word;
            $wordLength = strlen($word);

            if ($wordLength < 2) {
                continue;
            }

            // Index par longueur
            if (!isset($byLength[$wordLength])) {
                $byLength[$wordLength] = [];
            }
            $byLength[$wordLength][$word] = $matches;

            // Index par première lettre
            $firstChar = $word[0];
            if (!isset($byFirstChar[$firstChar])) {
                $byFirstChar[$firstChar] = [];
            }
            $byFirstChar[$firstChar][$word] = $matches;

            // Index par trigrams (3 caractères)
            if ($wordLength >= 3) {
                $trigrams = $this->generateTrigrams($word);
                foreach ($trigrams as $trigram) {
                    if (!isset($trigramIndex[$trigram])) {
                        $trigramIndex[$trigram] = [];
                    }
                    $trigramIndex[$trigram][$word] = $matches;
                }
            }
        }

        $optimizedIndexes = [
            'byLength' => $byLength,
            'byFirstChar' => $byFirstChar,
            'trigramIndex' => $trigramIndex,
        ];

        // Mettre en cache
        self::$cachedOptimizedIndexes[$cacheKey] = $optimizedIndexes;
        self::$cacheTimestamps[$cacheKey] = $now;

        // Nettoyage périodique du cache
        $this->cleanupCache($now);

        return $optimizedIndexes;
    }

    /**
     * Génère les trigrams d'un mot.
     */
    private function generateTrigrams(string $word): array
    {
        $trigrams = [];
        $length = strlen($word);

        if ($length < 3) {
            return [];
        }

        for ($i = 0; $i <= $length - 3; $i++) {
            $trigram = substr($word, $i, 3);
            $trigrams[$trigram] = true;
        }

        return array_keys($trigrams);
    }

    /**
     * Cherche les mots qui CONTIENNENT le mot de requête (optimisé).
     */
    private function findContainedMatchesOptimized(
        string $queryWord,
        array $byLength,
        SearchContext $context
    ): void {
        $queryLength = strlen($queryWord);

        // Seulement chercher dans les mots assez longs pour contenir la requête
        for ($targetLength = $queryLength; $targetLength <= $queryLength + 10; $targetLength++) {
            if (!isset($byLength[$targetLength])) {
                continue;
            }

            // OPTIMISATION : Limiter le nombre de mots à vérifier
            $maxChecks = min(200, count($byLength[$targetLength]));
            $wordsToCheck = array_slice($byLength[$targetLength], 0, $maxChecks, true);

            foreach ($wordsToCheck as $indexedWord => $matches) {
                // 🔥 CORRECTION : Conversion en string pour str_contains()
                $indexedWord = (string) $indexedWord;

                if (str_contains($indexedWord, $queryWord)) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Cherche les matches via trigrams (très efficace).
     */
    private function findMatchesByTrigrams(
        string $queryWord,
        array $trigramIndex,
        SearchContext $context
    ): void {
        $queryTrigrams = $this->generateTrigrams($queryWord);

        if (empty($queryTrigrams)) {
            return;
        }

        $candidates = [];
        $candidateScores = [];

        // 1. Récupérer les candidats via trigrams communs
        foreach ($queryTrigrams as $trigram) {
            if (isset($trigramIndex[$trigram])) {
                foreach ($trigramIndex[$trigram] as $word => $matches) {
                    // 🔥 CORRECTION : Conversion en string
                    $word = (string) $word;
                    $candidateScores[$word] = ($candidateScores[$word] ?? 0) + 1;
                    $candidates[$word] = $matches;
                }
            }
        }

        if (empty($candidates)) {
            return;
        }

        // 2. Trier par score de trigram (meilleurs matchs d'abord)
        arsort($candidateScores);

        // 3. Limiter à un nombre raisonnable de candidats
        $maxCandidates = min(100, count($candidates));
        $topCandidates = array_slice(array_keys($candidateScores), 0, $maxCandidates, true);

        // 4. Calculer la similarité sur les meilleurs candidats
        foreach ($topCandidates as $candidateWord) {
            $similarity = $context->similarityCalculator->calculateWordSimilarity(
                $queryWord,
                $candidateWord
            );

            if ($similarity >= $context->options->threshold) {
                foreach ($candidates[$candidateWord] as $match) {
                    $context->addPotentialMatch($match);
                }
            }
        }
    }

    /**
     * Cherche les matches par première lettre et longueur similaire (OPTIMISÉ).
     * CORRECTION : Commencer par la longueur pour réduire le dataset
     * AJOUT : Support du threshold personnalisé
     */
    private function findMatchesByFirstCharAndLengthOptimized(
        string $queryWord,
        array $byFirstChar,
        array $byLength,
        SearchContext $context,
        ?float $customThreshold = null
    ): void {
        $queryLength = strlen($queryWord);
        $firstChar = $queryWord[0];
        $threshold = $customThreshold ?? $context->options->threshold;

        // OPTIMISATION CRITIQUE : Commencer par la longueur (plus sélectif)
        $lengthsToCheck = $this->getOptimalLengthsToCheck($queryLength);

        $totalChecks = 0;
        $maxChecksPerQuery = 500; // Limite de sécurité

        foreach ($lengthsToCheck as $length) {
            if (!isset($byLength[$length])) {
                continue;
            }

            // Filtrer par première lettre DANS le sous-ensemble de longueur
            foreach ($byLength[$length] as $indexedWord => $matches) {
                // 🔥 CORRECTION : Conversion en string pour accéder au premier caractère
                $indexedWord = (string) $indexedWord;

                // Vérifier la première lettre
                if ($indexedWord[0] !== $firstChar) {
                    continue;
                }

                $totalChecks++;
                if ($totalChecks > $maxChecksPerQuery) {
                    return; // Limite de sécurité atteinte
                }

                $similarity = $context->similarityCalculator->calculateWordSimilarity(
                    $queryWord,
                    $indexedWord
                );

                if ($similarity >= $threshold) {
                    foreach ($matches as $match) {
                        $context->addPotentialMatch($match);
                    }
                }
            }
        }
    }

    /**
     * Détermine les longueurs optimales à checker.
     */
    private function getOptimalLengthsToCheck(int $queryLength): array
    {
        // Stratégie adaptative basée sur la longueur du mot
        if ($queryLength <= 3) {
            // Mots courts : large plage
            return array_filter([
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
                $queryLength + 2,
                $queryLength + 3,
            ], fn($l) => $l >= 2);
        } elseif ($queryLength <= 6) {
            // Mots moyens : plage moyenne
            return array_filter([
                $queryLength - 2,
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
                $queryLength + 2,
            ], fn($l) => $l >= 2);
        } else {
            // Mots longs : plage étroite
            return array_filter([
                $queryLength - 1,
                $queryLength,
                $queryLength + 1,
            ], fn($l) => $l >= 2);
        }
    }

    /**
     * Nettoyage périodique du cache.
     */
    private function cleanupCache(int $currentTime): void
    {
        // Nettoyer une fois toutes les 100 requêtes environ
        if (count(self::$cacheTimestamps) > 20 && rand(1, 100) === 1) {
            foreach (self::$cacheTimestamps as $key => $timestamp) {
                if (($currentTime - $timestamp) > self::CACHE_TTL) {
                    unset(self::$cachedOptimizedIndexes[$key]);
                    unset(self::$cacheTimestamps[$key]);
                }
            }
        }
    }

    /**
     * Découvre les matches multi-mots additionnels.
     */
    private function discoverMultiWordMatches(SearchContext $context): void
    {
        $wordIndex = $context->getWordIndex();
        $queryWords = $context->getQueryWords();

        foreach ($queryWords as $queryWord) {
            // Ignorer les mots courts
            if (strlen($queryWord) < 2) {
                continue;
            }

            if (!isset($wordIndex[$queryWord])) {
                continue;
            }

            foreach ($wordIndex[$queryWord] as $match) {
                $key = $match['indexable_type'] . '_' . $match['indexable_id'];

                // Ignorer si déjà découvert par les autres méthodes
                if ($context->hasPotentialMatches($key)) {
                    continue;
                }

                $context->addPotentialMatch($match);
            }
        }
    }
}
