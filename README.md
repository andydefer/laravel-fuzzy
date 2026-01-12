Voici votre fichier README.md corrigé et complet :

# Laravel Fuzzy Search

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andydefer/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/andydefer/laravel-fuzzy)
[![License](https://img.shields.io/packagist/l/andydefer/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/andydefer/laravel-fuzzy)

Un package Laravel puissant de recherche floue (fuzzy search) avec indexation basée sur la base de données et système de scoring avancé. Supporte les fautes de frappe, les recherches approximatives et les recherches multi-mots avec performances optimisées.

**Problème résolu** : Implémente une recherche floue performante directement dans votre base de données Laravel sans dépendre de services externes coûteux comme Algolia ou Meilisearch. Gère automatiquement les fautes de frappe, les variations orthographiques et optimise les performances pour les grandes bases de données.

## 📦 Installation

### Prérequis
- PHP ^8.2
- Laravel ^11.0
- MySQL 5.7+ ou PostgreSQL 9.6+

### Installation via Composer
```bash
composer require andydefer/laravel-fuzzy
```

### Publier les fichiers de configuration et migrations
```bash
php artisan vendor:publish --provider="Fuzzy\FuzzySearchServiceProvider"
```

### Exécuter les migrations
```bash
php artisan migrate
```

## ⚙️ Configuration

Après avoir publié la configuration, éditez `config/fuzzy.php` :

```php
return [
    // Auto-découverte des modèles dans app/Models/
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

    // Modèles explicitement configurés
    'searchable_models' => [
        App\Models\User::class,
        App\Models\Product::class,
        // Ajoutez vos modèles ici
    ],

    // Configuration du cache (optimisation performances)
    'cache' => [
        'enabled' => true,
        'prefix' => 'fuzzy_search:',
        'ttl' => [
            'search' => 3600, // 1 heure
            'search_in_model' => 3600,
            'search_in_models' => 3600,
            'stats' => 30,    // 30 secondes
        ],
        'invalidation' => [
            'on_index' => true,
            'on_update' => true,
            'on_delete' => true,
        ],
    ],

    // Options de recherche par défaut
    'default_options' => [
        'min_score' => 0.1,    // Score minimum pour inclure un résultat
        'max_results' => 20,   // Limite de résultats
        'fuzzy' => true,       // Activer la recherche floue
        'threshold' => 0.3,    // Seuil de similarité
    ],

    // Configuration du pipeline de recherche (NON MODIFIABLE)
    'pipeline' => [
        'stages' => [
            \Fuzzy\Stages\NormalizeQueryStage::class,
            \Fuzzy\Stages\MatchDiscoveryStage::class,
            \Fuzzy\Stages\ScoringStage::class,
            \Fuzzy\Stages\SortAndLimitStage::class,
        ],
    ],

    // Système de scoring avancé
    'scoring' => [
        'field_weights' => [
            'name' => 1.3,        // 30% de bonus pour le champ 'name'
            'title' => 1.2,       // 20% de bonus
            'email' => 1.0,
            'description' => 0.8, // 20% de pénalité
            'content' => 0.7,
            'default' => 0.6,
        ],
        'bonuses' => [
            'full_coverage' => 0.3,     // Bonus si tous les mots sont trouvés
            'high_coverage' => 0.15,    // Bonus si >75% des mots trouvés
            'early_position' => 0.2,    // Bonus si mot au début du champ
        ],
        'penalties' => [
            'short_query' => 0.4,       // Pénalité pour requêtes courtes (<4 chars)
            'cross_word_match_multi' => 0.3,
        ],
        'consecutive_bonus' => [        // NON MODIFIABLE - Configuration protégée
            2 => 1.05,
            3 => 1.15,
            4 => 1.30,
            5 => 1.50,
        ],
    ],

    // Pré-chargement des relations pour éviter les requêtes N+1
    'eager_load' => [
        // Exemple:
        // App\Models\User::class => ['profile', 'roles'],
        // App\Models\Product::class => ['category', 'brand'],
    ],

    // Mots ignorés dans les requêtes longues (>3 mots)
    'stop_words' => [
        'the', 'and', 'or', 'a', 'an', 'in', 'on', 'at', 'to',
        // Ajoutez vos propres stop words
    ],

    // Configuration de l'indexation
    'index' => [
        'min_word_length' => 2,
        'max_word_length' => 50,
        'batch_size' => 100,
        'queue' => env('FUZZY_SEARCH_QUEUE', false),
        'queue_name' => env('FUZZY_SEARCH_QUEUE_NAME', 'default'),
    ],

    // Configuration des algorithmes de similarité
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
];
```

## 🚀 Utilisation Rapide

### 1. Préparer votre modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    protected $fillable = ['name', 'description', 'price', 'status'];

    // ✅ REQUIS: Définir les champs à indexer
    public array $searchableFields = ['name', 'description'];

    // ✅ OPTIONNEL: Formatage personnalisé des résultats via propriété
    public ?string $fuzzyFormat = ProductSearchData::class;

    // ✅ OPTIONNEL: Formatage dynamique via méthode
    public function getFuzzyFormat(): ?string
    {
        // Exemple: format différent selon le type de produit
        if ($this->is_premium) {
            return PremiumProductSearchData::class;
        }

        return ProductSearchData::class;
    }

    // ✅ OPTIONNEL: Contrôle d'indexation dynamique
    public function shouldBeIndexed(): bool
    {
        // Exemple: indexer seulement les produits actifs et en stock
        return $this->status === 'active' && $this->stock > 0;
    }

    // ✅ REQUIS: Retourne l'ID unique pour l'indexation
    public function getIndexableId(): string|int
    {
        return $this->getKey();
    }

    // ✅ REQUIS: Retourne les attributs du modèle
    public function getAttribute($key)
    {
        return parent::getAttribute($key);
    }
}
```

### 2. Créer une classe de formatage personnalisé (optionnel)

```php
<?php

namespace App\SearchFormatters;

use Fuzzy\Data\FuzzySearchableData;
use App\Models\Product;

class ProductSearchData extends FuzzySearchableData
{
    // ⚠️ MÉTHODE REQUISE: Doit être statique et nommée fromModel
    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            type: 'product',
            model: $product,
            data: $product->toArray(),
            description: $product->short_description,
            image: optional($product->getFirstMedia('thumbnail'))->getUrl(),
            url: route('products.show', $product)
        );
    }
}
```

### 3. Indexer vos données

```bash
# Afficher les modèles disponibles sans indexer
php artisan fuzzy:index --list

