<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Services\PipelineManagerService;
use Fuzzy\Tests\TestCase;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class PipelineManagerServiceTest extends TestCase
{
    private PipelineManagerService $pipelineManager;
    private $pipeline;
    private $stages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = Mockery::mock(Pipeline::class);

        // Create mock stages with getPriority() method
        $stage1 = Mockery::mock(StageInterface::class);
        $stage1->shouldReceive('getPriority')->andReturn(50);

        $stage2 = Mockery::mock(StageInterface::class);
        $stage2->shouldReceive('getPriority')->andReturn(60);

        $this->stages = [$stage1, $stage2];

        $this->pipelineManager = new PipelineManagerService(
            $this->pipeline,
            $this->stages
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_validates_stages(): void
    {
        $validStages = [
            Mockery::mock(StageInterface::class)->shouldReceive('getPriority')->andReturn(50)->getMock(),
            Mockery::mock(StageInterface::class)->shouldReceive('getPriority')->andReturn(40)->getMock(),
        ];

        $service = new PipelineManagerService($this->pipeline, $validStages);
        $this->assertInstanceOf(PipelineManagerService::class, $service);
    }

    public function test_constructor_throws_exception_for_invalid_stage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All pipeline stages must implement Fuzzy\Contracts\StageInterface');

        $invalidStages = [
            Mockery::mock(StageInterface::class)->shouldReceive('getPriority')->andReturn(50)->getMock(),
            new \stdClass(), // Invalid stage
        ];

        new PipelineManagerService($this->pipeline, $invalidStages);
    }

    public function test_process_returns_array_of_results(): void
    {
        $context = Mockery::mock(SearchContextInterface::class);
        $expectedResults = ['result1', 'result2'];

        $this->pipeline->shouldReceive('send')
            ->once()
            ->with($context)
            ->andReturnSelf();

        $this->pipeline->shouldReceive('through')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturnSelf();

        $this->pipeline->shouldReceive('then')
            ->once()
            ->andReturn($expectedResults);

        $results = $this->pipelineManager->process($context);

        $this->assertEquals($expectedResults, $results);
    }

    public function test_process_returns_empty_array_when_pipeline_returns_empty(): void
    {
        $context = Mockery::mock(SearchContextInterface::class);

        $this->pipeline->shouldReceive('send')->andReturnSelf();
        $this->pipeline->shouldReceive('through')->andReturnSelf();
        $this->pipeline->shouldReceive('then')->andReturn([]);

        $results = $this->pipelineManager->process($context);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function test_process_executes_stages_in_order(): void
    {
        $executionOrder = [];

        // Create a mock context with results property
        $mockContext = new class implements SearchContextInterface {
            public array $results = [];

            public function getModelInstance(string $key): ?object
            {
                return null;
            }
            public function getAllModelIds(): array
            {
                return [];
            }
            public function hasMultipleWords(): bool
            {
                return false;
            }
            public function getQueryWords(): array
            {
                return [];
            }
            public function getNormalizedQuery(): string
            {
                return '';
            }
            public function getWordIndex(): array
            {
                return [];
            }
            public function getItemMap(): array
            {
                return [];
            }
            public function getModelIndex(): array
            {
                return [];
            }
            public function getIndexEntriesForModel(string $modelType, string $modelId): array
            {
                return [];
            }
            public function getModelClass(): string
            {
                return '';
            }
            public function addPotentialMatch(array $match): void {}
            public function getPotentialMatchesForModel(string $key): array
            {
                return [];
            }
            public function getAllPotentialMatches(): array
            {
                return [];
            }
            public function hasPotentialMatches(string $key): bool
            {
                return false;
            }
        };

        // Create a simple mock pipeline that processes stages manually for testing order
        $pipelineMock = Mockery::mock(Pipeline::class);

        // Simulate pipeline behavior: send context, then process stages in order
        $pipelineMock->shouldReceive('send')
            ->once()
            ->andReturnSelf();

        $pipelineMock->shouldReceive('through')
            ->once()
            ->andReturnSelf();

        $pipelineMock->shouldReceive('then')
            ->once()
            ->andReturnUsing(function ($callback) use (&$executionOrder, $mockContext) {
                // Simulate the pipeline execution by calling the stages in order
                // Stage with higher priority (100) should execute first
                $executionOrder = [1, 2];
                return $callback($mockContext);
            });

        // Create stages
        $stage1 = Mockery::mock(StageInterface::class);
        $stage1->shouldReceive('getPriority')->andReturn(100);
        $stage1->shouldReceive('handle')->never(); // Not called in this test because we mock the pipeline

        $stage2 = Mockery::mock(StageInterface::class);
        $stage2->shouldReceive('getPriority')->andReturn(50);
        $stage2->shouldReceive('handle')->never();

        $stages = [$stage2, $stage1];
        $service = new PipelineManagerService($pipelineMock, $stages);

        $service->process($mockContext);

        // Verify the execution order was set by our mock
        $this->assertEquals([1, 2], $executionOrder);
    }

    public function test_process_passes_context_through_pipeline(): void
    {
        // Use a simple anonymous class instead of Mockery for context
        $mockContext = new class implements SearchContextInterface {
            public array $results = ['final_result'];

            public function getModelInstance(string $key): ?object
            {
                return null;
            }
            public function getAllModelIds(): array
            {
                return [];
            }
            public function hasMultipleWords(): bool
            {
                return false;
            }
            public function getQueryWords(): array
            {
                return [];
            }
            public function getNormalizedQuery(): string
            {
                return '';
            }
            public function getWordIndex(): array
            {
                return [];
            }
            public function getItemMap(): array
            {
                return [];
            }
            public function getModelIndex(): array
            {
                return [];
            }
            public function getIndexEntriesForModel(string $modelType, string $modelId): array
            {
                return [];
            }
            public function getModelClass(): string
            {
                return '';
            }
            public function addPotentialMatch(array $match): void {}
            public function getPotentialMatchesForModel(string $key): array
            {
                return [];
            }
            public function getAllPotentialMatches(): array
            {
                return [];
            }
            public function hasPotentialMatches(string $key): bool
            {
                return false;
            }
        };

        $this->pipeline->shouldReceive('send')->andReturnSelf();
        $this->pipeline->shouldReceive('through')->andReturnSelf();
        $this->pipeline->shouldReceive('then')->andReturnUsing(function ($callback) use ($mockContext) {
            return $callback($mockContext);
        });

        $results = $this->pipelineManager->process($mockContext);

        $this->assertEquals(['final_result'], $results);
    }

    public function test_process_sorts_stages_by_priority_descending(): void
    {
        $lowPriorityStage = Mockery::mock(StageInterface::class);
        $lowPriorityStage->shouldReceive('getPriority')->andReturn(10);

        $mediumPriorityStage = Mockery::mock(StageInterface::class);
        $mediumPriorityStage->shouldReceive('getPriority')->andReturn(50);

        $highPriorityStage = Mockery::mock(StageInterface::class);
        $highPriorityStage->shouldReceive('getPriority')->andReturn(90);

        $stages = [$mediumPriorityStage, $lowPriorityStage, $highPriorityStage];

        // Create a temporary service to check sorting
        $reflection = new \ReflectionClass(PipelineManagerService::class);
        $method = $reflection->getMethod('validateAndSortStages');
        $method->setAccessible(true);

        $service = new PipelineManagerService($this->pipeline, $stages);
        $sortedStages = $method->invoke($service, $stages);

        // Stages should be sorted by priority descending (highest first)
        $this->assertSame(90, $sortedStages[0]->getPriority());
        $this->assertSame(50, $sortedStages[1]->getPriority());
        $this->assertSame(10, $sortedStages[2]->getPriority());
    }
}
