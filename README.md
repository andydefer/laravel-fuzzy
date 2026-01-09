# Laravel Fuzzy Search

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)
![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)

**Laravel Fuzzy Search** est un package complet et puissant pour implémenter la recherche floue (fuzzy search) dans vos applications Laravel. Basé sur un système d'indexation en base de données, il offre des performances élevées et une grande précision dans les résultats de recherche.

## ✨ Fonctionnalités principales

- 🔍 **Recherche floue avancée** avec algorithmes de similarité intelligents
- 📊 **Indexation en base de données** pour des performances optimales
- 🎯 **Recherche multi-mots** avec bonus de couverture et pénalités
- 🏷️ **Pondération des champs** (name > title > email > description)
- 🧹 **Nettoyage intelligent des requêtes** avec stop words
- 🔄 **Pipeline de traitement modulaire** avec stages configurables
- 📈 **Statistiques et monitoring** de l'index
- 🧪 **Tests complets** inclus (2300+ tests)
- 🚀 **Intégration facile** avec les modèles Eloquent existants

## 📦 Installation

```bash
composer require andydefer/laravel-fuzzy
```

Publiez les fichiers de configuration et de migration :

```bash
php artisan vendor:publish --tag=fuzzy-config
php artisan vendor:publish --tag=fuzzy-migrations
```

Exécutez les migrations :

```bash
php artisan migrate
```

## 🚀 Démarrage rapide

### 1. Ajouter l'interface à votre modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelFuzzy\Contracts\MustFuzzySearch;
use LaravelFuzzy\Traits\FuzzySearchable;

class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    protected $fillable = ['name', 'description', 'sku'];

    // Définir les champs à indexer
    public array $searchableFields = ['name', 'description', 'sku'];
}
```

### 2. Configurer les modèles recherchables

Dans `config/fuzzy.php` :

```php
return [
    'searchable_models' => [
        App\Models\Product::class,
        App\Models\User::class,
        App\Models\Article::class,
        // Ajoutez vos modèles ici
    ],
    // ...
];
```

### 3. Indexer vos modèles

```bash
# Indexer tous les modèles
php artisan fuzzy:index

# Indexer un modèle spécifique
php artisan fuzzy:index "App\Models\Product"

# Forcer la réindexation complète
php artisan fuzzy:index --force

# Indexer par lots (100 par défaut)
php artisan fuzzy:index --chunk=500
```

### 4. Utiliser la recherche

```php
// Recherche simple
$results = FuzzySearch::search('laptop pro');

// Recherche dans un modèle spécifique
$products = FuzzySearch::searchInModel(Product::class, 'laptop 16gb');

// Recherche dans plusieurs modèles
$results = FuzzySearch::searchInModels([Product::class, Article::class], 'mémoire ram');

// Avec options personnalisées
$results = FuzzySearch::search('ordinateur', [
    'minScore' => 0.2,
    'maxResults' => 10,
    'fuzzy' => true,
    'threshold' => 0.4,
]);

// Directement depuis le modèle (via le trait)
$products = Product::fuzzySearch('macbook air m2');
```

## 🎯 Algorithmes de similarité

Le package utilise des algorithmes de similarité intelligents :

### Correspondances exactes
- **Score parfait (1.0)** pour les correspondances exactes
- **Bonus de poids** pour les champs importants (name, title)

### Correspondances partielles
- **Contient la requête** : `"pro"` trouve `"professional"`
- **Sous-chaînes** : `"lap"` trouve `"laptop"`
- **Mots croisés** avec pénalités intelligentes

### Traitement multi-mots
- **Bonus de couverture** quand tous les mots sont trouvés
- **Pénalités** pour les mots courts et correspondances croisées
- **Pondération** basée sur la position des mots

## ⚙️ Configuration avancée

### Configuration des poids des champs

```php
// config/fuzzy.php
'field_weights' => [
    'name' => 1.0,      // Le plus important
    'title' => 0.9,
    'email' => 0.8,
    'description' => 0.7,
    'content' => 0.6,
    'default' => 0.5,
],
```

### Stop words personnalisées

```php
'stop_words' => [
    'le', 'la', 'les', 'un', 'une', 'des',
    'du', 'de', 'à', 'au', 'aux', 'avec',
    'et', 'ou', 'mais', 'donc', 'or', 'ni', 'car',
    // Ajoutez vos mots spécifiques
],
```

### Options de recherche par défaut

```php
'default_options' => [
    'min_score' => 0.1,     // Score minimum pour inclure un résultat
    'max_results' => 20,    // Nombre maximum de résultats
    'fuzzy' => true,        // Activer la recherche floue
    'threshold' => 0.3,     // Seuil de similarité minimum
],
```

## 🔧 API complète

### Service de recherche

```php
// Recherche de base
FuzzySearch::search(string $query, array $options = []): Collection

