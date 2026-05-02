<?php

declare(strict_types=1);

namespace Fuzzy\Services;

use Fuzzy\Contracts\ModelDiscoveryInterface;
use Fuzzy\Contracts\MustFuzzySearch;
use Fuzzy\Exceptions\ModelNotSearchableException;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Service for discovering models that implement MustFuzzySearch interface.
 *
 * This service scans configured directories to find all model classes that
 * implement the MustFuzzySearch interface. No configuration file is needed.
 *
 * @package Fuzzy\Services
 */
class ModelDiscoveryService implements ModelDiscoveryInterface
{
    private const EXTRACT_NAMESPACE_REGEX = '/namespace\s+(.+?);/s';
    private const EXTRACT_CLASS_REGEX = '/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{)/';

    /**
     * Get all searchable models.
     *
     * Discovers all models implementing MustFuzzySearch interface by scanning
     * the configured directories.
     *
     * @return array<int, string> Array of fully qualified model class names
     */
    public function getSearchableModels(): array
    {
        return $this->discoverSearchableModels();
    }

    /**
     * Check if a model implements the MustFuzzySearch interface.
     *
     * @param string $modelClass Fully qualified model class name
     * @return bool True if model is searchable
     */
    public function isValidModel(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        return $reflection->implementsInterface(MustFuzzySearch::class);
    }

    /**
     * Validate that a model implements MustFuzzySearch interface.
     *
     * @param string $modelClass Fully qualified model class name
     * @throws ModelNotSearchableException If model does not implement MustFuzzySearch
     */
    public function validateModel(string $modelClass): void
    {
        if (!$this->isValidModel($modelClass)) {
            throw new ModelNotSearchableException(
                sprintf('Model %s must implement %s', $modelClass, MustFuzzySearch::class)
            );
        }
    }

    /**
     * Discover models implementing MustFuzzySearch interface.
     *
     * Scans configured directories for PHP files and extracts model classes.
     *
     * @return array<int, string> Array of discovered model class names
     */
    private function discoverSearchableModels(): array
    {
        $models = [];
        $finder = new Finder();

        $paths = $this->getDiscoveryPaths();

        $finder->files()
            ->in($paths)
            ->name('*.php');

        foreach ($finder as $file) {
            $modelClass = $this->extractClassNameFromFile($file->getRealPath());

            if ($modelClass && $this->isValidModel($modelClass)) {
                $models[] = $modelClass;
            }
        }

        return array_unique($models);
    }

    /**
     * Get paths for model discovery.
     *
     * Scans the app/Models directory and the test fixtures directory.
     *
     * @return array<int, string> Array of directory paths
     */
    private function getDiscoveryPaths(): array
    {
        return [
            app_path('Models'),
            dirname(__DIR__, 2) . '/tests/Fixtures',
        ];
    }

    /**
     * Extract fully qualified class name from a file.
     *
     * @param string $filePath Path to PHP file
     * @return string|null Fully qualified class name or null if not found
     */
    private function extractClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        $namespace = '';
        $className = '';

        if (preg_match(self::EXTRACT_NAMESPACE_REGEX, $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match(self::EXTRACT_CLASS_REGEX, $content, $matches)) {
            $className = $matches[1];
        }

        if ($namespace && $className) {
            $fullClassName = $namespace . '\\' . $className;
            return class_exists($fullClassName) ? $fullClassName : null;
        }

        return null;
    }
}