# Indexer tous les modèles configurés
php artisan fuzzy:index

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Forcer la réindexation complète (supprime et recrée l'index)
php artisan fuzzy:index --force

# Indexer par lots pour les grandes tables
php artisan fuzzy:index --chunk=500

# Indexer avec auto-découverte seulement
php artisan fuzzy:index --auto
```

### 4. Recherche de base

```php
use Fuzzy\FuzzySearch;
use App\Models\Product;

// Recherche globale (tous les modèles indexés)
$results = FuzzySearch::search('laptop gaming');

// Recherche dans un modèle spécifique
$products = FuzzySearch::searchInModel(Product::class, 'wireless mouse');

// Recherche dans plusieurs modèles
$results = FuzzySearch::searchInModels(
    [Product::class, App\Models\Article::class],
    'nouvelle technologie'
);

// Via le trait dans vos modèles
$products = Product::fuzzySearch('keyboard mechanical');
```

## 🔧 Utilisation Avancée

### Contrôle d'indexation dynamique avec `shouldBeIndexed()`

La méthode `shouldBeIndexed()` vous permet de contrôler précisément quels enregistrements sont inclus dans l'index de recherche :

```php
// Exemple 1: Articles publiés seulement
class Article extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public function shouldBeIndexed(): bool
    {
        return $this->status === 'published'
            && $this->published_at <= now()
            && !$this->is_draft;
    }
}

// Exemple 2: Utilisateurs avec email vérifié
class User extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public function shouldBeIndexed(): bool
    {
        return $this->email_verified_at !== null
            && $this->is_active
            && !$this->is_banned;
    }
}

