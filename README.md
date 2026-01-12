# Laravel Fuzzy Search

Un package puissant de recherche floue pour Laravel avec indexation basée sur la base de données et système de scoring avancé.

## 🚀 Fonctionnalités

- **Recherche floue intelligente** : Trouvez des résultats même avec des fautes de frappe ou des termes similaires
- **Indexation base de données** : Stockage optimisé des données de recherche
- **Pipeline modulaire** : Architecture en étapes pour un traitement flexible
- **Scoring avancé** : Système de pertinence avec bonus/penalties configurables
- **Cache intégré** : Performance optimisée avec invalidation intelligente
- **Multi-modèles** : Recherchez à travers plusieurs modèles Eloquent simultanément
- **Auto-découverte** : Détection automatique des modèles compatibles
- **Commandes Artisan** : Outils complets pour gérer l'index et le cache

## 📦 Installation

```bash
composer require andydefer/laravel-fuzzy
```

Publier la configuration et les migrations :

```bash
php artisan vendor:publish --provider="Fuzzy\\FuzzySearchServiceProvider"
```

Exécuter les migrations :

```bash
php artisan migrate
```

## 🔧 Configuration

Le fichier de configuration `config/fuzzy.php` permet de personnaliser :

- **searchable_models** : Liste des modèles à indexer
- **auto_discovery** : Détection automatique des modèles dans app/Models/
- **pipeline.stages** : Étapes de traitement personnalisables
- **cache** : Configuration du cache (TTL, invalidation)
- **scoring** : Pondérations des champs, bonus et penalties
- **stop_words** : Mots ignorés lors de la recherche

## 🎯 Utilisation rapide

### 1. Préparer vos modèles

Implémentez l'interface `MustFuzzySearch` et utilisez le trait `FuzzySearchable` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Traits\FuzzySearchable;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // Champs à indexer pour la recherche
    public array $searchableFields = ['name', 'description', 'sku'];

    // Format personnalisé pour les résultats (optionnel)
    public ?string $fuzzyFormat = ProductSearchData::class;

    // Condition d'indexation personnalisée (optionnel)
    public function shouldBeIndexed(): bool
    {
        return $this->is_active && $this->stock > 0;
    }
}
```

### 2. Indexer vos données

```bash
# Indexer tous les modèles
php artisan fuzzy:index

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Forcer la réindexation
php artisan fuzzy:index --force

# Voir les statistiques
php artisan fuzzy:stats
```

### 3. Utiliser la recherche

**Via le Facade :**

```php
use Fuzzy\FuzzySearch;

// Recherche globale
$results = FuzzySearch::search('laptop gaming');

// Recherche dans un modèle spécifique
$products = FuzzySearch::searchInModel(Product::class, 'wireless mouse');

// Recherche avec options
$results = FuzzySearch::search('iphone', [
    'min_score' => 0.3,
    'max_results' => 10,
    'fuzzy' => true,
    'threshold' => 0.4,
]);
```

**Via le trait dans vos modèles :**

```php
$products = Product::fuzzySearch('keyboard mechanical');
```

**Via l'injection de dépendance :**

```php
use Fuzzy\Services\FuzzySearchService;

class ProductController
{
    public function __construct(
        private FuzzySearchService $searchService
    ) {}

    public function search(Request $request)
    {
        $results = $this->searchService->search(
            $request->input('query'),
            ['min_score' => 0.2]
        );

        return response()->json($results);
    }
}
```

## ⚙️ Options de recherche

| Option | Type | Défaut | Description |
|--------|------|---------|-------------|
| `min_score` | float | 0.1 | Score minimum pour inclure un résultat |
| `max_results` | int | 20 | Nombre maximum de résultats |
| `fuzzy` | bool | true | Activer la recherche floue |
| `threshold` | float | 0.3 | Seuil de similarité pour les matchs flous |

## 🔍 `shouldBeIndexed()` - Contrôle d'indexation dynamique

La méthode `shouldBeIndexed()` permet de décider dynamiquement si un modèle doit être inclus dans l'index de recherche.

### **Comportement par défaut**
Si non définie, la méthode retourne `true` (tous les modèles sont indexés).

### **Exemples d'utilisation**

```php
// 🔵 Produits : seulement actifs et en stock
public function shouldBeIndexed(): bool
{
    return $this->is_active && $this->stock > 0;
}

