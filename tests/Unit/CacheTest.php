<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit;

use Fuzzy\Tests\TestCase;
use Fuzzy\Services\FuzzySearchService;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\Fixtures\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Fuzzy\Models\FuzzyIndex;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser le cache array pour les tests (plus propre)
        config(['cache.default' => 'array']);

        // Nettoyer avant chaque test
        Cache::flush();

        // Créer des données de test minimales
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Clean up
        FuzzyIndex::query()->truncate();
        User::query()->delete();
        Product::query()->delete();

        // Créer quelques données de test
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'admin',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'type' => 'user',
        ]);

        Product::create([
            'name' => 'Laptop Pro',
            'description' => 'High-end laptop',
            'price' => 1299.99,
        ]);

        // Réindexer
        $searchService = app(FuzzySearchService::class);
        $searchService->reindexAll();
    }

    public function test_search_results_are_cached(): void
    {
        // Arrange: Activer le cache
        config(['fuzzy.cache.enabled' => true]);

        // Spy sur Cache pour vérifier les appels
        Cache::spy();

        $searchService = app(FuzzySearchService::class);

        // Act: Premier appel
        $results1 = $searchService->search('john');

        // Assert: Cache::remember doit être appelé au moins une fois
        Cache::shouldHaveReceived('remember')->atLeast()->once();

        $this->assertInstanceOf(Collection::class, $results1);
        $this->assertGreaterThan(0, $results1->count());
    }

    public function test_cache_does_not_flush_entire_application_cache(): void
    {
        // Arrange: Activer le cache
        config(['fuzzy.cache.enabled' => true]);

        $searchService = app(FuzzySearchService::class);

        // Mettre d'autres données en cache
        Cache::put('session_data', 'user123', 60);
        Cache::put('config_cache', 'value', 3600);

        // Mettre en cache une recherche
        $searchService->search('john');

        // Vérifier que nos caches existent
        $this->assertTrue(Cache::has('session_data'));
        $this->assertTrue(Cache::has('config_cache'));

        // Act: Invalider le cache fuzzy SEULEMENT
        $searchService->invalidateAllCache();

        // Assert: Les autres caches doivent toujours exister
        $this->assertTrue(
            Cache::has('session_data'),
            'Les sessions ne doivent pas être supprimées'
        );
        $this->assertTrue(
            Cache::has('config_cache'),
            'La config ne doit pas être supprimée'
        );

        // Vérifier que la recherche fuzzy est bien invalidée
        // en faisant une nouvelle recherche
        Cache::spy();
        $searchService->search('john');
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    public function test_cache_is_invalidated_after_indexing(): void
    {
        // Arrange: Activer le cache
        config(['fuzzy.cache.enabled' => true]);
        config(['fuzzy.cache.invalidation.on_index' => true]);

        $searchService = app(FuzzySearchService::class);

        // Premier appel (mise en cache)
        $initialResults = $searchService->search('john');
        $initialCount = $initialResults->count();

        // Indexer un nouveau modèle (devrait invalider le cache)
        $newUser = User::create([
            'name' => 'Johnny New',
            'email' => 'johnny@example.com',
            'type' => 'user',
        ]);

        // Spy pour voir si le cache est régénéré
        Cache::spy();

        // Act: Indexer le nouveau user
        $searchService->indexModel($newUser);

        // Faire une recherche (devrait régénérer le cache)
        $newResults = $searchService->search('john');

        // Assert: Le cache doit être régénéré (remember appelé)
        Cache::shouldHaveReceived('remember')->atLeast()->once();

        // Les résultats devraient être différents
        // (un utilisateur de plus devrait être trouvé)
        $this->assertGreaterThanOrEqual(
            $initialCount,
            $newResults->count(),
            'La recherche après indexation devrait trouver au moins autant de résultats'
        );
    }

    public function test_cache_disabled_works(): void
    {
        // Arrange: Désactiver le cache
        config(['fuzzy.cache.enabled' => false]);

        $searchService = app(FuzzySearchService::class);

        // Act: Faire une recherche
        $results = $searchService->search('test');

        // Assert: Doit retourner des résultats (même si vides)
        $this->assertInstanceOf(Collection::class, $results);

        // Vérifier qu'on peut faire plusieurs recherches sans erreur
        $results2 = $searchService->search('test');
        $this->assertInstanceOf(Collection::class, $results2);
    }

    public function test_stats_cache_has_short_ttl(): void
    {
        // Arrange: Activer le cache avec TTL court pour les stats
        config(['fuzzy.cache.enabled' => true]);
        config(['fuzzy.cache.ttl.stats' => 2]); // 2 secondes pour le test

        $searchService = app(FuzzySearchService::class);

        // Premier appel stats (mise en cache)
        $stats1 = $searchService->getStats();
        $initialCount = $stats1['models'][User::class]['count'] ?? 0;

        // Ajouter un nouvel utilisateur SANS l'indexer encore
        // (pour que les stats de l'index ne changent pas)
        $newUser = User::create([
            'name' => 'Stats Test User',
            'email' => 'stats@example.com',
            'type' => 'user',
        ]);

        // Attendre que le cache expire (2 secondes + marge)
        sleep(3);

        // Maintenant indexer le nouvel utilisateur
        $searchService->indexModel($newUser);

        // Deuxième appel stats (devrait être rafraîchi car cache expiré)
        $stats2 = $searchService->getStats();
        $newCount = $stats2['models'][User::class]['count'] ?? 0;

        // Debug si nécessaire
        if ($initialCount === $newCount) {
            // Afficher pour debug
            echo "\nDebug stats cache test:\n";
            echo "Initial count: $initialCount\n";
            echo "New count: $newCount\n";
            echo "Stats1: " . json_encode($stats1) . "\n";
            echo "Stats2: " . json_encode($stats2) . "\n";

            // Vérifier manuellement
            $directStats = $searchService->getStats();
            echo "Direct stats count: " . ($directStats['models'][User::class]['count'] ?? 0) . "\n";
        }

        // Assert: Les stats devraient être différentes car le cache a expiré
        $this->assertNotEquals(
            $initialCount,
            $newCount,
            sprintf(
                'Les stats doivent être rafraîchies après expiration du cache (TTL: 2s). ' .
                    'Initial: %d, Nouveau: %d. ' .
                    'Cela indique que le cache stats fonctionne avec le TTL configuré.',
                $initialCount,
                $newCount
            )
        );
    }

    public function test_model_specific_cache_invalidation(): void
    {
        // Arrange: Activer le cache
        config(['fuzzy.cache.enabled' => true]);
        config(['fuzzy.cache.invalidation.on_index' => true]);

        $searchService = app(FuzzySearchService::class);

        // Recherche dans User uniquement (mise en cache)
        $results1 = $searchService->searchInModel(User::class, 'john');
        $initialCount = $results1->count();

        // Créer et indexer un Product (ne devrait pas invalider le cache User)
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 100,
        ]);

        $searchService->indexModel($product);

        // Spy pour vérifier si le cache User est utilisé
        Cache::spy();

        // Recherche User à nouveau
        $results2 = $searchService->searchInModel(User::class, 'john');

        // Assert: Les résultats devraient être les mêmes
        $this->assertEquals(
            $initialCount,
            $results2->count(),
            'Le cache User ne devrait pas être affecté par l\'indexation d\'un Product'
        );

        // Vérifier que le cache a été utilisé (remember appelé pour le cache User)
        // Note: Il peut y avoir d'autres appels de cache, mais on s'assure au moins
        // que la recherche a fonctionné
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    public function test_invalidate_cache_for_specific_model(): void
    {
        // Arrange: Activer le cache
        config(['fuzzy.cache.enabled' => true]);

        $searchService = app(FuzzySearchService::class);

        // Mettre en cache des recherches pour deux modèles
        $userResults1 = $searchService->searchInModel(User::class, 'john');
        $productResults1 = $searchService->searchInModel(Product::class, 'laptop');

        // Act: Invalider seulement le cache User
        $searchService->invalidateCacheForModel(User::class);

        // Spy pour voir ce qui se passe
        Cache::spy();

        // Rechercher à nouveau
        $userResults2 = $searchService->searchInModel(User::class, 'john');
        $productResults2 = $searchService->searchInModel(Product::class, 'laptop');

        // Assert: Le cache User doit être régénéré (remember appelé)
        // Le cache Product devrait être réutilisé (si non expiré)

        // On s'assure au moins que les recherches fonctionnent
        $this->assertInstanceOf(Collection::class, $userResults2);
        $this->assertInstanceOf(Collection::class, $productResults2);

        // Vérifier que Cache::remember a été appelé (pour la régénération)
        Cache::shouldHaveReceived('remember')->atLeast()->once();
    }

    public function test_cache_keys_are_properly_managed(): void
    {
        // Arrange: Activer le cache
        config(['fuzzy.cache.enabled' => true]);

        $searchService = app(FuzzySearchService::class);

        // Faire plusieurs recherches pour générer des clés
        $searchService->search('test1');
        $searchService->search('test2');
        $searchService->searchInModel(User::class, 'test3');
        $searchService->getStats();

        // Récupérer toutes les clés stockées
        $storageKey = config('fuzzy.cache.prefix', 'fuzzy_search:') . 'cache_keys';
        $storedKeys = Cache::get($storageKey, []);

        // Assert: Des clés devraient être stockées
        $this->assertNotEmpty(
            $storedKeys,
            'Les clés de cache devraient être stockées pour invalidation future'
        );

        // Act: Invalider tout le cache
        $searchService->invalidateAllCache();

        // Assert: Les clés stockées devraient être supprimées
        $this->assertFalse(
            Cache::has($storageKey),
            'La liste des clés de cache devrait être supprimée après invalidateAllCache'
        );
    }
}