// Exemple 3: Produits avec relations
class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public function shouldBeIndexed(): bool
    {
        return $this->is_active
            && $this->category->is_published // Relation
            && $this->brand->is_verified     // Relation
            && $this->stock > 0;
    }
}
```

**Important** : Après avoir modifié `shouldBeIndexed()`, vous devez réindexer :
```bash
php artisan fuzzy:index --force
```

### Formatage dynamique avec `getFuzzyFormat()`

Pour un contrôle encore plus précis, vous pouvez utiliser la méthode `getFuzzyFormat()` :

```php
class User extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public function getFuzzyFormat(): ?string
    {
        // Format différent selon le type d'utilisateur
        if ($this->is_premium) {
            return PremiumUserSearchData::class;
        }

        if ($this->is_admin) {
            return AdminUserSearchData::class;
        }

        return UserSearchData::class;
    }
}
```

**Note** : Vous pouvez aussi utiliser la propriété `$fuzzyFormat` directement :

```php
class User extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public ?string $fuzzyFormat = UserSearchData::class;
}
```

### Options de recherche avancées

```php
use Fuzzy\FuzzySearch;

// Recherche avec options personnalisées
$results = FuzzySearch::search('iphone', [
    'min_score' => 0.3,      // Score minimum (0.0 - 1.0)
    'max_results' => 10,     // Limite de résultats
    'fuzzy' => true,         // Activer/désactiver recherche floue
    'threshold' => 0.4,      // Seuil de similarité (0.0 - 1.0)
]);

// Options camelCase ou snake_case (les deux fonctionnent)
$results = FuzzySearch::search('test', [
    'minScore' => 0.2,       // camelCase
    'max_results' => 15,     // snake_case
]);

// Recherche exacte (désactive la recherche floue)
$results = FuzzySearch::search('John Doe', [
    'fuzzy' => false,        // Recherche exacte seulement
]);

// Recherche avec score élevé seulement
$results = FuzzySearch::search('important', [
    'min_score' => 0.8,      // Résultats très pertinents seulement
]);

// Recherche avec seuil de similarité personnalisé
$results = FuzzySearch::search('approximate', [
    'fuzzy' => true,
    'threshold' => 0.2,      // Plus sensible aux correspondances faibles
]);
```

### Gestion des modèles et index

```php
use Fuzzy\FuzzySearch;
use App\Models\Product;

// Indexer un modèle spécifique
$product = Product::find(1);
FuzzySearch::indexModel($product);

// Mettre à jour l'index d'un modèle
$product->name = 'Nouveau nom';
$product->save();
FuzzySearch::updateModelIndex($product);

// Supprimer un modèle de l'index
FuzzySearch::removeModelFromIndex($product);

// Réindexer tous les modèles d'une classe
FuzzySearch::reindexModel(Product::class);

// Réindexer tout
FuzzySearch::reindexAll();

// Obtenir des statistiques
$stats = FuzzySearch::getStats();
echo "Total entries: " . $stats['total_entries'];
```

### Utilitaire de similarité

```php
use Fuzzy\FuzzySearch;

// Calculer la similarité entre deux chaînes
$similarity = FuzzySearch::calculateSimilarity('hello', 'helo');
// Résultat: ~0.85 (très similaire)

$similarity = FuzzySearch::calculateSimilarity('cat', 'dog');
// Résultat: ~0.1 (peu similaire)

// Normaliser une chaîne pour la recherche
$normalized = FuzzySearch::normalize('Héllò Wörld!');
// Résultat: 'hello world'

// Séparer une chaîne en mots
$words = FuzzySearch::splitIntoWords('hello-world test');
// Résultat: ['hello', 'world', 'test']
```

## 🎯 Comment tirer le meilleur parti de ce package

### 1. Vue d'ensemble de l'architecture

Le package fonctionne via un **pipeline en 4 étapes** qui transforme une requête utilisateur brute en résultats pertinents. Contrairement à une recherche SQL classique, chaque étape ajoute une couche d'intelligence : normalisation des termes, découverte via trigrammes, calcul de score basé sur plusieurs critères, et enfin tri intelligent.

### 2. Avant / Après : du SQL LIKE à la recherche fuzzy

**AVANT - Approche SQL traditionnelle :**
```php
// Recherche basique avec LIKE - problématique à plusieurs niveaux
public function searchProducts(string $query): Collection
{
    return Product::where(function ($q) use ($query) {
        $terms = explode(' ', $query);

        foreach ($terms as $term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%");
        }
    })
    ->where('status', 'active') // Filtre métier mélangé
    ->orderBy('created_at', 'desc') // Tri arbitraire
    ->limit(20)
    ->get(); // Pas de notion de pertinence
}
```

**Problèmes avec cette approche :**
- ❌ **Pas de tolérance aux fautes** : "iphne" ne trouvera pas "iPhone"
- ❌ **Performance faible** : Les `%LIKE%` ne peuvent pas utiliser d'index efficacement
- ❌ **Logique métier mélangée** : Filtres et recherche dans la même requête
- ❌ **Pas de scoring** : Impossible de trier par pertinence
- ❌ **Complexe à maintenir** : Ajouter un champ requiert de modifier la requête

**APRÈS - Avec Laravel Fuzzy Search :**
```php
// Approche déclarative et maintenable
class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public array $searchableFields = ['name', 'description', 'sku'];

    public function shouldBeIndexed(): bool
    {
        // Séparation claire : logique d'indexation ici
        return $this->status === 'active' && $this->is_visible;
    }
}

