# Laravel Fuzzy Search - Documentation Complète

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-fuzzy)
[![License](https://img.shields.io/packagist/l/your-vendor/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-fuzzy)

## 📋 Table des matières

1. [Introduction](#-introduction)
2. [Installation rapide](#-installation-rapide)
3. [Préparer vos modèles](#-préparer-vos-modèles)
   - [Interface et Trait](#interface-et-trait)
   - [Configuration de l'indexation automatique (IndexationLevel)](#configuration-de-lindexation-automatique-indexationlevel)
   - [Méthode `shouldBeIndexed()` - Contrôle d'indexation](#méthode-shouldbeindexed---contrôle-dindexation)
   - [Différence entre IndexationLevel et shouldBeIndexed](#différence-entre-indexationlevel-et-shouldbeindexed)
   - [Champs protégés](#champs-protégés-vs-non-protégés)
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
- **Contrôler finement l'indexation automatique** avec `IndexationLevel`

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
use Fuzzy\Enums\IndexationLevel;

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

    // 3. Contrôler quels enregistrements sont indexables (RECOMMANDÉ)
    public function shouldBeIndexed(): bool
    {
        return $this->is_active === true;
    }

    // 4. Configurer quels événements déclenchent l'indexation auto (OPTIONNEL)
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::CREATE_AND_UPDATE; // Pas d'indexation sur suppression
    }

    // 5. Formateur personnalisé (OPTIONNEL)
    public function getFuzzyFormat(): ?string
    {
        return ProductSearchData::class;
    }

    // 6. Champs protégés (préservent les stop words) (OPTIONNEL)
    public function getProtectedFields(): array
    {
        return ['name']; // "Jean de La Fontaine" garde "de" et "la"
    }
}
```

### Configuration de l'indexation automatique (IndexationLevel)

L'enum `IndexationLevel` permet de contrôler précisément quels événements du modèle déclenchent une indexation automatique:

```php
use Fuzzy\Enums\IndexationLevel;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // Seulement lors de la création
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::CREATE_ONLY;
    }

    // Seulement lors de la mise à jour
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::UPDATE_ONLY;
    }

    // Seulement lors de la suppression
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::DELETE_ONLY;
    }

    // Création et mise à jour uniquement (pas de suppression)
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::CREATE_AND_UPDATE;
    }

    // Aucune indexation automatique (indexation manuelle uniquement)
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::NONE;
    }

    // Tous les événements (comportement par défaut)
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::ALL;
    }
}
```

#### Valeurs disponibles de l'enum IndexationLevel

| Valeur | Événements déclenchés | Cas d'usage |
|--------|----------------------|-------------|
| `NONE` | Aucun | Indexation manuelle uniquement (via commande) |
| `CREATE_ONLY` | création seulement | Logs d'audit, données immuables |
| `UPDATE_ONLY` | mise à jour seulement | Données de référence mises à jour périodiquement |
| `DELETE_ONLY` | suppression seulement | Nettoyage d'index |
| `CREATE_AND_UPDATE` | création + mise à jour | Contenu éditable (suppression non indexée) |
| `CREATE_AND_DELETE` | création + suppression | Données temporaires |
| `UPDATE_AND_DELETE` | mise à jour + suppression | Données préexistantes modifiables |
| `ALL` | tous (défaut) | Cas standard |

### Méthode `shouldBeIndexed()` - Contrôle d'indexation

Cette méthode détermine quels **enregistrements** sont inclus dans l'index, indépendamment des événements déclenchés:

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

// Exemple 4: Jamais indexé (utile pour tests ou modèles abstraits)
public function shouldBeIndexed(): bool
{
    return false;
}
```

### Différence entre IndexationLevel et shouldBeIndexed

Ces deux mécanismes sont **complémentaires** et ont des responsabilités distinctes:

| Mécanisme | Responsabilité | Contrôle | Portée |
|-----------|----------------|----------|--------|
| **`IndexationLevel`** | **QUAND** indexer automatiquement | Les événements déclencheurs | Au niveau de la classe |
| **`shouldBeIndexed()`** | **SI** le modèle peut être indexé | L'éligibilité individuelle | Au niveau de l'enregistrement |

#### Schéma de fonctionnement

```
Événement du modèle (create/update/delete)
    ↓
Vérification: L'événement est-il dans IndexationLevel ?
    ↓ OUI
Vérification: shouldBeIndexed() retourne-t-il true ?
    ↓ OUI
Indexation effectuée
```

#### Exemples concrets

```php
class Article extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // Configuration: S'indexer sur create et update uniquement
    public static function getIndexationLevel(): IndexationLevel
    {
        return IndexationLevel::CREATE_AND_UPDATE;
    }

    // Condition: Seulement les articles publiés sont indexables
    public function shouldBeIndexed(): bool
    {
        return $this->status === 'published';
    }
}

// Scénario 1: Article non publié
$article = Article::create(['title' => 'Brouillon', 'status' => 'draft']);
// → shouldBeIndexed() = false → AUCUNE indexation, même si CREATE est autorisé

// Scénario 2: Article publié
$article = Article::create(['title' => 'Publié', 'status' => 'published']);
// → shouldBeIndexed() = true → Indexation effectuée (CREATE autorisé)

// Scénario 3: Article publié devient brouillon
$article->status = 'draft';
$article->save();
// → shouldBeIndexed() = false → L'index est SUPPRIMÉ (même si UPDATE_ONLY)
```

#### Cas particuliers et subtilités

**1. Indexation manuelle (commande `fuzzy:index` ou `indexModel()`)**

Les commandes manuelles **ignorent** `IndexationLevel` mais **respectent** `shouldBeIndexed()`:

```php
// Même avec IndexationLevel::NONE
public static function getIndexationLevel(): IndexationLevel
{
    return IndexationLevel::NONE; // Pas d'indexation auto
}

public function shouldBeIndexed(): bool
{
    return true; // Indexable
}

// La commande manuelle fonctionne quand même:
php artisan fuzzy:index --force
// → Le modèle sera indexé car la commande appelle indexModel() directement
```

**2. Événement de suppression**

L'événement `deleted` **ne vérifie PAS** `shouldBeIndexed()`:

```php
public function shouldBeIndexed(): bool
{
    return false; // Jamais indexé normalement
}

// Si le modèle a été indexé manuellement avant
$product->delete();
// → L'index sera SUPPRIMÉ même si shouldBeIndexed() = false
// C'est intentionnel: on nettoie toujours l'index lors de la suppression
```

**3. Priorité absolue de `shouldBeIndexed()`**

La méthode `shouldBeIndexed()` a toujours la **priorité la plus haute**:

```php
// Même avec IndexationLevel::ALL
public static function getIndexationLevel(): IndexationLevel
{
    return IndexationLevel::ALL;
}

public function shouldBeIndexed(): bool
{
    return false; // Désactive TOUTE indexation
}

// Aucune indexation automatique ne se produira
// Les commandes manuelles seront aussi bloquées
```

### Champs protégés vs non protégés

| Type de champ | Comportement | Exemple |
|--------------|--------------|---------|
| **Protégé** (`getProtectedFields()`) | Les stop words sont conservés | "Jean de La Fontaine" → "jean de la fontaine" |
| **Non protégé** | Les stop words sont supprimés | "The quick brown fox" → "quick brown fox" |

---

## 🔄 Indexation des données

### Indexation automatique

Le trait `FuzzySearchable` écoute automatiquement les événements configurés via `IndexationLevel`:

```php
// Si IndexationLevel::ALL (défaut)
$product = Product::create(['name' => 'Laptop']);  // ✅ Indexé
$product->name = 'Gaming Laptop'; $product->save(); // ✅ Mis à jour
$product->delete();                                 // ✅ Supprimé

// Si IndexationLevel::CREATE_ONLY
$product = Product::create(['name' => 'Laptop']);  // ✅ Indexé
$product->name = 'Gaming Laptop'; $product->save(); // ❌ Pas de mise à jour
$product->delete();                                 // ❌ Pas de suppression

// Si IndexationLevel::NONE
$product = Product::create(['name' => 'Laptop']);  // ❌ Pas indexé
// Indexation manuelle uniquement via commande
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
# Indexation incrémentale (par défaut) - uniquement nouveaux/modifiés
php artisan fuzzy:index

# Indexation forcée (supprime puis recrée tout)
php artisan fuzzy:index --force

# Indexer un modèle spécifique avec force
php artisan fuzzy:index "App\Models\Product" --force

# Indexer avec une taille de lot personnalisée
php artisan fuzzy:index --chunk=500

# Lister les modèles découvrables (sans indexer)
php artisan fuzzy:index --list

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

```php
<?php

namespace App\Stages;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Enums\StageType;
use Closure;

class MyCustomStage implements StageInterface
{
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
        \Log::info('Search executed', [
            'query' => $context->query->originalQuery
        ]);
        
        return $next($context);
    }
}
```

Ajoutez à la configuration `config/fuzzy.php`:

```php
'pipeline' => [
    App\Stages\MyCustomStage::class,
],
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

