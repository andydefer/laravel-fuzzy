<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Exceptions\ModelNotSearchableException;
use Fuzzy\Services\ModelDiscoveryService;
use Fuzzy\Tests\Fixtures\NonSearchableModel;
use Fuzzy\Tests\Fixtures\User;
use Fuzzy\Tests\TestCase;

final class ModelDiscoveryServiceTest extends TestCase
{
    private ModelDiscoveryService $modelDiscovery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelDiscovery = new ModelDiscoveryService();
    }

    public function test_is_valid_model_returns_true_for_searchable_model(): void
    {
        $result = $this->modelDiscovery->isValidModel(User::class);

        $this->assertTrue($result);
    }

    public function test_is_valid_model_returns_false_for_non_searchable_model(): void
    {
        $result = $this->modelDiscovery->isValidModel(NonSearchableModel::class);

        $this->assertFalse($result);
    }

    public function test_is_valid_model_returns_false_for_nonexistent_class(): void
    {
        $result = $this->modelDiscovery->isValidModel('NonExistentClass');

        $this->assertFalse($result);
    }

    public function test_validate_model_passes_for_searchable_model(): void
    {
        $this->modelDiscovery->validateModel(User::class);

        $this->assertTrue(true);
    }

    public function test_validate_model_throws_exception_for_non_searchable_model(): void
    {
        $this->expectException(ModelNotSearchableException::class);

        $this->modelDiscovery->validateModel(NonSearchableModel::class);
    }

    public function test_get_searchable_models_returns_array(): void
    {
        config(['fuzzy.searchable_models' => [User::class]]);

        $models = $this->modelDiscovery->getSearchableModels();

        $this->assertIsArray($models);
        $this->assertContains(User::class, $models);
    }

    public function test_get_searchable_models_filters_invalid_models(): void
    {
        config(['fuzzy.searchable_models' => [User::class, NonSearchableModel::class]]);

        $models = $this->modelDiscovery->getSearchableModels();

        $this->assertContains(User::class, $models);
        $this->assertNotContains(NonSearchableModel::class, $models);
    }

    public function test_get_searchable_models_uses_auto_discovery_when_no_config(): void
    {
        config(['fuzzy.searchable_models' => []]);

        $models = $this->modelDiscovery->getSearchableModels();

        $this->assertIsArray($models);
    }
}