// Utilisation propre et séparée
public function searchProducts(string $query): Collection
{
    // Le package gère tout : fautes de frappe, scoring, performance
    return Product::fuzzySearch($query, [
        'min_score' => 0.3,
        'max_results' => 20,
    ]);

    // Tri automatique par pertinence
    // Tolérance aux fautes intégrée
    // Performance optimisée via trigrammes
}
```

**Bénéfices concrets :**
- ✅ **Tolérance aux fautes** : "iphne" → "iPhone", "gaming" → "gaming"
- ✅ **Performance optimisée** : Index trigrammes + cache intelligent
- ✅ **Séparation des responsabilités** : Indexation vs utilisation
- ✅ **Scoring intelligent** : Tri par pertinence réelle
- ✅ **Maintenabilité** : Ajouter un champ = modifier un tableau

### 3. Exemple réel de migration

**Scénario :** Migration d'un catalogue produits avec 50 000 références et recherche actuellement basée sur Elasticsearch coûteux.

**Ancienne implémentation (surcouche complexe) :**
```php
// Ancien service surchargé de responsabilités
class ProductSearchService
{
    public function search(string $query, array $filters = []): array
    {
        // 1. Préparation complexe
        $terms = $this->analyzer->analyze($query);
        $elasticQuery = $this->buildElasticQuery($terms, $filters);

        // 2. Appel externe
        $results = $this->elasticClient->search($elasticQuery);

        // 3. Transformation des résultats
        $productIds = collect($results['hits'])->pluck('_id');

        // 4. Chargement des modèles (problème N+1)
        $products = Product::whereIn('id', $productIds)
            ->with(['category', 'brand', 'images'])
            ->get();

        // 5. Réorganisation pour correspondre aux résultats Elastic
        $sortedProducts = $this->sortProducts($products, $results);

        // 6. Formatage
        return $this->formatter->format($sortedProducts);
    }

    // 200 lignes de méthodes privées complexes...
}
```

**Nouvelle implémentation avec Laravel Fuzzy Search :**
```php
// Configuration simple et déclarative
class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public array $searchableFields = ['name', 'description', 'sku', 'meta_keywords'];

    public ?string $fuzzyFormat = ProductSearchResult::class;

    public function shouldBeIndexed(): bool
    {
        return $this->is_published
            && $this->stock_count > 0
            && $this->category->is_active;
    }
}

// Configuration de pré-chargement dans config/fuzzy.php
'eager_load' => [
    App\Models\Product::class => ['category', 'brand', 'media'],
],

