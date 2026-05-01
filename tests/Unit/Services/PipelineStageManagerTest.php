<?php

declare(strict_types=1);

namespace Fuzzy\Tests\Unit\Services;

use Fuzzy\Contracts\StageInterface;
use Fuzzy\Exceptions\DuplicateStageException;
use Fuzzy\Services\PipelineStageManager;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\RelevanceScoringStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;
use Fuzzy\Tests\Fixtures\CustomStage;
use Fuzzy\Tests\Fixtures\CustomStage2;
use Fuzzy\Tests\TestCase;
use InvalidArgumentException;
use stdClass;

final class PipelineStageManagerTest extends TestCase
{
    private PipelineStageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PipelineStageManager($this->app);
    }

    /**
     * Test that getMergedStages returns only core stages when no custom stages.
     */
    public function test_get_merged_stages_returns_only_core_stages(): void
    {
        config(['fuzzy.pipeline' => []]);

        $stages = $this->manager->getMergedStages();

        $expected = [
            NormalizeQueryStage::class,
            MatchDiscoveryStage::class,
            ScoringStage::class,
            RelevanceScoringStage::class,
            SortAndLimitStage::class,
        ];

        $this->assertEquals($expected, $stages);
    }

    /**
     * Test that getMergedStages prepends custom stages before core stages.
     */
    public function test_get_merged_stages_prepends_custom_stages(): void
    {
        $customStage = CustomStage::class;
        config(['fuzzy.pipeline' => [$customStage]]);

        $stages = $this->manager->getMergedStages();

        $this->assertCount(6, $stages);
        $this->assertEquals($customStage, $stages[0]);
    }

    /**
     * Test that getMergedStages validates custom stages.
     */
    public function test_get_merged_stages_validates_custom_stages(): void
    {
        config(['fuzzy.pipeline' => ['Invalid\\Stage\\Class']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pipeline stage "Invalid\\Stage\\Class" does not exist');

        $this->manager->getMergedStages();
    }

    /**
     * Test that getMergedStages validates stage interface implementation.
     */
    public function test_get_merged_stages_validates_stage_interface(): void
    {
        config(['fuzzy.pipeline' => [stdClass::class]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must implement/');

        $this->manager->getMergedStages();
    }

    /**
     * Test that duplicate custom stages throw DuplicateStageException.
     */
    public function test_duplicate_custom_stages_throws_exception(): void
    {
        $customStage = CustomStage::class;
        config(['fuzzy.pipeline' => [$customStage, $customStage]]);

        $this->expectException(DuplicateStageException::class);
        $this->expectExceptionMessage('Duplicate pipeline stage "' . $customStage . '" detected at position 2');

        $this->manager->getMergedStages();
    }

    /**
     * Test that custom stage conflicting with core stage throws exception.
     */
    public function test_custom_stage_conflicting_with_core_throws_exception(): void
    {
        config(['fuzzy.pipeline' => [NormalizeQueryStage::class]]);

        $this->expectException(DuplicateStageException::class);
        $this->expectExceptionMessage('cannot be added as a custom stage because it is already part of the core pipeline');

        $this->manager->getMergedStages();
    }

    /**
     * Test that createStageInstances creates instances of stage classes.
     */
    public function test_create_stage_instances_creates_instances(): void
    {
        $stageClasses = [CustomStage::class, CustomStage::class];
        $instances = $this->manager->createStageInstances($stageClasses);

        $this->assertCount(2, $instances);
        $this->assertInstanceOf(CustomStage::class, $instances[0]);
        $this->assertInstanceOf(CustomStage::class, $instances[1]);
    }

    /**
     * Test that createStageInstances with empty array returns empty array.
     */
    public function test_create_stage_instances_with_empty_array(): void
    {
        $instances = $this->manager->createStageInstances([]);

        $this->assertIsArray($instances);
        $this->assertEmpty($instances);
    }

    /**
     * Test that multiple custom stages are preserved in order.
     */
    public function test_multiple_custom_stages_preserved_in_order(): void
    {
        // Create two different custom stage classes
        $stage1 = CustomStage::class;
        $stage2 = CustomStage2::class;  // Different class

        config(['fuzzy.pipeline' => [$stage1, $stage2]]);

        $stages = $this->manager->getMergedStages();

        $this->assertEquals($stage1, $stages[0]);
        $this->assertEquals($stage2, $stages[1]);
        $this->assertCount(7, $stages); // 2 custom + 5 core = 7
    }

    /**
     * Test that three identical duplicate stages are detected.
     */
    public function test_triple_duplicate_custom_stages_throws_exception(): void
    {
        $customStage = CustomStage::class;
        config(['fuzzy.pipeline' => [$customStage, $customStage, $customStage]]);

        $this->expectException(DuplicateStageException::class);
        $this->expectExceptionMessage('detected at position 2');

        $this->manager->getMergedStages();
    }
}
