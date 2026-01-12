# Laravel Fuzzy Search

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andydefer/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/andydefer/laravel-fuzzy)
[![License](https://img.shields.io/packagist/l/andydefer/laravel-fuzzy.svg?style=flat-square)](https://packagist.org/packages/andydefer/laravel-fuzzy)

Un package Laravel puissant de recherche floue (fuzzy search) avec indexation basée sur la base de données et système de scoring avancé. Supporte les fautes de frappe, les recherches approximatives et les recherches multi-mots avec performances optimisées.

**Problème résolu** : Implémente une recherche floue performante directement dans votre base de données Laravel sans dépendre de services externes coûteux comme Algolia ou Meilisearch. Gère automatiquement les fautes de frappe, les variations orthographiques et optimise les performances pour les grandes bases de données.

## 📦 Installation

### Prérequis
- PHP ^8.1
- Laravel ^9.0 | ^10.0 | ^11.0
- Composer
- MySQL 5.7+ ou PostgreSQL 9.6+

### Installation via Composer
```bash
composer require andydefer/laravel-fuzzy
```

### Publish les fichiers de configuration et migrations
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
    ],

    // Models explicitement configurés
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
            'stats' => 30,    // 30 secondes
        ],
    ],

    // Options de recherche par défaut
    'default_options' => [
        'min_score' => 0.1,    // Score minimum pour inclure un résultat
        'max_results' => 20,   // Limite de résultats
        'fuzzy' => true,       // Activer la recherche floue
        'threshold' => 0.3,    // Seuil de similarité
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
        ],
    ],

    // Mots ignorés dans les requêtes longues
    'stop_words' => [
        'the', 'and', 'or', 'a', 'an', 'in', 'on', 'at', 'to',
        // Ajoutez vos propres stop words
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

    // ✅ OPTIONNEL: Formatage personnalisé des résultats
    public ?string $fuzzyFormat = ProductSearchData::class;

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
            description: $product->short_description,
            image: $product->getFirstMediaUrl('thumbnail'),
            url: route('products.show', $product),
            data: [
                'price' => $product->formatted_price,
                'in_stock' => $product->stock > 0,
                'category' => optional($product->category)->name,
            ]
        );
    }
}
```

### 3. Indexer vos données

```bash
# Indexer tous les modèles configurés
php artisan fuzzy:index

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Forcer la réindexation complète
php artisan fuzzy:index --force

# Indexer par lots pour les grandes tables
php artisan fuzzy:index --chunk=500
```

### 4. Recherche de base

```php
use Fuzzy\FuzzySearch;

// Recherche globale (tous les modèles indexés)
$results = FuzzySearch::search('laptop gaming');

// Recherche dans un modèle spécifique
$products = FuzzySearch::searchInModel(Product::class, 'wireless mouse');

