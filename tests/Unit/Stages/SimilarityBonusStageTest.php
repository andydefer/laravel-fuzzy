<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Stages;

use Fuzzy\Tests\TestCase;
use Fuzzy\Stages\SimilarityBonusStage;
use Fuzzy\SearchContext;
use Fuzzy\Data\SearchOptionsData;
use Fuzzy\Data\SearchResultData;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Models\FuzzyIndex;
use Illuminate\Support\Facades\DB;
use Fuzzy\Services\StringNormalizer;
use Fuzzy\Services\SimilarityCalculator;
use Fuzzy\Services\IndexBuilder;

class SimilarityBonusStageTest extends TestCase
{
    private SimilarityBonusStage $stage;
    private StringNormalizer $normalizer;
    private SimilarityCalculator $similarityCalculator;
    private IndexBuilder $indexBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->stage = new SimilarityBonusStage();
        $this->normalizer = app(StringNormalizer::class);
        $this->similarityCalculator = app(SimilarityCalculator::class);
        $this->indexBuilder = app(IndexBuilder::class);

        // Nettoyer la base
        DB::statement('PRAGMA foreign_keys=off');
        User::query()->delete();
        FuzzyIndex::query()->delete();
        DB::statement('PRAGMA foreign_keys=on');
    }

    /** @test */
    public function test_consecutive_characters_bonus(): void
    {
        // Créer et sauvegarder un utilisateur
        $user = User::create([
            'name' => 'Giloise Clinic',
            'email' => 'giloise@example.com',
            'type' => 'user'
        ]);

        // Indexer manuellement
        $this->indexBuilder->indexModel($user);

        $context = $this->createMockContext($user, 'galoise', 'Giloise Clinic');

        $result = new SearchResultData(
            item: $user,
            score: 0.5,
            modelType: User::class,
            matchedField: 'name',
            matchedValue: 'Giloise Clinic'
        );

        $context->results = ['user_' . $user->id => $result];

        $next = function ($ctx) {
            return $ctx->results;
        };

        $this->stage->handle($context, $next);

        // Le score devrait être augmenté grâce au bonus consécutif
        $this->assertGreaterThan(
            0.5,
            $result->score,
            'Score should be increased due to consecutive characters bonus (galoise vs giloise)'
        );
    }

    /** @test */
    public function test_common_typo_bonus(): void
    {
        // Créer et sauvegarder un utilisateur
        $user = User::create([
            'name' => 'Saint-Germain Hospital',
            'email' => 'saint@example.com',
            'type' => 'user'
        ]);

        $this->indexBuilder->indexModel($user);

        $context = $this->createMockContext($user, 'germoin', 'Saint-Germain Hospital');

        $result = new SearchResultData(
            item: $user,
            score: 0.4,
            modelType: User::class,
            matchedField: 'name',
            matchedValue: 'Saint-Germain Hospital'
        );

        $context->results = ['user_' . $user->id => $result];

        $next = function ($ctx) {
            return $ctx->results;
        };

        $this->stage->handle($context, $next);

        // Le score devrait être augmenté grâce au bonus faute de frappe
        $this->assertGreaterThan(
            0.4,
            $result->score,
            'Score should be increased due to common typo bonus (germoin vs germain)'
        );
    }

    /** @test */
    public function test_common_word_bonus(): void
    {
        // Créer et sauvegarder un utilisateur
        $user = User::create([
            'name' => 'Clinique Saint-Germain',
            'email' => 'clinique@example.com',
            'type' => 'user'
        ]);

        $this->indexBuilder->indexModel($user);

        $context = $this->createMockContext($user, 'clinique', 'Clinique Saint-Germain');

        $result = new SearchResultData(
            item: $user,
            score: 0.6,
            modelType: User::class,
            matchedField: 'name',
            matchedValue: 'Clinique Saint-Germain'
        );

        $context->results = ['user_' . $user->id => $result];

        $next = function ($ctx) {
            return $ctx->results;
        };

        $this->stage->handle($context, $next);

        // Le score devrait être augmenté grâce au bonus mot commun
        $this->assertGreaterThan(
            0.6,
            $result->score,
            'Score should be increased due to common word bonus (clinique)'
        );
    }

    /** @test */
    public function test_multi_word_bonus(): void
    {
        // Créer et sauvegarder un utilisateur
        $user = User::create([
            'name' => 'Clinique Saint-Germain Paris',
            'email' => 'clinique2@example.com',
            'type' => 'user'
        ]);

        $this->indexBuilder->indexModel($user);

        $context = $this->createMockContext($user, 'saint germain', 'Clinique Saint-Germain Paris');

        $result = new SearchResultData(
            item: $user,
            score: 0.7,
            modelType: User::class,
            matchedField: 'name',
            matchedValue: 'Clinique Saint-Germain Paris'
        );

        $context->results = ['user_' . $user->id => $result];

        $next = function ($ctx) {
            return $ctx->results;
        };

        $this->stage->handle($context, $next);

        // Le score devrait être augmenté grâce au bonus multi-mots
        $this->assertGreaterThan(
            0.7,
            $result->score,
            'Score should be increased due to multi-word bonus'
        );
    }

    /** @test */
    public function test_stage_handles_empty_results(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'type' => 'user'
        ]);

        $context = $this->createMockContext($user, 'test', 'Test');
        $context->results = [];

        $next = function ($ctx) {
            return $ctx->results;
        };

        $result = $this->stage->handle($context, $next);

        $this->assertEmpty($result);
    }

    /** @test */
    public function test_stage_filters_by_min_score(): void
    {
        $user = User::create([
            'name' => 'XYZ ABC Clinic', // Nom sans rapport avec la requête
            'email' => 'test@example.com',
            'type' => 'user'
        ]);

        $this->indexBuilder->indexModel($user);

        // Context avec minScore élevé et une requête SANS RAPPORT
        // "pqrst" n'a aucune similarité avec "XYZ ABC Clinic"
        $context = $this->createMockContext($user, 'pqrst', 'XYZ ABC Clinic');
        $context->options = new SearchOptionsData(minScore: 0.9);

        // Score initial très bas car pas de correspondance
        $result = new SearchResultData(
            item: $user,
            score: 0.1, // Score TRÈS bas - aucune correspondance
            modelType: User::class,
            matchedField: 'name',
            matchedValue: 'XYZ ABC Clinic'
        );

        $context->results = ['user_' . $user->id => $result];

        $next = function ($ctx) {
            return $ctx->results;
        };

        $this->stage->handle($context, $next);

        // Le résultat devrait être filtré (score < minScore)
        $this->assertEmpty(
            $context->results,
            'Result should be filtered out when score < minScore'
        );
    }

    private function createMockContext(User $user, string $query, string $targetValue): SearchContext
    {
        // Récupérer les données d'index réelles depuis la base
        $indexEntries = FuzzyIndex::where('indexable_type', User::class)
            ->where('indexable_id', $user->id)
            ->get();

        $wordIndex = [];
        $itemMap = [];

        foreach ($indexEntries as $entry) {
            // Ajouter à wordIndex
            foreach ($entry->words as $word) {
                if (strlen($word) >= 2) {
                    if (!isset($wordIndex[$word])) {
                        $wordIndex[$word] = [];
                    }

                    $wordIndex[$word][] = [
                        'indexable_type' => $entry->indexable_type,
                        'indexable_id' => $entry->indexable_id,
                        'field' => $entry->field,
                        'original_value' => $entry->original_value,
                        'normalized_words' => $entry->words,
                        'weight' => $entry->weight,
                    ];
                }
            }

            // Ajouter à itemMap
            $key = $entry->indexable_type . '_' . $entry->indexable_id;
            if (!isset($itemMap[$key])) {
                $itemMap[$key] = [
                    'indexable_type' => $entry->indexable_type,
                    'indexable_id' => $entry->indexable_id,
                ];
            }
        }

        $indexData = [
            'wordIndex' => $wordIndex,
            'itemMap' => $itemMap,
        ];

        // Normaliser la requête pour obtenir les mots
        $normalizedQuery = $this->normalizer->normalizeQuery($query);
        $queryWords = $this->normalizer->splitIntoWords($normalizedQuery);

        $context = new SearchContext(
            modelClass: User::class,
            query: $query,
            options: new SearchOptionsData(),
            normalizer: $this->normalizer,
            similarityCalculator: $this->similarityCalculator,
            indexBuilder: $this->indexBuilder,
            indexData: $indexData
        );

        // Définir manuellement les propriétés nécessaires
        $context->normalizedQuery = $normalizedQuery;
        $context->queryWords = $queryWords;
        $context->hasMultipleWords = count($queryWords) > 1;

        return $context;
    }
}
