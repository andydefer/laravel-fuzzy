<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Commands;

use Fuzzy\Commands\ClearCacheCommand;
use Fuzzy\Contracts\CacheManagerInterface;
use Fuzzy\Contracts\SearchServiceInterface;
use Fuzzy\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Mockery;

final class ClearCacheCommandTest extends TestCase
{
    private $searchService;
    private $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->searchService = Mockery::mock(SearchServiceInterface::class);

        // Configure le mock pour retourner le cacheManager via getCacheManager()
        $this->searchService->shouldReceive('getCacheManager')
            ->zeroOrMoreTimes()
            ->andReturn($this->cacheManager);

        // Bind the search service in the container
        $this->app->instance(SearchServiceInterface::class, $this->searchService);

        config(['fuzzy.cache.prefix' => 'fuzzy_test:']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_clear_cache_command_requires_confirmation(): void
    {
        $this->cacheManager->shouldReceive('invalidateAll')->never();

        $this->artisan('fuzzy:clear-cache')
            ->expectsConfirmation('Are you sure you want to clear fuzzy search cache?', 'no')
            ->expectsOutput('Cache clearing cancelled.')
            ->assertExitCode(0);
    }

    public function test_clear_cache_command_with_force_skips_confirmation(): void
    {
        $this->cacheManager->shouldReceive('invalidateAll')
            ->once()
            ->andReturnNull();

        $this->artisan('fuzzy:clear-cache', ['--force' => true])
            ->expectsOutput('✓ All fuzzy search cache cleared successfully.')
            ->assertExitCode(0);
    }

    public function test_clear_cache_command_clears_all_cache(): void
    {
        $this->cacheManager->shouldReceive('invalidateAll')
            ->once()
            ->andReturnNull();

        $exitCode = Artisan::call('fuzzy:clear-cache', ['--force' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('All fuzzy search cache cleared successfully', $output);
    }

    public function test_clear_cache_for_specific_model(): void
    {
        $modelClass = 'Fuzzy\\Tests\\Fixtures\\User';

        $this->cacheManager->shouldReceive('invalidateForModel')
            ->once()
            ->with($modelClass)
            ->andReturnNull();

        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--force' => true,
            '--model' => $modelClass,
        ]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("Cache cleared for model: {$modelClass}", $output);
    }

    public function test_clear_stats_cache_only(): void
    {
        $this->cacheManager->shouldReceive('invalidateStatsCache')
            ->once()
            ->andReturnNull();

        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--force' => true,
            '--stats' => true,
        ]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Statistics cache cleared successfully', $output);
    }

    public function test_clear_cache_with_multiple_options_stats_takes_precedence(): void
    {
        $this->cacheManager->shouldReceive('invalidateStatsCache')
            ->once()
            ->andReturnNull();

        $this->cacheManager->shouldReceive('invalidateForModel')->never();
        $this->cacheManager->shouldReceive('invalidateAll')->never();

        $exitCode = Artisan::call('fuzzy:clear-cache', [
            '--force' => true,
            '--stats' => true,
            '--model' => 'SomeModel',
        ]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Statistics cache cleared successfully', $output);
    }

    public function test_clear_cache_command_respects_confirmation_when_force_false(): void
    {
        $this->cacheManager->shouldReceive('invalidateAll')->never();

        $this->artisan('fuzzy:clear-cache', ['--force' => false])
            ->expectsConfirmation('Are you sure you want to clear fuzzy search cache?', 'no')
            ->expectsOutput('Cache clearing cancelled.')
            ->assertExitCode(0);
    }

    public function test_clear_cache_command_accepts_confirmation(): void
    {
        $this->cacheManager->shouldReceive('invalidateAll')
            ->once()
            ->andReturnNull();

        $this->artisan('fuzzy:clear-cache')
            ->expectsConfirmation('Are you sure you want to clear fuzzy search cache?', 'yes')
            ->expectsOutput('✓ All fuzzy search cache cleared successfully.')
            ->assertExitCode(0);
    }
}