// Recherche dans plusieurs modèles
$results = FuzzySearch::searchInModels(
    [Product::class, Article::class],
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
```

### Gestion des modèles

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
```

## 🎯 Comment tirer le meilleur parti de ce package

### 1. Vue d'ensemble de l'architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Requête utilisateur                       │
│                   "iphne 15 pro max"                         │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                Pipeline de recherche fuzzy                   │
├─────────────────┬─────────────────┬─────────────────┬───────┤
│  1. Normalisation │  2. Découverte  │  3. Scoring     │  4. Tri │
│   • Nettoyage    │   • Trigrammes  │   • Pondération │   • Score│
│   • Stop words   │   • Matching    │   • Bonus/Pénal.│   • Limite│
│   • Tokenisation │   • Similarité  │                 │         │
└─────────────────┴─────────────────┴─────────────────┴───────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Résultats enrichis                        │
│                                                              │
│  • "iPhone 15 Pro Max" (score: 0.92)                        │
│  • "iPhone 14 Pro" (score: 0.75)                            │
│  • "Samsung Galaxy" (score: 0.15)                           │
└─────────────────────────────────────────────────────────────┘
```

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

    // Relation préchargée automatiquement
    public function getIndexableRelations(): array
    {
        return ['category', 'brand', 'media'];
    }
}

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

1. **NormalizeQueryStage** : Nettoie et normalise la requête
2. **MatchDiscoveryStage** : Trouve les correspondances potentielles
3. **ScoringStage** : Calcule les scores de pertinence
4. **SortAndLimitStage** : Trie et limite les résultats

### Système de Scoring

Le score final (0.0 - 1.0) est calculé à partir de plusieurs facteurs :

- **Match exact** : 1.0 (parfait)
- **Match par mot** : 0.9 + bonus
- **Match flou** : Similarité × poids + bonus
- **Bonus** : Position, couverture, mots consécutifs
- **Pénalités** : Requêtes courtes, champs peu importants

### Optimisations de Performance

1. **Indexation par trigrams** : Recherche ultra-rapide
2. **Cache intelligent** : TTL configurable par type
3. **Batch loading** : Évite les requêtes N+1
4. **Optimisations DB** : Indexes spécifiques
5. **Pré-chargement** : Chargement anticipé des modèles

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

**⚠️ Considérer une alternative si :**
- Plus de 1M+ enregistments avec haute fréquence de recherche
- Besoins de recherche sémantique/vectorielle
- Équipe dédiée aux opérations de search
- Budget illimité pour des solutions cloud

### 4. Pourquoi ne pas utiliser Laravel Scout ?

**Laravel Fuzzy Search est préférable quand :**

| Cas d'usage | Pourquoi ce package est mieux adapté |
|-------------|----------------------------------------|
| **Budget limité** | Aucun coût supplémentaire (hébergement, licences) |
| **Contrôle total requis** | Tout reste dans votre base de données |
| **Déploiement simple** | Pas de service externe à configurer/maintenir |
| **Données sensibles** | Les données ne quittent jamais votre infrastructure |
| **Intégration légère** | Évite la surcharge cognitive d'un service supplémentaire |
| **Recherche "good enough"** | Besoins de base à intermédiaires satisfaits |

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
• ✅ Dépendances = 0

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
FuzzySearch::search(string $query, array $options = []): Collection

// Recherche dans un modèle spécifique
FuzzySearch::searchInModel(string $modelClass, string $query, array $options = []): Collection

// Recherche dans plusieurs modèles
FuzzySearch::searchInModels(array $modelClasses, string $query, array $options = []): Collection

// Gestion de l'index
FuzzySearch::indexModel(MustFuzzySearch $model): void
FuzzySearch::updateModelIndex(MustFuzzySearch $model): void
FuzzySearch::removeModelFromIndex(MustFuzzySearch $model): void
FuzzySearch::reindexModel(string $modelClass): void
FuzzySearch::reindexAll(): void

// Statistiques
FuzzySearch::getStats(): array

// Utilitaires
FuzzySearch::calculateSimilarity(string $str1, string $str2): float
FuzzySearch::normalize(string $str): string
FuzzySearch::splitIntoWords(string $str): array
```

### Trait `FuzzySearchable`

```php
// Dans votre modèle
class YourModel extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // REQUIS: Champs à indexer
    public array $searchableFields = ['name', 'email'];

    // OPTIONNEL: Formatage personnalisé
    public ?string $fuzzyFormat = YourSearchData::class;
    // OU
    public function getFuzzyFormat(): ?string { /* ... */ }

    // OPTIONNEL: Contrôle d'indexation
    public function shouldBeIndexed(): bool { /* ... */ }

    // REQUIS: ID unique
    public function getIndexableId(): string|int { /* ... */ }

    // Méthode de recherche via le modèle
    public static function fuzzySearch(string $query, array $options = []): Collection
}
```

### Commandes Artisan

```bash
# Gestion de l'index
php artisan fuzzy:index [model] [--force] [--chunk=100] [--auto] [--list]
php artisan fuzzy:clear [model] [--force]
php artisan fuzzy:stats

# Gestion du cache
php artisan fuzzy:clear-cache [--force] [--model=] [--stats]

# Aide et découverte
php artisan fuzzy:index --list  # Liste les modèles découverts
```

## ⚡ Performance & Limitations

### Recommendations de Performance

```php
// config/fuzzy.php
'cache' => [
    'enabled' => true, // TOUJOURS activer en production
    'ttl' => [
        'search' => 3600, // 1 heure pour les recherches
        'stats' => 30,    // Court pour les stats
    ],
],

// Pour les grandes tables
'index' => [
    'batch_size' => 1000, // Augmenter pour grandes tables
],
```

### Limitations Connues

1. **Grandes bases de données** : Pour +500 000 enregistrements, considérez :
   - Augmenter `batch_size` à 5000
   - Utiliser `php artisan fuzzy:index --chunk=1000`
   - Indexer pendant les heures creuses

2. **Champs très longs** : Les champs > 1000 caractères peuvent être tronqués

3. **Recherche multi-langues** : Support basique de l'internationalisation
   - Accents normalisés (é → e)
   - Pas de support avancé pour les langues non-latines

4. **Mémoire** : L'indexation de très grandes tables peut nécessiter plus de mémoire PHP

### Benchmarks Indicatifs

| Records | Indexation | Recherche |
|---------|------------|-----------|
| 10 000  | ~2s        | ~50ms     |
| 100 000 | ~15s       | ~150ms    |
| 500 000 | ~1-2min    | ~300ms    |

## 🔒 Sécurité

### Bonnes Pratiques

```php
// VALIDATION des requêtes
public function search(Request $request)
{
    $validated = $request->validate([
        'query' => 'required|string|max:100',
        'min_score' => 'sometimes|numeric|between:0,1',
    ]);

    // LIMITATION des résultats
    $results = FuzzySearch::search($validated['query'], [
        'max_results' => 50, // Limite stricte
        'min_score' => $validated['min_score'] ?? 0.1,
    ]);

    // FILTRAGE par permissions
    $filteredResults = $results->filter(function ($result) {
        return auth()->user()->can('view', $result->item);
    });

    return response()->json($filteredResults);
}
```

### Protection contre les abus

1. **Rate limiting** sur les endpoints de recherche :
```php
// Dans routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/search', 'SearchController@index');
});
```

2. **Validation des paramètres** : Toujours valider `min_score`, `max_results`

3. **Logging des requêtes** : Surveillez les patterns suspects

## 🧪 Tests

```bash
# Lancer tous les tests
composer test

# Tests unitaires seulement
composer test -- --testsuite=Unit

# Tests d'intégration
composer test -- --testsuite=Feature

# Exemple de test unitaire
public function test_basic_search_returns_results()
{
    Product::factory()->create(['name' => 'iPhone 15 Pro']);

    $results = FuzzySearch::searchInModel(Product::class, 'iphone');

    $this->assertCount(1, $results);
    $this->assertEquals('iPhone 15 Pro', $results->first()->item->name);
}
```

## 🤝 Contribution

### Workflow de Contribution

1. **Fork** le projet
2. **Clone** votre fork
3. **Branche** (`git checkout -b feature/ma-fonctionnalite`)
4. **Commits** avec messages conventionnels
5. **Tests** (obligatoires pour nouvelles fonctionnalités)
6. **Push** (`git push origin feature/ma-fonctionnalite`)
7. **Pull Request**

### Standards de Code

- **PSR-12** : Coding standards
- **PHPStan** : Niveau 6 minimum
- **PHPUnit** : Tests avec coverage > 80%
- **Conventional Commits** :
  - `feat:` Nouvelle fonctionnalité
  - `fix:` Correction de bug
  - `docs:` Documentation
  - `test:` Tests uniquement
  - `refactor:` Refactoring
  - `style:` Formatage

### Structure du Projet

```
src/
├── Commands/          # Commandes Artisan
├── Contracts/         # Interfaces
├── Data/              # Data Objects
├── Exceptions/        # Exceptions personnalisées
├── Models/            # Modèles Eloquent
├── Repositories/      # Pattern Repository
├── Services/          # Services principaux
├── Stages/            # Pipeline stages
├── Traits/            # Traits réutilisables
└── ValueObjects/      # Value Objects
```

## 📋 Changelog

Les changements notables sont documentés dans le fichier [CHANGELOG.md](CHANGELOG.md).

Ce projet suit [Semantic Versioning](https://semver.org/).

## 📄 Licence

Le package Laravel Fuzzy Search est open-source sous licence [MIT](LICENSE).

## 🆘 Support & Dépannage

### Problèmes Courants

1. **"Aucun résultat retourné"**
   ```bash
   # Vérifiez l'index
   php artisan fuzzy:stats

   # Vérifiez shouldBeIndexed()
   # Baissez min_score
   FuzzySearch::search('test', ['min_score' => 0.01])
   ```

2. **"Erreur lors de l'indexation"**
   ```bash
   # Vérifiez les logs
   tail -f storage/logs/laravel.log

   # Augmentez la mémoire
   php -d memory_limit=512M artisan fuzzy:index
   ```

3. **"Performances lentes"**
   ```php
   // Activez le cache
   config(['fuzzy.cache.enabled' => true]);

   // Augmentez TTL
   config(['fuzzy.cache.ttl.search' => 86400]); // 24h
   ```

### Debug

```php
// Mode debug
config(['fuzzy.debug' => true]);

// Logging des requêtes
\Log::debug('Fuzzy search', [
    'query' => $query,
    'results_count' => $results->count(),
    'execution_time' => $executionTime,
]);
```

---
**Note** : Ce package est prêt pour la production et maintenu activement. Pour les questions, issues ou contributions, merci de consulter le repository GitHub.