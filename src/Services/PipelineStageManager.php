<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\StageInterface;
use Fuzzy\Exceptions\DuplicateStageException;
use Fuzzy\Stages\MatchDiscoveryStage;
use Fuzzy\Stages\NormalizeQueryStage;
use Fuzzy\Stages\RelevanceScoringStage;
use Fuzzy\Stages\ScoringStage;
use Fuzzy\Stages\SortAndLimitStage;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * Manages pipeline stage registration and validation.
 */
class PipelineStageManager
{
    private const CORE_STAGES = [
        NormalizeQueryStage::class,
        MatchDiscoveryStage::class,
        ScoringStage::class,
        RelevanceScoringStage::class,
        SortAndLimitStage::class,
    ];

    public function __construct(private Application $app) {}

    /**
     * Get merged pipeline stages (custom + core).
     *
     * @throws DuplicateStageException If duplicate stages are found
     * @throws InvalidArgumentException If a stage is invalid
     */
    public function getMergedStages(): array
    {
        $customStages = config('fuzzy.pipeline', []);

        // Validate each custom stage
        foreach ($customStages as $index => $stage) {
            $this->validateStage($stage);
        }

        // Check for duplicates in custom stages
        $this->validateNoDuplicates($customStages);

        // Check for conflicts with core stages
        $this->validateNoCoreConflicts($customStages);

        return array_merge($customStages, self::CORE_STAGES);
    }

    /**
     * Create stage instances from class names.
     */
    public function createStageInstances(array $stageClasses): array
    {
        return array_map(fn($class) => $this->app->make($class), $stageClasses);
    }

    /**
     * Validate that a stage class exists and implements StageInterface.
     *
     * @throws InvalidArgumentException If the stage is invalid
     */
    private function validateStage(string $stageClass): void
    {
        if (!class_exists($stageClass)) {
            throw new InvalidArgumentException(
                sprintf('Pipeline stage "%s" does not exist', $stageClass)
            );
        }

        if (!is_subclass_of($stageClass, StageInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Pipeline stage "%s" must implement %s',
                    $stageClass,
                    StageInterface::class
                )
            );
        }
    }

    /**
     * Validate that there are no duplicate stages in custom configuration.
     *
     * @param array<int, string> $customStages
     * @throws DuplicateStageException If duplicates are found
     */
    private function validateNoDuplicates(array $customStages): void
    {
        $seen = [];

        foreach ($customStages as $index => $stage) {
            if (in_array($stage, $seen, true)) {
                throw DuplicateStageException::duplicate($stage, $index + 1);
            }
            $seen[] = $stage;
        }
    }

    /**
     * Validate that no custom stage conflicts with core stages.
     *
     * @param array<int, string> $customStages
     * @throws DuplicateStageException If a custom stage conflicts with core
     */
    private function validateNoCoreConflicts(array $customStages): void
    {
        foreach ($customStages as $stage) {
            if (in_array($stage, self::CORE_STAGES, true)) {
                throw DuplicateStageException::conflictsWithCore($stage);
            }
        }
    }
}