---

## 💾 Système de cache

Le package utilise une abstraction `CacheStoreInterface` pour le cache, permettant de changer facilement de système de cache (Laravel cache, Redis, Memcached, etc.) sans modifier le code métier.

### Configuration du cache

```php
// config/fuzzy.php
'cache' => [
    'enabled' => env('FUZZY_SEARCH_CACHE_ENABLED', true),
    'driver' => env('FUZZY_SEARCH_CACHE_DRIVER', 'laravel'),
    'prefix' => 'fuzzy_search:',
    'ttl' => [
        'search' => 3600,           // Recherches globales: 1 heure
        'search_in_model' => 3600,  // Recherches par modèle: 1 heure
        'search_in_models' => 3600, // Recherches multi-modèles: 1 heure
        'stats' => 30,              // Statistiques: 30 secondes
    ],
    'invalidation' => [
        'on_index' => true,   // Invalider cache lors de l'indexation
        'on_update' => true,  // Invalider cache lors des mises à jour
        'on_delete' => true,  // Invalider cache lors des suppressions
    ],
],
```

### Types de cache et TTL

| Type de cache | TTL par défaut | Description |
|---------------|----------------|-------------|
| `search` | 3600s (1h) | Recherches globales |
| `search_in_model` | 3600s (1h) | Recherches dans un modèle |
| `search_in_models` | 3600s (1h) | Recherches multi-modèles |
| `stats` | 30s | Statistiques de l'index |

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