// Recherche dans un modèle spécifique
FuzzySearch::searchInModel(string $modelClass, string $query, array $options = []): Collection

// Recherche dans plusieurs modèles
FuzzySearch::searchInModels(array $modelClasses, string $query, array $options = []): Collection

// Indexation manuelle
FuzzySearch::indexModel(MustFuzzySearch $model): void
FuzzySearch::updateModelIndex(MustFuzzySearch $model): void
FuzzySearch::removeModelFromIndex(MustFuzzySearch $model): void

// Réindexation
FuzzySearch::reindexAll(): void
FuzzySearch::reindexModel(string $modelClass): void

// Utilitaires
FuzzySearch::calculateSimilarity(string $str1, string $str2): float
FuzzySearch::normalize(string $str): string
FuzzySearch::splitIntoWords(string $str): array
FuzzySearch::getStats(): array
```

### Trait FuzzySearchable

```php
// Dans vos modèles
class Product extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    // Champs à indexer
    public array $searchableFields = ['name', 'description'];

    // Format personnalisé (optionnel)
    public ?string $fuzzyFormat = ProductSearchData::class;

    // Méthodes disponibles via le trait
    $product->indexForSearch();      // Indexer ce modèle
    $product->updateIndexForSearch(); // Mettre à jour l'index
    $product->removeFromIndex();     // Retirer de l'index

    // Recherche dans ce modèle seulement
    Product::fuzzySearch('query', $options);
}
```

### Format personnalisé des résultats

```php
<?php

namespace App\Data;

use LaravelFuzzy\Data\FuzzySearchableData;
use App\Models\Product;

class ProductSearchData extends FuzzySearchableData
{
    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            type: 'product',
            description: $product->short_description,
            image: $product->thumbnail_url,
            url: route('products.show', $product),
            data: [
                'price' => $product->price,
                'category' => $product->category->name,
                'in_stock' => $product->in_stock,
            ],
        );
    }
}
```

## 📊 Gestion de l'index

### Commandes Artisan

```bash
# Indexer les modèles
php artisan fuzzy:index
php artisan fuzzy:index "App\Models\Product" --force --chunk=500

# Vider l'index
php artisan fuzzy:clear
php artisan fuzzy:clear "App\Models\Product" --force

# Statistiques
php artisan fuzzy:stats
```

### Statistiques de l'index

```bash
php artisan fuzzy:stats

# Sortie exemple :
=== Search Index Statistics ===
Total entries: 1,245

Per model statistics:

| Model               | Entries | Fields                            |
|---------------------|---------|-----------------------------------|
| App\Models\Product  | 850     | name: 850, description: 720      |
| App\Models\User     | 395     | name: 395, email: 395            |
```

## 🎯 Exemples d'utilisation

### E-commerce - Recherche de produits

```php
// Recherche avec fautes de frappe
$results = Product::fuzzySearch('iphnoe 14 pro max');

// Résultats trouvés :
// 1. iPhone 14 Pro Max (score: 0.95)
// 2. iPhone 14 Pro (score: 0.85)
// 3. iPhone 14 (score: 0.75)

// Recherche multi-critères
$results = FuzzySearch::search('ordinateur portable gaming 16go', [
    'minScore' => 0.2,
    'maxResults' => 15,
]);
```

### CRM - Recherche de clients

```php
class Client extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public array $searchableFields = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'address',
    ];

    public ?string $fuzzyFormat = ClientSearchData::class;
}

// Recherche même avec des informations partielles
$clients = Client::fuzzySearch('martin paris 75008');
```

### Blog - Recherche d'articles

```php
class Article extends Model implements MustFuzzySearch
{
    use FuzzySearchable;

    public array $searchableFields = [
        'title',
        'content',
        'tags',
        'author_name',
    ];

    public function getSearchableFields(): array
    {
        return array_merge($this->searchableFields, [
            'category.name', // Relation inclus
        ]);
    }
}

// Recherche sémantique
$articles = Article::fuzzySearch('apprendre laravel tutoriel débutant');
```

## 🔍 Pipeline de traitement

Le package utilise un pipeline modulaire avec 6 stages :

1. **NormalizeQueryStage** : Nettoie et normalise la requête
2. **ExactMatchStage** : Cherche les correspondances exactes
3. **WordMatchStage** : Cherche les correspondances mot à mot
4. **FuzzyMatchStage** : Applique les algorithmes de similarité
5. **MultiWordProcessingStage** : Traite les requêtes multi-mots
6. **SortAndLimitStage** : Trie et limite les résultats

### Personnalisation du pipeline

```php
<?php

