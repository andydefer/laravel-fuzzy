<?php

declare(strict_types=1);

namespace Fuzzy\Config;

use Fuzzy\Contracts\ConfigInterface;

/**
 * Configuration for scoring coverage bonuses in the search engine.
 * 
 * Manages threshold values and bonus multipliers for query word coverage,
 * allowing fine-tuned control over how partial matches are rewarded.
 */
final class CoverageBonusConfig implements ConfigInterface
{
    /**
     * @param float $fullCoverageThreshold Minimum ratio (0-1) to apply full bonus (default: 0.75)
     * @param float $highCoverageThreshold Minimum ratio (0-1) to apply high bonus (default: 0.50)
     * @param float $fullCoverageBonus Bonus score added when full coverage threshold is met (default: 0.30)
     * @param float $highCoverageBonus Bonus score added when high coverage threshold is met (default: 0.15)
     */
    private function __construct(
        private readonly float $fullCoverageThreshold,
        private readonly float $highCoverageThreshold,
        private readonly float $fullCoverageBonus,
        private readonly float $highCoverageBonus
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(): self
    {
        $config = config('fuzzy.scoring.coverage_bonus', []);

        return new self(
            fullCoverageThreshold: (float) ($config['full_threshold'] ?? 0.75),
            highCoverageThreshold: (float) ($config['high_threshold'] ?? 0.50),
            fullCoverageBonus: (float) ($config['full_bonus'] ?? 0.30),
            highCoverageBonus: (float) ($config['high_bonus'] ?? 0.15),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function createDefault(): self
    {
        return new self(
            fullCoverageThreshold: 0.75,
            highCoverageThreshold: 0.50,
            fullCoverageBonus: 0.30,
            highCoverageBonus: 0.15,
        );
    }

    /**
     * Get the full coverage threshold value.
     *
     * @return float
     */
    public function getFullCoverageThreshold(): float
    {
        return $this->fullCoverageThreshold;
    }

    /**
     * Get the high coverage threshold value.
     *
     * @return float
     */
    public function getHighCoverageThreshold(): float
    {
        return $this->highCoverageThreshold;
    }

    /**
     * Get the full coverage bonus value.
     *
     * @return float
     */
    public function getFullCoverageBonus(): float
    {
        return $this->fullCoverageBonus;
    }

    /**
     * Get the high coverage bonus value.
     *
     * @return float
     */
    public function getHighCoverageBonus(): float
    {
        return $this->highCoverageBonus;
    }
}
