# Laravel Fuzzy Search - Documentation Complète

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-fuzzy)
[![License](https://img.shields.io/packagist/l/your-vendor/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-fuzzy)

## 📋 Table des matières

1. [Introduction](#-introduction)
2. [Installation rapide](#-installation-rapide)
3. [Préparer vos modèles](#-préparer-vos-modèles)
4. [Indexation des données](#-indexation-des-données)
5. [Recherche](#-recherche)
6. [Formatage des résultats](#-formatage-des-résultats)
7. [Pipeline de recherche](#-pipeline-de-recherche)
8. [Stratégies de scoring](#-stratégies-de-scoring)
9. [Algorithmes de similarité](#-algorithmes-de-similarité)
10. [Système de cache](#-système-de-cache)
11. [Gestion des stop words](#-gestion-des-stop-words)
12. [Optimisation des performances](#-optimisation-des-performances)
13. [Configuration complète](#-configuration-complète)
14. [Commandes Artisan](#-commandes-artisan)
15. [API de référence](#-api-de-référence)
16. [Dépannage](#-dépannage)

---

## 🚀 Introduction

**Laravel Fuzzy Search** est un package de recherche floue avancée pour Laravel qui permet de:

- Rechercher malgré les fautes de frappe ("john doe" → "jhon doe")
- Gérer les recherches multi-mots intelligemment
- Trier les résultats par pertinence réelle
- Indexer automatiquement vos modèles Eloquent
- Personnaliser chaque étape du processus de recherche

**Problème résolu** : Implémentez une recherche performante et tolérante aux erreurs directement dans votre base de données, sans dépendre de services externes coûteux comme Algolia ou Meilisearch.

---

## 📦 Installation rapide

```bash
# 1. Installer via Composer
composer require your-vendor/laravel-fuzzy

# 2. Publier la configuration (optionnel)
php artisan vendor:publish --provider="Fuzzy\FuzzySearchServiceProvider"

# 3. Exécuter les migrations
php artisan migrate

# 4. Indexer vos données
php artisan fuzzy:index
```

---

## 🎯 Préparer vos modèles

### Interface et Trait

Pour qu'un modèle soit recherchable, il doit implémenter `MustFuzzySearch` et utiliser le trait `FuzzySearchable`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // 1. Définir les champs à indexer (REQUIS)
    protected $fillable = ['name', 'description', 'price', 'is_active'];

    // 2. Spécifier quels champs seront recherchables (REQUIS)
    public function getSearchableFields(): array
    {
        return ['name', 'description'];
    }

    // 3. Contrôler quels enregistrements sont indexés (OPTIONNEL)
    public function shouldBeIndexed(): bool
    {
        return $this->is_active === true;
    }

    // 4. Formateur personnalisé (OPTIONNEL)
    public function getFuzzyFormat(): ?string
    {
        return ProductSearchData::class;
    }

    // 5. Champs protégés (préservent les stop words) (OPTIONNEL)
    public function getProtectedFields(): array
    {
        return ['name']; // "Jean de La Fontaine" garde "de" et "la"
    }
}
```

### Méthode `shouldBeIndexed()` - Contrôle d'indexation

Cette méthode détermine quels enregistrements sont inclus dans l'index:

```php
// Exemple 1: Uniquement les produits actifs
public function shouldBeIndexed(): bool
{
    return $this->status === 'active' && $this->stock > 0;
}

// Exemple 2: Uniquement les utilisateurs vérifiés
public function shouldBeIndexed(): bool
{
    return !is_null($this->email_verified_at) && !$this->is_banned;
}

// Exemple 3: Uniquement les articles publiés
public function shouldBeIndexed(): bool
{
    return $this->published_at <= now() && $this->is_public;
}
```

### Champs protégés vs non protégés

| Type de champ | Comportement | Exemple |
|--------------|--------------|---------|
| **Protégé** (`getProtectedFields()`) | Les stop words sont conservés | "Jean de La Fontaine" → "jean de la fontaine" |
| **Non protégé** | Les stop words sont supprimés | "The quick brown fox" → "quick brown fox" |

---

## 🔄 Indexation des données

### Indexation automatique (recommandée)

Le trait `FuzzySearchable` écoute automatiquement les événements du modèle:

```php
// Création - indexé automatiquement
$product = Product::create(['name' => 'Laptop', 'description' => '...']);

// Mise à jour - index mis à jour automatiquement
$product->name = 'Gaming Laptop';
$product->save();

// Suppression - retiré de l'index automatiquement
$product->delete();
```

### Indexation manuelle

```php
use Fuzzy\Contracts\SearchServiceInterface;

class ProductController extends Controller
{
    public function reindex(SearchServiceInterface $search)
    {
        // Indexer un modèle spécifique
        $search->getIndexManager()->indexModel($product);
        
        // Mettre à jour l'index d'un modèle
        $search->getIndexManager()->updateModelIndex($product);
        
        // Supprimer un modèle de l'index
        $search->getIndexManager()->removeModel($product);
        
        // Réindexer tous les modèles d'une classe
        $search->getIndexManager()->reindexModel(Product::class);
        
        // Réindexer TOUS les modèles
        $search->getIndexManager()->reindexAll();
    }
}
```

### Commandes d'indexation

```bash
# Indexer tous les modèles
php artisan fuzzy:index

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Forcer la réindexation (supprime et recrée)
php artisan fuzzy:index --force

# Indexer avec une taille de lot personnalisée
php artisan fuzzy:index --chunk=500

# Afficher les statistiques de l'index
php artisan fuzzy:stats

# Effacer l'index d'un modèle
php artisan fuzzy:clear "App\Models\Product" --force

# Effacer tous les index
php artisan fuzzy:clear --force

# Effacer le cache de recherche
php artisan fuzzy:clear-cache --force
```

---

## 🔍 Recherche

### Facade (recommandée)

```php
use Fuzzy\FuzzySearch;

// Recherche globale (tous modèles)
$results = FuzzySearch::search('laptop gaming');

// Recherche dans un modèle spécifique
$products = FuzzySearch::searchInModel(Product::class, 'wireless mouse');

// Recherche dans plusieurs modèles
$results = FuzzySearch::searchInModels(
    [Product::class, Article::class],
    'nouvelle technologie'
);

// Avec options personnalisées
$results = FuzzySearch::search('iphone', [
    'min_score' => 0.5,      // Score minimum (0.0-1.0)
    'max_results' => 10,     // Max résultats
    'fuzzy' => true,         // Activer recherche floue
    'threshold' => 0.4,      // Seuil de similarité
]);
```

### Via le trait du modèle

```php
// Recherche uniquement dans ce modèle
$products = Product::fuzzySearch('keyboard mechanical', [
    'min_score' => 0.3,
    'max_results' => 20,
]);
```

### Service direct

```php
use Fuzzy\Contracts\SearchServiceInterface;

class SearchController extends Controller
{
    public function search(SearchServiceInterface $search)
    {
        $results = $search->search('keyword');
        
        foreach ($results as $result) {
            echo $result->score;           // Score de pertinence (0-100)
            echo $result->modelType;       // Type du modèle
            echo $result->matchedField;    // Champ qui a matché
            echo $result->matchedValue;    // Valeur qui a matché
            echo $result->relevance;       // Score de similarité détaillé
            
            // Accès au modèle original
            $model = $result->item;
            echo $model->name;
        }
    }
}
```

### Options de recherche

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `min_score` | float | 0.1 | Score minimum pour inclure un résultat |
| `max_results` | int | 20 | Nombre maximum de résultats |
| `fuzzy` | bool | true | Activer/désactiver la recherche floue |
| `threshold` | float | 0.3 | Seuil de similarité pour matchs flous |

---

## 🎨 Formatage des résultats

### Formatage par défaut

Par défaut, chaque résultat contient l'objet original du modèle.

### Formatage personnalisé avec DTO

Créez une classe de formatage qui étend `FuzzySearchableData`:

```php
<?php

namespace App\SearchFormatters;

use Fuzzy\Data\FuzzySearchableData;
use App\Models\Product;

class ProductSearchData extends FuzzySearchableData
{
    // ⚠️ MÉTHODE REQUISE: doit être statique et nommée fromModel
    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            type: 'product',
            model: $product,                    // Modèle original
            data: $product->toArray(),          // Toutes les données
            description: $product->short_description,
            image: $product->getFirstMediaUrl('thumbnail'),
            url: route('products.show', $product)
        );
    }
}
```

### Formatage dynamique selon le contexte

```php
class User extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public function getFuzzyFormat(): ?string
    {
        if ($this->isPremium()) {
            return PremiumUserSearchData::class;
        }
        
        return UserSearchData::class;
    }
}
```

### Formatage inline avec callback

```php
use Fuzzy\Data\SearchResultData;

$result = SearchResultData::withFormatter(
    item: $user,
    score: 0.95,
    modelType: 'User',
    formatter: fn($user) => [
        'id' => $user->id,
        'display_name' => $user->full_name,
        'profile_url' => route('profile', $user)
    ],
    matchedField: 'name',
    matchedValue: 'John Doe'
);
```

---

## 🔧 Pipeline de recherche

Le package utilise un pipeline en 5 étapes pour traiter chaque recherche:

```
Requête brute → Étape 1 → Étape 2 → Étape 3 → Étape 4 → Étape 5 → Résultats
```

### Étapes du pipeline

| Étape | Classe | Priorité | Rôle |
|-------|--------|----------|------|
| 1 | `NormalizeQueryStage` | 90 | Normalise la requête, supprime stop words |
| 2 | `MatchDiscoveryStage` | 75 | Découvre correspondances potentielles |
| 3 | `ScoringStage` | 55 | Calcule scores de pertinence |
| 4 | `RelevanceScoringStage` | 45 | Calcule scores de similarité avancés |
| 5 | `SortAndLimitStage` | 20 | Trie et limite les résultats |

### Ajouter une étape personnalisée

1. **Créer votre stage**:

```php
<?php

namespace App\Stages;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Enums\StageType;
use Closure;

class MyCustomStage implements StageInterface
{
    // Priorité plus haute = exécution plus tôt
    private const PRIORITY = 80;
    
    public function getPriority(): int
    {
        return self::PRIORITY;
    }
    
    public function getType(): StageType
    {
        return StageType::PRE_PROCESSING;
    }
    
    public function handle(SearchContextInterface $context, Closure $next): mixed
    {
        // Votre logique personnalisée ici
        // Accédez à la requête: $context->query
        // Accédez aux options: $context->options
        
        // Exemple: logging
        \Log::info('Search executed', [
            'query' => $context->query->originalQuery
        ]);
        
        // Passer à l'étape suivante
        return $next($context);
    }
}
```

2. **Ajouter à la configuration**:

```php
// config/fuzzy.php
'pipeline' => [
    App\Stages\MyCustomStage::class,
],
```

### Types de stages disponibles

```php
use Fuzzy\Enums\StageType;

StageType::PRE_PROCESSING    // Avant la recherche (normalisation, validation)
StageType::MATCH_DISCOVERY   // Découverte des correspondances
StageType::SCORING           // Calcul des scores
StageType::POST_PROCESSING   // Après le scoring (filtrage, tri)
```

---

## 🎯 Stratégies de scoring

Le système de scoring utilise 4 stratégies organisées par priorité:

| Stratégie | Priorité | Description |
|-----------|----------|-------------|
| `ExactMatchStrategy` | 100 | Correspondance exacte (score = 1.0) |
| `WordMatchStrategy` | 90 | Correspondance mot par mot |
| `MultiWordStrategy` | 80 | Recherche multi-mots |
| `FuzzyMatchStrategy` | 70 | Recherche floue (tolérance erreurs) |

### Comment le score est calculé

```
Score final = Score_base × Poids_champ + Bonus - Pénalités
```

### Bonus disponibles

| Bonus | Valeur | Condition |
|-------|--------|-----------|
| **Position précoce** | +20% | Mot trouvé dans les 20% premiers caractères |
| **Position médiane** | +10% | Mot trouvé dans les 40% premiers caractères |
| **Couverture complète** | +30% | Tous les mots de la requête sont trouvés |
| **Couverture élevée** | +15% | Plus de 75% des mots sont trouvés |
| **Lettres consécutives** | +5% à +50% | Selon la longueur de la séquence |

### Pénalités

| Pénalité | Valeur | Condition |
|----------|--------|-----------|
| **Requête courte** | -40% | Moins de 4 caractères |

---

## 🧮 Algorithmes de similarité

Le package combine 3 algorithmes pour calculer la similarité entre mots:

### 1. Longest Common Substring (LCS)

Trouve la plus longue sous-chaîne commune entre deux mots.

```php
// Exemple: "hello" vs "helo"
// LCS = "hel" (3 caractères)
// Score = 3 / min(5,4) = 0.75
```

### 2. Levenshtein

Mesure la distance d'édition entre deux chaînes.

```php
// Exemple: "kitten" vs "sitting"
// Distance = 3 (k→s, e→i, n→g)
// Similarité = 1 - (3 / max(6,7)) = 0.57
```

### 3. Préfixe

Compare le début des chaînes.

```php
// Exemple: "hello" vs "help"
// Préfixe commun = "hel"
// Score = 0.4 + (3/5 × 0.3) = 0.58
```

### Score composite

```
Score_final = (LCS_score × 0.4) + (Levenshtein_score × 0.3) + (Prefix_score × 0.2)
```

### Similarité phonétique

Le package utilise également l'algorithme **Soundex** pour détecter les mots qui sonnent similaire:

```php
// "Catherine" et "Katherine" → même code Soundex
// Score de similarité plus élevé automatiquement
```

---

## 💾 Système de cache

### Types de cache et TTL

| Type de cache | TTL par défaut | Description |
|---------------|----------------|-------------|
| `search` | 3600s (1h) | Recherches globales |
| `search_in_model` | 3600s (1h) | Recherches dans un modèle |
| `search_in_models` | 3600s (1h) | Recherches multi-modèles |
| `stats` | 30s | Statistiques de l'index |

### Invalidation automatique

Le cache est automatiquement invalidé lors de:
- **Indexation** (`on_index` = true)
- **Mise à jour** (`on_update` = true)
- **Suppression** (`on_delete` = true)

### Gestion manuelle du cache

```php
use Fuzzy\Contracts\SearchServiceInterface;

$cache = $search->getCacheManager();

// Invalider tout le cache
$cache->invalidateAll();

// Invalider le cache d'un modèle
$cache->invalidateForModel(Product::class);

// Invalider uniquement les statistiques
$cache->invalidateStatsCache();

// Vérifier si le cache est activé
if ($cache->isEnabled()) {
    // ...
}
```

### Commandes de cache

```bash
# Effacer tout le cache
php artisan fuzzy:clear-cache --force

# Effacer le cache d'un modèle spécifique
php artisan fuzzy:clear-cache --model="App\Models\Product" --force

# Effacer uniquement le cache des stats
php artisan fuzzy:clear-cache --stats --force
```

---

## 🛑 Gestion des stop words

Les stop words sont des mots courants ignorés dans les recherches (le, la, and, the, etc.).

### Stop words intégrés

| Langue | Stop words |
|--------|------------|
| **Français** | le, la, les, un, une, et, ou, mais, dans, avec, etc. |
| **Anglais** | the, a, an, and, or, but, for, in, on, at, etc. |

### Détection automatique

La locale est automatiquement détectée depuis Laravel:

```php
// config/app.php
'locale' => 'fr',  // Stop words français
'locale' => 'en',  // Stop words anglais
```

### Comportement selon la longueur de requête

| Requête | Comportement |
|---------|--------------|
| ≤ 3 mots | Les stop words sont **conservés** |
| ≥ 4 mots | Les stop words sont **supprimés** |

```php
// Requête courte → stop words conservés
// "the cat" → "the cat"

// Requête longue → stop words supprimés
// "the quick brown fox" → "quick brown fox"
```

### Champs protégés

Pour les champs comme `name` ou `email`, les stop words sont TOUJOURS conservés:

```php
public function getProtectedFields(): array
{
    return ['name', 'email'];  // "Jean de La Fontaine" → "jean de la fontaine"
}
```

---

## ⚡ Optimisation des performances

### 1. Optimisation de l'index

```php
// config/fuzzy.php
'index' => [
    'batch_size' => 1000,       // Lots plus grands pour les gros volumes
    'min_word_length' => 3,     // Ignorer mots très courts
    'queue' => true,            // Indexation en file d'attente
],
```

### 2. Pré-chargement des relations

```php
// config/fuzzy.php
'eager_load' => [
    App\Models\Product::class => ['category', 'brand', 'images'],
    App\Models\User::class => ['profile', 'roles'],
],
```

### 3. Cache agressif

```php
'cache' => [
    'enabled' => true,
    'ttl' => [
        'search' => 86400,      // 24h pour les recherches fréquentes
    ],
],
```

### 4. Limitation des résultats

```php
// Toujours limiter le nombre de résultats
$results = FuzzySearch::search('query', [
    'max_results' => 20,  // Ne jamais mettre 0 ou null
]);
```

### 5. Indexation par lots

```bash
# Production: lots de 500-1000
php artisan fuzzy:index --chunk=1000

# Dev: lots plus petits
php artisan fuzzy:index --chunk=50
```

---

## ⚙️ Configuration complète

Voici le fichier de configuration complet `config/fuzzy.php` avec toutes les options disponibles:

<details>
<summary>📄 Cliquez pour voir la configuration complète</summary>

```php
<?php

return [
    // Cache Configuration
    'cache' => [
        'enabled' => env('FUZZY_SEARCH_CACHE_ENABLED', true),
        'prefix' => 'fuzzy_search:',
        'ttl' => [
            'search' => 3600,
            'search_in_model' => 3600,
            'search_in_models' => 3600,
            'stats' => 30,
        ],
        'invalidation' => [
            'on_index' => true,
            'on_update' => true,
            'on_delete' => true,
        ],
    ],

    // Default Search Options
    'default_options' => [
        'min_score' => 0.1,
        'max_results' => 20,
        'fuzzy' => true,
        'threshold' => 0.1,
    ],

    // Index Configuration
    'index' => [
        'min_word_length' => 2,
        'max_word_length' => 50,
        'batch_size' => 100,
        'queue' => env('FUZZY_SEARCH_QUEUE', false),
        'queue_name' => env('FUZZY_SEARCH_QUEUE_NAME', 'default'),
    ],

    // Custom Pipeline Stages
    'pipeline' => [],

    // LCS Algorithm
    'lcs' => [
        'base_index' => 0,
        'match_increment' => 1,
        'weight' => null,
    ],

    // Levenshtein Algorithm
    'levenshtein' => [
        'empty_string_length' => 0,
        'distance_penalty_threshold' => 2,
        'penalty_factor_base' => 0.7,
        'penalty_reduction_per_distance' => 0.1,
        'close_match_bonus_threshold' => 2,
        'min_length_for_bonus' => 4,
        'close_match_bonus' => 0.1,
        'weight' => null,
    ],

    // Prefix Algorithm
    'prefix' => [
        'min_prefix_length' => 3,
        'prefix_base_score' => 0.4,
        'prefix_variable_multiplier' => 0.3,
        'prefix_max_score' => 0.6,
        'weight' => null,
    ],

    // Advanced Scoring
    'scoring' => [
        'field_weights' => [
            'name' => 1.3,
            'title' => 1.2,
            'email' => 1.0,
            'description' => 0.8,
            'content' => 0.7,
            'default' => 0.6,
        ],
        'consecutive_bonus' => [
            2 => 1.05,
            3 => 1.10,
            4 => 1.35,
            5 => 1.50,
        ],
        'penalties' => ['short_query' => 0.4],
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

    // Match Discovery
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

    // Base Similarity
    'similarity' => [
        'min_query_length' => 2,
        'algorithm_weights' => [
            'longest_common_substring' => 0.4,
            'levenshtein' => 0.3,
            'prefix' => 0.2,
        ],
        'coverage_bonus_threshold' => 0.5,
        'coverage_bonus_multiplier' => 0.15,
        'low_coverage_multiplier' => 1.5,
        'containment_high_ratio' => 0.8,
        'containment_query_in_target_high_score' => 0.95,
        'containment_target_in_query_high_score' => 0.9,
        'containment_base_score_query_in_target' => 0.75,
        'containment_base_score_target_in_query' => 0.65,
        'containment_multiplier_query_in_target' => 0.2,
        'containment_multiplier_target_in_query' => 0.25,
        'containment_max_score_query_in_target' => 0.9,
        'containment_max_score_target_in_query' => 0.85,
        'containment_ratio_high' => 0.8,
        'containment_ratio_medium' => 0.5,
        'containment_high_multiplier' => 1.8,
        'containment_medium_multiplier' => 2.5,
        'unmatched_letter_penalty' => 0.15,
        'max_score_cap' => 7.0,
        'word_penalty_per_char' => 0.04,
        'length_penalty_multiplier' => 0.04,
        'minimal_penalty' => 0.3,
        'match_fuzziness_penalty' => 0.1,
        'min_word_match_ratio' => 0.8,
        'short_word_threshold' => 3,
        'very_bad_match_threshold' => 4.0,
        'very_bad_match_penalty' => 0.8,
        'strictness_factor_per_word' => 0.05,
        'real_similarity_threshold' => 0.35,
        'real_similarity_base_penalty' => 1.5,
        'real_similarity_multiplier' => 1.5,
        'low_similarity_threshold' => 0.3,
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
        'unmatched_letter_multiplier' => 1.5,
    ],

    // Word Similarity
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
        'unmatched_letter_penalty' => 0.15,
        'unmatched_letter_multiplier' => 1.5,
        'word_penalty_per_char' => 0.04,
        'length_penalty_multiplier' => 0.04,
        'minimal_penalty' => 0.3,
        'match_fuzziness_penalty' => 0.1,
        'min_word_match_ratio' => 0.8,
        'short_word_threshold' => 3,
        'very_bad_match_threshold' => 4.0,
        'very_bad_match_penalty' => 0.8,
        'strictness_factor_per_word' => 0.05,
        'real_similarity_threshold' => 0.35,
        'real_similarity_base_penalty' => 1.5,
        'real_similarity_multiplier' => 1.5,
        'low_similarity_threshold' => 0.3,
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

    // Relevance Scoring
    'relevance_scoring' => [
        'penalty' => 10.0,
        'default_max_results' => 20,
        'original_score_weight' => 0.7,
        'relevance_score_weight' => 0.3,
        'max_normalized_relevance' => 100.0,
        'min_normalized_relevance' => 0.0,
        'normalization_factor' => 10.0,
    ],
];
```
</details>

---

## ⌨️ Commandes Artisan

### Indexation

```bash
# Indexer tous les modèles
php artisan fuzzy:index

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Forcer la réindexation
php artisan fuzzy:index --force

# Taille de lot personnalisée
php artisan fuzzy:index --chunk=500

# Lister les modèles sans indexer
php artisan fuzzy:index --list
```

### Nettoyage

```bash
# Effacer l'index d'un modèle
php artisan fuzzy:clear "App\Models\Product" --force

# Effacer tous les index
php artisan fuzzy:clear --force
```

### Cache

```bash
# Effacer tout le cache
php artisan fuzzy:clear-cache --force

# Effacer cache d'un modèle
php artisan fuzzy:clear-cache --model="App\Models\Product" --force

# Effacer cache des stats seulement
php artisan fuzzy:clear-cache --stats --force
```

### Statistiques

```bash
# Afficher les statistiques de l'index
php artisan fuzzy:stats
```

---

## 📚 API de référence

### Interfaces principales

| Interface | Rôle |
|-----------|------|
| `MustFuzzySearch` | Interface que vos modèles doivent implémenter |
| `SearchServiceInterface` | Service principal de recherche |
| `CacheManagerInterface` | Gestion du cache |
| `IndexManagerInterface` | Gestion de l'indexation |
| `SearchProcessorInterface` | Traitement des recherches |
| `StageInterface` | Interface pour les stages du pipeline |

### Classes principales

| Classe | Rôle |
|--------|------|
| `FuzzySearch` | Facade pour accès statique |
| `SearchResultData` | Objet de résultat standardisé |
| `FuzzySearchableData` | Base pour formatage personnalisé |
| `SearchContext` | Contexte de recherche (pipeline) |
| `StringNormalizer` | Normalisation des chaînes |

### Enums

```php
use Fuzzy\Enums\StageType;

StageType::PRE_PROCESSING     // Prétraitement
StageType::MATCH_DISCOVERY    // Découverte correspondances
StageType::SCORING            // Scoring
StageType::POST_PROCESSING    // Post-traitement
```

### Constantes globales

```php
FUZZY_SCORE_IDENTICAL    // 1.0 - Score parfait
FUZZY_SCORE_NONE         // 0.0 - Aucun score
FUZZY_BASE_FACTOR        // 1.0 - Facteur de base
FUZZY_DISTANCE_IDENTICAL // 0.0 - Distance identique
```

---

## 🐛 Dépannage

### Problème: Les résultats ne sont pas trouvés

**Causes possibles:**
1. Index non créé
2. `shouldBeIndexed()` retourne false
3. Stop words supprimés malencontreusement

**Solutions:**
```bash
# Vérifier l'index
php artisan fuzzy:stats

# Réindexer
php artisan fuzzy:index --force

# Vérifier shouldBeIndexed()
php artisan tinker
>>> $product = Product::first();
>>> $product->shouldBeIndexed();
```

### Problème: Performance lente

**Solutions:**
```php
// 1. Augmenter la taille des lots
'index' => ['batch_size' => 1000],

// 2. Activer le cache
'cache' => ['enabled' => true],

// 3. Réduire max_results
'default_options' => ['max_results' => 20],

// 4. Pré-charger les relations
'eager_load' => [Product::class => ['category']],
```

### Problème: Stop words non supprimés

**Solution:**
```php
// Vérifier que le champ n'est pas protégé
public function getProtectedFields(): array
{
    return []; // Vide pour supprimer stop words
}
```

### Problème: Score trop bas/haut

**Solutions:**
```php
// Ajuster les seuils
'default_options' => [
    'min_score' => 0.3,     // Plus élevé = moins de résultats
    'threshold' => 0.5,     // Plus élevé = correspondances plus précises
],

// Ajuster les poids des champs
'scoring' => [
    'field_weights' => [
        'name' => 2.0,       // Augmenter l'importance
        'default' => 0.5,    // Réduire l'importance par défaut
    ],
],
```

---

## 🤝 Contribution

### Workflow de contribution

```bash
# 1. Fork le projet
# 2. Cloner votre fork
git clone https://github.com/votre-nom/laravel-fuzzy.git

# 3. Créer une branche
git checkout -b feature/ma-fonctionnalite

# 4. Installer les dépendances
composer install

# 5. Lancer les tests
composer test

# 6. Pousser les changements
git push origin feature/ma-fonctionnalite

# 7. Ouvrir une Pull Request
```

### Standards de code

- **PSR-12** pour le codage
- **PHPStan** niveau 6
- **PHPUnit** avec couverture >80%
- **Conventional Commits**

---

## 📄 Licence

MIT License - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

**Fait avec ❤️ pour la communauté Laravel**