namespace App\Services\Search;

use LaravelFuzzy\Stages\FuzzyMatchStage;

class CustomFuzzyStage extends FuzzyMatchStage
{
    protected function calculateAdjustedScore(
        float $baseScore,
        string $queryWord,
        bool $hasMultipleWords,
        string $field,
        float $fieldWeight,
        float $wordSimilarity
    ): float {
        // Logique personnalisée
        $adjustedScore = $baseScore * $fieldWeight;

        // Bonus pour les correspondances au début
        if (str_starts_with($field, 'name')) {
            $adjustedScore *= 1.2;
        }

        return min($adjustedScore, 1.0);
    }
}
```

## 🧪 Tests et qualité

Le package inclut plus de 2300 tests pour garantir la stabilité :

```bash
# Exécuter tous les tests
composer test

# Tests unitaires
php artisan test --testsuite=Unit

# Tests d'intégration
php artisan test --testsuite=Integration

# Couverture de code
composer test-coverage
```

## ⚡ Performances

### Optimisations incluses

- **Indexation en base de données** : Recherche rapide sans full-text scan
- **Cache des mots indexés** : Évite les calculs répétitifs
- **Pondération intelligente** : Meilleurs résultats en premier
- **Limitation des résultats** : Évite les surcharges mémoire
- **Batch processing** : Indexation par lots pour les gros volumes

### Benchmarks

```php
// Test avec 10,000 produits
$start = microtime(true);
$results = Product::fuzzySearch('laptop');
$time = microtime(true) - $start;

echo "Recherche effectuée en {$time} secondes";
// Typiquement 0.01-0.05 secondes
```

## 🔧 Dépannage

### Problèmes courants

1. **Aucun résultat trouvé**
   ```bash
   # Vérifier l'index
   php artisan fuzzy:stats

   # Réindexer
   php artisan fuzzy:index --force
   ```

2. **Performances lentes**
   ```php
   // Augmenter le chunk size
   php artisan fuzzy:index --chunk=1000

   // Vérifier les index MySQL
   SHOW INDEX FROM fuzzy_index;
   ```

3. **Erreurs de mémoire**
   ```php
   // Réduire le maxResults
   FuzzySearch::search('query', ['maxResults' => 10]);

   // Indexer par plus petits lots
   php artisan fuzzy:index --chunk=100
   ```

### Logs et monitoring

```php
// Activer les logs
Log::channel('search')->info('Fuzzy search performed', [
    'query' => $query,
    'results_count' => $results->count(),
    'execution_time' => $executionTime,
]);

// Monitoring avec Laravel Telescope
Telescope::recordSearch($query, $results);
```

## 📈 Statistiques avancées

```php
$stats = FuzzySearch::getStats();

// Exemple de sortie
[
    'total_entries' => 1245,
    'models' => [
        'App\Models\Product' => [
            'count' => 850,
            'fields' => [
                'name' => 850,
                'description' => 720,
                'sku' => 850,
            ],
        ],
        'App\Models\User' => [
            'count' => 395,
            'fields' => [
                'name' => 395,
                'email' => 395,
            ],
        ],
    ],
];
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez suivre ces étapes :

1. Fork le projet
2. Créer une branche (`git checkout -b feature/amazing-feature`)
3. Commit vos changements (`git commit -m 'Add amazing feature'`)
4. Push sur la branche (`git push origin feature/amazing-feature`)
5. Ouvrir une Pull Request

### Développement

```bash
# Cloner le projet
git clone https://github.com/andydefer/laravel-fuzzy.git

# Installer les dépendances
composer install

# Exécuter les tests
composer test

# Vérifier le style de code
composer lint
```

## 📄 Licence

Ce package est open-source et disponible sous la licence [MIT](LICENSE).

## 🔗 Liens utiles

- [Documentation complète](docs/)
- [Guide de migration](docs/migration.md)
- [Changelog](CHANGELOG.md)
- [Issues](https://github.com/andydefer/laravel-fuzzy/issues)

---

**Laravel Fuzzy Search** - Une solution professionnelle pour la recherche floue dans Laravel. Précision, performance et simplicité d'intégration. 🔍🚀

Avec son système d'indexation intelligent, ses algorithmes de similarité avancés et son intégration transparente avec Eloquent, ce package est la solution idéale pour implémenter une recherche puissante dans vos applications Laravel.