// Utilisation dans le contrôleur
class ProductController extends Controller
{
    public function search(SearchProductRequest $request)
    {
        // Une seule ligne pour la recherche
        $results = Product::fuzzySearch($request->input('query'), [
            'min_score' => $request->input('min_score', 0.2),
            'max_results' => 24,
        ]);

        // Les résultats sont déjà formatés par ProductSearchResult
        return ProductSearchResource::collection($results);
    }
}
```

**Gains observés :**
- **Réduction de code** : 200+ lignes → ~30 lignes
- **Maintenabilité** : Logique d'indexation séparée de la logique de recherche
- **Performance** : Plus d'appels externes, tout est en base de données
- **Simplicité** : Pas de service externe à gérer
- **Coûts** : Économie de 500€/mois sur l'instance Elasticsearch

## 🧠 Concepts Clés

### Architecture du Pipeline de Recherche

Le package utilise un pipeline modulaire en 4 étapes :

1. **NormalizeQueryStage** : Nettoie et normalise la requête, retire les stop words des requêtes longues
2. **MatchDiscoveryStage** : Trouve les correspondances potentielles via trigrammes, filtrage par longueur et première lettre
3. **ScoringStage** : Calcule les scores de pertinence avec bonus/penalties
4. **SortAndLimitStage** : Trie par score descendant et limite les résultats

### Système de Scoring

Le score final (0.0 - 1.0) est calculé à partir de plusieurs facteurs :

- **Match exact** : 1.0 (parfait)
- **Match par mot** : 0.9 + bonus
- **Match flou** : Similarité × poids + bonus
- **Bonus** :
  - Position (mot en début de champ : +20%)
  - Couverture complète (tous les mots trouvés : +30%)
  - Couverture élevée (>75% des mots : +15%)
  - Lettres consécutives (2 lettres : +5%, 3 lettres : +15%, etc.)
- **Penalties** :
  - Requêtes courtes (<4 caractères : -40%)
  - Champs peu importants (description : -20%)

### Optimisations de Performance

1. **Indexation par trigrams** : Recherche ultra-rapide via table `fuzzy_index`
2. **Cache intelligent** : TTL configurable par type de recherche
3. **Batch loading** : Évite les requêtes N+1 via pré-chargement
4. **Optimisations DB** : Indexes spécifiques sur mots, longueurs
5. **Filtres rapides** : Par première lettre et longueur de mot

## 🔄 Pourquoi choisir Laravel Fuzzy Search ?

| Caractéristique | Laravel Fuzzy Search | Laravel Scout + Algolia | Laravel Scout + Meilisearch | SQL LIKE brut | Elasticsearch |
|-----------------|---------------------|------------------------|----------------------------|---------------|----------------|
| **Coût** | ✅ Gratuit | 💰 Payant (cloud) | 💰 Payant (self-hosted/cloud) | ✅ Gratuit | 💰 Complexe/coûteux |
| **Complexité** | ✅ Simple | ⚠️ Moyenne | ⚠️ Moyenne | ✅ Simple | 🔴 Complexe |
| **Installation** | ✅ Composer + DB | ✅+🔌 Service externe | ✅+🔌 Service externe | ✅ Native | 🔴 Service séparé |
| **Recherche floue** | ✅ Avancée | ✅ Bonne | ✅ Excellente | ❌ Basique | ✅ Excellente |
| **Fautes de frappe** | ✅ Gérées | ✅ Gérées | ✅ Gérées | ❌ Non | ✅ Gérées |
| **Performance** | ✅ Optimisée DB | ✅ Excellente | ✅ Excellente | ⚠️ Lente | ✅ Excellente |
| **Multi-mots** | ✅ Intelligent | ✅ Oui | ✅ Oui | ⚠️ Basique | ✅ Oui |
| **Scoring avancé** | ✅ Configurable | ✅ Bon | ✅ Bon | ❌ Aucun | ✅ Avancé |
| **Cache intégré** | ✅ Oui | ❌ Non | ❌ Non | ❌ Non | ❌ Non |
| **Indépendance** | ✅ 100% DB | ❌ Dépendant | ⚠️ Self-hosted | ✅ 100% DB | 🔴 Service séparé |
| **Maintenance** | ✅ Zéro externe | ❌ Géré par tiers | ⚠️ À vous | ✅ Zéro | 🔴 Lourde |
| **Learning Curve** | ✅ Faible | ⚠️ Moyenne | ⚠️ Moyenne | ✅ Faible | 🔴 Raide |

### Quand utiliser Laravel Fuzzy Search ?

**✅ Idéal pour :**
- Applications avec 10K - 500K enregistrements
- Budget limité ou contraintes de coût
- Équipes souhaitant garder le contrôle total
- Déploiements simples (shared hosting, VPS)
- Recherche "good enough" avec tolérance aux fautes
- Données sensibles qui doivent rester dans votre infrastructure

**⚠️ Considérer une alternative si :**
- Plus de 1M+ enregistrements avec haute fréquence de recherche
- Besoins de recherche sémantique/vectorielle
- Équipe dédiée aux opérations de search
- Budget illimité pour des solutions cloud
- Requêtes très complexes avec facettes, filtres avancés

### Pourquoi ne pas utiliser Laravel Scout ?

**Laravel Fuzzy Search est préférable quand :**

| Cas d'usage | Pourquoi ce package est mieux adapté |
|-------------|----------------------------------------|
| **Budget limité** | Aucun coût supplémentaire (hébergement, licences) |
| **Contrôle total requis** | Tout reste dans votre base de données |
| **Déploiement simple** | Pas de service externe à configurer/maintenir |
| **Données sensibles** | Les données ne quittent jamais votre infrastructure |
| **Intégration légère** | Évite la surcharge cognitive d'un service supplémentaire |
| **Recherche "good enough"** | Besoins de base à intermédiaires satisfaits |
| **Dépendances minimales** | Seulement spatie/laravel-data + illuminate/* |

**Laravel Scout est préférable quand :**

| Cas d'usage | Pourquoi Scout est mieux adapté |
|-------------|----------------------------------|
| **Volume très important** | +1 million de documents avec recherche fréquente |
| **Fonctionnalités avancées** | Recherche sémantique, vectorielle, géospatiale |
| **Équipe dédiée** | Ressources pour maintenir un service search séparé |
| **Budget illimité** | Solutions cloud managées (Algolia) acceptables |
| **Performance extrême** | Latence < 10ms requise à tout prix |
| **Écosystème existant** | Déjà investi dans Elasticsearch/Meilisearch |

**Compromis techniques à comprendre :**

```php
// Avec ce package - Simple mais limité en volume
Avantages:
• ✅ Intégration immédiate (composer + migrate)
• ✅ Coût = 0 (déjà payé pour la DB)
• ✅ Contrôle total (SQL, backups, monitoring)
• ✅ Dépendances minimes

