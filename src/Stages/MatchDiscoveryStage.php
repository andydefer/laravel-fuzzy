<?php

declare(strict_types=1);

namespace Fuzzy\Stages;

use Fuzzy\Contracts\SearchContextInterface;
use Fuzzy\Contracts\StageInterface;
use Fuzzy\Config\MatchDiscoveryConfig;
use Fuzzy\Enums\StageType;
use Fuzzy\Stages\MatchDiscoveryStage\MatchFinder;
use Closure;

class MatchDiscoveryStage implements StageInterface
{
    private const PRIORITY = 75;

    public function __construct(
        private ?MatchDiscoveryConfig $config = null,
        private ?MatchFinder $finder = null
    ) {
        $this->config ??= MatchDiscoveryConfig::fromConfig();
        $this->finder ??= new MatchFinder($this->config);
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getType(): StageType
    {
        return StageType::MATCH_DISCOVERY;
    }

    public function handle(SearchContextInterface $context, Closure $next): mixed
    {
        if ($context->query->isEmpty()) {
            return $next($context);
        }

        $hasExactMatches = $this->finder->discoverExactMatches($context);

        if (!$context->hasMultipleWords() && $hasExactMatches && $context->options->fuzzy) {
            $this->handleSingleWordWithExactMatch($context);
        } else {
            $this->finder->discoverWordMatches($context);

            if ($context->options->fuzzy) {
                $this->finder->discoverFuzzyMatchesOptimized($context);
            }

            if ($context->hasMultipleWords()) {
                $this->finder->discoverMultiWordMatches($context);
            }
        }

        return $next($context);
    }

    private function handleSingleWordWithExactMatch(SearchContextInterface $context): void
    {
        $normalizedQuery = $context->getNormalizedQuery();
        $wordIndex = $context->getWordIndex();

        if (count($wordIndex) < $this->config->getSmallIndexThreshold()) {
            $this->finder->discoverVeryCloseMatches($context, $normalizedQuery, $wordIndex);
        } else {
            $this->finder->discoverCloseMatchesOptimized($context, $normalizedQuery);
        }
    }
}
