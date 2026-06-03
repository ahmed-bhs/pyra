<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application;

use AhmedBhs\Pyra\Application\Counter\CounterRegistry;
use AhmedBhs\Pyra\Domain\ImpurityFinding;
use AhmedBhs\Pyra\Domain\LevelThreshold;

/**
 * Inspects a single test file against a level: counts its tests, extracts the
 * class symbols it depends on, and flags a forbidden dependency (a test
 * behaving like a higher-level test). Shared by the global scanner and the
 * diff analyzer so both apply the exact same per-file logic.
 */
final class FileInspector
{
    public function __construct(
        private readonly CounterRegistry $counterRegistry = new CounterRegistry(),
    ) {
    }

    public function inspect(string $filePath, LevelThreshold $levelThreshold): FileInspectionResult
    {
        $contents = is_file($filePath) ? (string) file_get_contents($filePath) : '';
        $countResult = $this->counterRegistry->get($levelThreshold->counter)->count($contents);

        $impurity = null;
        if ($countResult->tests > 0) {
            $offendingSymbol = $this->firstForbiddenDependency($countResult->dependencies, $levelThreshold->forbiddenDependencies);
            if (null !== $offendingSymbol) {
                $impurity = new ImpurityFinding($filePath, $offendingSymbol);
            }
        }

        return new FileInspectionResult($countResult->tests, $countResult->dependencies, $impurity);
    }

    /**
     * @param list<string> $dependencies
     * @param list<string> $forbidden
     */
    private function firstForbiddenDependency(array $dependencies, array $forbidden): ?string
    {
        foreach ($dependencies as $dependency) {
            $normalized = ltrim($dependency, '\\');
            foreach ($forbidden as $forbiddenPrefix) {
                $normalizedForbidden = ltrim($forbiddenPrefix, '\\');
                if ($normalized === $normalizedForbidden || str_starts_with($normalized, $normalizedForbidden.'\\')) {
                    return $normalized;
                }
            }
        }

        return null;
    }
}