Limites:
• ⚠️ Montée en charge limitée par la DB
• ⚠️ Fonctionnalités search "basiques"
• ⚠️ Maintenance des index à gérer

// Avec Scout + Meilisearch - Puissant mais complexe
Avantages:
• ✅ Performance extrême
• ✅ Fonctionnalités riches (facettes, synonymes, etc.)
• ✅ Scalabilité horizontale

Limites:
• ⚠️ Service supplémentaire à maintenir
• ⚠️ Coût d'hébergement/licence
• ⚠️ Complexité opérationnelle accrue
• ⚠️ Synchronisation à gérer
```

**Recommandation pratique :**
- Commencez avec **Laravel Fuzzy Search** pour valider le besoin
- Passez à **Scout + Meilisearch auto-hébergé** si vous dépassez 500K documents
- Considérez **Scout + Algolia** seulement si le budget le permet et que la maintenance interne est un problème

Ce package remplit parfaitement 80% des cas d'usage courants, tandis que Scout couvre les 20% de cas avancés nécessitant des investissements spécifiques.

## 📖 API Référence

### Facade `FuzzySearch`

#### Méthodes principales

```php
// Recherche dans tous les modèles
FuzzySearch::search(string $query, array $options = []): Illuminate\Support\Collection

// Recherche dans un modèle spécifique
FuzzySearch::searchInModel(string $modelClass, string $query, array $options = []): Illuminate\Support\Collection

// Recherche dans plusieurs modèles
FuzzySearch::searchInModels(array $modelClasses, string $query, array $options = []): Illuminate\Support\Collection

// Indexation
FuzzySearch::indexModel(Fuzzy\Contracts\MustFuzzySearch $model): void
FuzzySearch::updateModelIndex(Fuzzy\Contracts\MustFuzzySearch $model): void
FuzzySearch::removeModelFromIndex(Fuzzy\Contracts\MustFuzzySearch $model): void
FuzzySearch::reindexModel(string $modelClass): void
FuzzySearch::reindexAll(): void

// Statistiques
FuzzySearch::getStats(): array

// Utilitaires
FuzzySearch::calculateSimilarity(string $str1, string $str2): float
FuzzySearch::normalize(string $str): string
FuzzySearch::splitIntoWords(string $str): array
```

#### Options de recherche (`$options` array)

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `min_score` / `minScore` | `float` | 0.1 | Score minimum (0.0-1.0) pour inclure un résultat |
| `max_results` / `maxResults` | `int` | 20 | Nombre maximum de résultats à retourner |
| `fuzzy` | `bool` | `true` | Activer/désactiver la recherche floue |
| `threshold` | `float` | 0.3 | Seuil de similarité pour les matchs flous (0.0-1.0) |

### Trait `FuzzySearchable`

```php
// Dans votre modèle
class YourModel extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // REQUIS: Champs à indexer
    public array $searchableFields = ['name', 'email'];

    // OPTIONNEL: Formatage personnalisé via propriété
    public ?string $fuzzyFormat = YourSearchData::class;

    // OPTIONNEL: Formatage personnalisé via méthode (prioritaire)
    public function getFuzzyFormat(): ?string { /* ... */ }

    // OPTIONNEL: Contrôle d'indexation
    public function shouldBeIndexed(): bool { /* ... */ }

    // REQUIS: ID unique pour l'indexation
    public function getIndexableId(): string|int { /* ... */ }

    // REQUIS: Récupération d'attribut (hérité de Model)
    public function getAttribute($key) { return parent::getAttribute($key); }

    // Méthode de recherche via le modèle
    public static function fuzzySearch(string $query, array $options = []): Illuminate\Support\Collection
}
```

### Commandes Artisan

```bash
# Gestion de l'index
php artisan fuzzy:index [model] [--force] [--chunk=100] [--list]
php artisan fuzzy:clear [model] [--force]
php artisan fuzzy:stats