// 🟢 Articles : seulement publiés
public function shouldBeIndexed(): bool
{
    return $this->status === 'published'
        && $this->published_at <= now();
}

// 🟡 Utilisateurs : seulement vérifiés
public function shouldBeIndexed(): bool
{
    return $this->email_verified_at !== null
        && $this->is_active;
}

// 🔴 Documents : seulement approuvés
public function shouldBeIndexed(): bool
{
    return $this->status === 'approved'
        && !$this->is_archived
        && $this->visibility === 'public';
}
```

### **Important à savoir :**
- ✅ Appelée automatiquement lors de l'indexation
- ✅ Si retourne `false`, le modèle N'EST PAS indexé
- ✅ Les modèles existants restent dans l'index jusqu'à réindexation
- ✅ Utilisez `removeModelFromIndex()` pour retirer immédiatement

### **Cas d'usage avancé :**
```php
// Indexation basée sur des relations
public function shouldBeIndexed(): bool
{
    // Vérifie que la catégorie est publiée
    return $this->category->is_published
        && $this->status === 'active'
        && $this->user->hasPermission('searchable');
}
```

## 🎨 `fuzzyFormat` - Formatage personnalisé des résultats

La propriété `fuzzyFormat` permet de transformer vos modèles en structures de données personnalisées pour l'API.

### **Fonctionnement interne :**
1. Le package vérifie si le modèle a une propriété `fuzzyFormat`
2. Si oui, il appelle la méthode statique `fromModel()` de cette classe
3. L'objet retourné remplace le modèle original dans les résultats

### **Création d'une classe de formatage :**

```php
<?php

namespace App\SearchFormatters;

use Fuzzy\Data\FuzzySearchableData;
use App\Models\Product;

class ProductSearchData extends FuzzySearchableData
{
    // ⚠️ MÉTHODE OBLIGATOIRE - Doit être statique et nommée fromModel
    public static function fromModel(Product $product): self
    {
        // Chargez les relations si nécessaire
        $product->loadMissing(['category', 'brand']);

        return new self(
            id: $product->id,
            name: $product->name,
            type: 'product',
            model: $product,
            description: $product->short_description,
            image: $product->getFirstMediaUrl('thumbnail'),
            url: route('products.show', $product),
            // Champs personnalisés
            data: [
                'price' => $product->formatted_price,
                'category' => $product->category->name,
                'brand' => $product->brand->name,
                'in_stock' => $product->stock > 0,
                'rating' => $product->average_rating,
            ]
        );
    }
}
```

### **Configuration du modèle :**
```php
class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // ⚠️ PROPRIÉTÉ REQUISE - Doit pointer vers une classe valide
    public ?string $fuzzyFormat = ProductSearchData::class;
}
```

### **Résultat formaté :**
```json
{
  "score": 0.85,
  "model_type": "Product",
  "matched_field": "name",
  "matched_value": "iPhone 15 Pro",
  "item": {
    "id": 123,
    "name": "iPhone 15 Pro",
    "type": "product",
    "description": "Smartphone Apple dernière génération...",
    "image": "https://example.com/images/iphone.jpg",
    "url": "https://example.com/products/123",
    "data": {
      "price": "1 099 €",
      "category": "Smartphones",
      "brand": "Apple",
      "in_stock": true,
      "rating": 4.7
    }
  }
}
```

### **Formatage conditionnel :**
```php
// Plusieurs classes de formatage selon le contexte
public ?string $fuzzyFormat = null;