Le fichier de configuration `config/fuzzy.php` contient toutes les options disponibles. Publiez-le avec:

```bash
php artisan vendor:publish --provider="Fuzzy\FuzzySearchServiceProvider"
```

<details>
<summary>📄 Voir la configuration complète</summary>

```php
<?php

return [
    // Cache Configuration
    'cache' => [
        'enabled' => env('FUZZY_SEARCH_CACHE_ENABLED', true),
        'driver' => env('FUZZY_SEARCH_CACHE_DRIVER', 'laravel'),
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
# Indexation incrémentale (défaut) - uniquement nouveaux/modifiés
php artisan fuzzy:index

# Indexation forcée (supprime puis recrée tout)
php artisan fuzzy:index --force

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Indexer un modèle spécifique avec force
php artisan fuzzy:index "App\Models\Product" --force

# Taille de lot personnalisée
php artisan fuzzy:index --chunk=500

# Lister les modèles découvrables (sans indexer)
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
| `CacheStoreInterface` | Abstraction du système de cache |
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
| `LaravelCacheStore` | Implémentation Laravel du cache |

### Enums

```php
use Fuzzy\Enums\IndexationLevel;
use Fuzzy\Enums\StageType;

// Contrôle des événements d'indexation
IndexationLevel::NONE              // Aucun événement
IndexationLevel::CREATE_ONLY       // Création uniquement
IndexationLevel::UPDATE_ONLY       // Mise à jour uniquement
IndexationLevel::DELETE_ONLY       // Suppression uniquement
IndexationLevel::CREATE_AND_UPDATE // Création + mise à jour
IndexationLevel::CREATE_AND_DELETE // Création + suppression
IndexationLevel::UPDATE_AND_DELETE // Mise à jour + suppression
IndexationLevel::ALL               // Tous les événements (défaut)

// Types de stages du pipeline
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
3. `IndexationLevel` configuré pour ne pas indexer l'événement concerné
4. Stop words supprimés malencontreusement

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

# Vérifier IndexationLevel
>>> $product::getIndexationLevel();
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

### Problème: Indexation automatique ne fonctionne pas

**Vérifications:**
```php
// 1. Vérifier IndexationLevel
public static function getIndexationLevel(): IndexationLevel
{
    // Doit inclure l'événement concerné
    return IndexationLevel::CREATE_AND_UPDATE;
}

// 2. Vérifier shouldBeIndexed()
public function shouldBeIndexed(): bool
{
    return true; // Doit retourner true
}
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