# Gestion du cache
php artisan fuzzy:clear-cache [--force] [--model=] [--stats]

# Aide et découverte
php artisan fuzzy:index --list  # Liste les modèles découverts sans indexer
```

**Options disponibles :**
- `[model]` : Classe complète du modèle à indexer (ex: `App\Models\User`)
- `--force` : Force la réindexation (supprime l'index existant)
- `--chunk` : Taille des lots pour l'indexation (défaut: 100)
- `--list` : Liste les modèles disponibles sans indexer

## 🛠️ Gestion et Maintenance

### Supervision de l'index

```bash
# Vérifier l'état de l'index
php artisan fuzzy:stats

# Sortie exemple:
=== Search Index Statistics ===
Total entries: 1250
Per model statistics:
+---------------------+---------+---------------------------+
| Model               | Entries | Fields                    |
+---------------------+---------+---------------------------+
| App\Models\User     | 500     | name: 250, email: 250     |
| App\Models\Product  | 750     | name: 375, description: 375|
+---------------------+---------+---------------------------+
```

### Nettoyage et maintenance

```bash
# Supprimer l'index pour un modèle spécifique
php artisan fuzzy:clear "App\Models\Product" --force

# Supprimer tous les index
php artisan fuzzy:clear --force

# Vider le cache de recherche
php artisan fuzzy:clear-cache --force

# Vider seulement le cache des statistiques
php artisan fuzzy:clear-cache --stats --force

# Vider le cache pour un modèle spécifique
php artisan fuzzy:clear-cache --model="App\Models\User" --force
```

## ⚡ Performance & Optimisation

### Configuration optimale pour la production

```php
// config/fuzzy.php
return [
    'cache' => [
        'enabled' => true, // TOUJOURS activer en production
        'prefix' => 'fuzzy_search:',
        'ttl' => [
            'search' => 3600,        // 1 heure pour les recherches
            'search_in_model' => 1800, // 30 minutes pour les recherches spécifiques
            'search_in_models' => 1800,
            'stats' => 300,          // 5 minutes pour les stats
        ],
    ],

    'index' => [
        'min_word_length' => 2,
        'max_word_length' => 100,    // Augmenter pour les champs longs
        'batch_size' => 1000,        // Augmenter pour grandes tables
    ],

    // Pré-chargement agressif pour éviter N+1
    'eager_load' => [
        App\Models\Product::class => ['category', 'brand', 'images'],
        App\Models\User::class => ['profile', 'company'],
    ],
];
```

### Optimisations pour les grandes tables

```bash
# Indexation par lots larges
php artisan fuzzy:index --chunk=5000

# Indexation pendant les heures creuses
php artisan fuzzy:index --chunk=10000 > /dev/null 2>&1 &