public function getFuzzyFormat(): ?string
{
    return $this->type === 'premium'
        ? PremiumProductSearchData::class
        : ProductSearchData::class;
}
```

## 🏗️ Architecture

### Pipeline de recherche

Le système utilise un pipeline en 4 étapes :

1. **NormalizeQueryStage** : Normalisation et validation de la requête
2. **MatchDiscoveryStage** : Découverte des matches potentiels
3. **ScoringStage** : Calcul des scores de pertinence
4. **SortAndLimitStage** : Tri et limitation des résultats

### Système de scoring

Score composite basé sur :
- **Match exact** : Score parfait (1.0)
- **Match par mot** : Score élevé (0.9)
- **Match flou** : Score basé sur la similarité
- **Bonus/penalties** : Position, longueur, couverture, etc.

## 🛠️ Commandes Artisan

```bash
# Gestion de l'index
php artisan fuzzy:index [model] [--force] [--chunk=100] [--auto] [--list]
php artisan fuzzy:clear [model] [--force]
php artisan fuzzy:stats

# Gestion du cache
php artisan fuzzy:clear-cache [--force] [--model=] [--stats]

# Aide
php artisan fuzzy:index --list  # Liste les modèles découverts
```

## ⚡ Performance

Le package inclut plusieurs optimisations :

- **Index optimisés** : Structures de données pour une recherche rapide
- **Cache intelligent** : Mise en cache avec invalidation sélective
- **Préchargement batch** : Évite les requêtes N+1
- **Recherche floue optimisée** : Algorithmes efficaces pour grands jeux de données
- **Pipeline asynchrone** : Traitement modulaire et extensible

## 🧪 Tests

```bash
# Exécuter tous les tests
composer test

# Tests spécifiques
composer test -- --testsuite=Unit
composer test -- --testsuite=Feature

# Avec couverture de code
composer test-coverage
```

## 🔧 Développement

### Structure du projet

```
src/
├── Contracts/          # Interfaces
├── Services/          # Services principaux
├── Stages/           # Étapes du pipeline
├── Traits/           # Traits réutilisables
├── Models/           # Modèles Eloquent
├── Repositories/     # Répositories
├── Data/            # Objects de données
├── ValueObjects/    # Value Objects
├── Exceptions/      # Exceptions
└── Commands/        # Commandes Artisan
```

### Ajouter un nouvel algorithme de similarité

```php
<?php

namespace App\SearchAlgorithms;

use Fuzzy\Contracts\SimilarityAlgorithmInterface;

class CustomAlgorithm implements SimilarityAlgorithmInterface
{
    public function calculate(string $str1, string $str2): float
    {
        // Implémentez votre algorithme
        return $similarityScore;
    }

    public function getName(): string
    {
        return 'custom';
    }

    public function getWeight(): float
    {
        return 0.3;
    }
}
```

Enregistrez-le dans votre `AppServiceProvider` :

```php
public function boot()
{
    $calculator = app('laravel-fuzzy.similarity');
    $calculator->addAlgorithm(new CustomAlgorithm());
}
```

## 📊 Monitoring

Surveillez les performances avec les statistiques :

```php
$stats = FuzzySearch::getStats();
/*
[
    'total_entries' => 1250,
    'models' => [
        'App\Models\Product' => [
            'count' => 800,
            'fields' => ['name' => 800, 'description' => 800]
        ],
        'App\Models\User' => [
            'count' => 450,
            'fields' => ['name' => 450, 'email' => 450]
        ]
    ]
]
*/
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez consulter [CONTRIBUTING.md](CONTRIBUTING.md) pour plus de détails.

## 📄 Licence

Ce package est open-source sous la licence [MIT](LICENSE).

## 🐛 Support

Si vous rencontrez des problèmes :

1. Vérifiez les [issues existantes](https://github.com/andydefer/laravel-fuzzy/issues)
2. Créez une nouvelle issue avec un cas de test reproductible
3. Contactez-nous pour du support prioritaire

## 🌟 Fonctionnalités avancées

- **Recherche multi-mots** : Trouve des résultats même si tous les mots ne sont pas présents
- **Pondération par champ** : Donnez plus d'importance à certains champs (nom > description)
- **Bonus de position** : Les mots au début d'un champ ont un score plus élevé
- **Bonus consécutif** : Les séquences de lettres communes augmentent le score
- **Invalidation de cache** : Cache automatiquement invalidé lors des mises à jour

---

**Note :** Ce package est en développement actif. L'API peut évoluer jusqu'à la version 1.0.