# Indexation spécifique pour les nouveaux modèles
php artisan fuzzy:index "App\Models\Product" --chunk=2000
```

### Limitations connues et solutions

1. **Grandes bases de données (+500K enregistrements)**
   ```php
   // Solution: Indexation par lots et cache agressif
   'batch_size' => 5000,
   'cache' => ['ttl' => ['search' => 86400]], // Cache 24h
   ```

2. **Champs très longs (>1000 caractères)**
   ```php
   // Solution: Augmenter la limite ou tronquer
   'index' => ['max_word_length' => 200],

   // Ou dans votre modèle:
   public function getAttribute($key)
   {
       $value = parent::getAttribute($key);
       if (in_array($key, $this->searchableFields) && strlen($value) > 1000) {
           return substr($value, 0, 1000) . '...';
       }
       return $value;
   }
   ```

## 🤝 Contribution

### Workflow de Contribution

1. **Fork** le projet sur GitHub
2. **Clone** votre fork localement
3. **Créez une branche** pour votre fonctionnalité
   ```bash
   git checkout -b feature/ma-fonctionnalite
   ```
4. **Développez** avec des commits atomiques
   ```bash
   git commit -m "feat: ajout de la recherche par trigrammes"
   git commit -m "test: tests pour la nouvelle fonctionnalité"
   git commit -m "docs: documentation mise à jour"
   ```
5. **Poussez** votre branche
   ```bash
   git push origin feature/ma-fonctionnalite
   ```
6. **Ouvrez une Pull Request** sur le repository principal

### Standards de Code

- **PSR-12** : Standards de codage PHP
- **PHPStan** : Niveau 6 minimum
- **PHPUnit** : Tests avec couverture > 80%
- **Conventional Commits** :
  ```
  feat:     Nouvelle fonctionnalité
  fix:      Correction de bug
  docs:     Modification de documentation
  style:    Formatage, point-virgule manquant, etc.
  refactor: Refactoring de code
  test:     Ajout ou correction de tests
  chore:    Tâches de maintenance
  ```

### Tests requis pour les contributions

```bash
# Avant de soumettre une PR, exécutez:
composer test
composer analyse  # PHPStan
composer lint     # PHP CS Fixer

# Les tests doivent tous passer
# La couverture de code ne doit pas baisser
# Aucune erreur PHPStan de niveau 6
```

### Documentation des changements

```markdown
## [Version] - YYYY-MM-DD

### Ajouté
- Nouvelle fonctionnalité X
- Support pour Y

### Modifié
- Changement breaking Z
- Amélioration de performance

### Supprimé
- Fonctionnalité dépréciée A

### Corrections
- Bug #123: Description
```

## 📄 Licence

Le package Laravel Fuzzy Search est open-source sous licence [MIT](LICENSE).

### Conditions de la licence MIT

```
```
Copyright (c) 2024 [andydefer]

Vous pouvez utiliser ce logiciel librement :
- l’utiliser pour n’importe quel usage
- le copier
- le modifier
- le distribuer
- le vendre

Vous devez simplement :
- conserver ce message de copyright et de licence dans les copies du logiciel.

Ce logiciel est fourni "tel quel", sans aucune garantie.
L’auteur n’est pas responsable des problèmes, bugs ou dommages
liés à l’utilisation du logiciel.
```

```

## 🆘 Support

### Ressources

- **Documentation** : [Lien vers documentation complète]
- **GitHub Issues** : [Lien vers les issues]
- **Discussions** : [Lien vers les discussions]

### Support commercial

Pour un support commercial, une assistance à la mise en œuvre ou des fonctionnalités personnalisées, contactez-nous à [votre-email@example.com].

### Communauté

Rejoignez notre communauté pour :
- Poser des questions
- Partager vos implémentations
- Proposer des améliorations
- Discuter des cas d'usage

### Reporting de bugs

```markdown
**Description du bug**
Une description claire et concise du bug.

**Pour reproduire**
Étapes pour reproduire le comportement:
1. Configurer '...'
2. Exécuter '....'
3. Voir l'erreur

**Comportement attendu**
Une description claire et concise de ce que vous attendiez.

**Screenshots**
Si applicable, ajoutez des screenshots.

**Environnement:**
 - OS: [ex: Ubuntu 22.04]
 - PHP: [ex: 8.2]
 - Laravel: [ex: 11.0]
 - Package version: [ex: 1.0.0]

**Informations supplémentaires**
Toute autre information pertinente.
```

### Demande de fonctionnalités

```markdown
**Votre fonctionnalité est-elle liée à un problème?**
Une description claire et concise du problème.

**Décrivez la solution que vous souhaitez**
Une description claire et concise de ce que vous voulez qu'il arrive.

**Décrivez les alternatives que vous avez considérées**
Une description claire et concise des solutions ou fonctionnalités alternatives que vous avez considérées.

**Contexte supplémentaire**
Ajoutez tout autre contexte ou screenshots concernant la demande de fonctionnalité.
```

---
**Note** : Ce package est prêt pour la production et maintenu activement. Pour les questions, issues ou contributions, merci de consulter le repository GitHub.

**Fait avec ❤️ pour la communauté Laravel**