<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Diff;

use AhmedBhs\Pyra\Domain\LevelThreshold;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\TestLevel;

/**
 * Classifies a changed file path as a test file (returning its level) using the
 * configured level paths. Paths are matched relative to the project root.
 */
final class FileClassifier
{
    public function __construct(
        private readonly PyramidConfig $pyramidConfig,
        private readonly string $projectRoot,
    ) {
    }

    public function testLevelFor(string $relativePath): ?TestLevel
    {
        $normalizedFile = $this->normalize($relativePath);

        foreach ($this->pyramidConfig->levels as $levelThreshold) {
            foreach ($this->relativeLevelPaths($levelThreshold) as $levelPath) {
                if (str_starts_with($normalizedFile, $levelPath.'/')) {
                    return $levelThreshold->level;
                }
            }
        }

        return null;
    }

    public function levelThresholdFor(TestLevel $testLevel): ?LevelThreshold
    {
        foreach ($this->pyramidConfig->levels as $levelThreshold) {
            if ($levelThreshold->level === $testLevel) {
                return $levelThreshold;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function relativeLevelPaths(LevelThreshold $levelThreshold): array
    {
        $root = $this->normalize($this->projectRoot);

        $paths = [];
        foreach ($levelThreshold->paths as $absolutePath) {
            $normalized = $this->normalize($absolutePath);
            $paths[] = '' !== $root && str_starts_with($normalized, $root.'/')
                ? substr($normalized, \strlen($root) + 1)
                : $normalized;
        }

        return $paths;
    }

    private function normalize(string $path): string
    {
        return rtrim(ltrim(str_replace('\\', '/', $path), '/'), '/');
    }
}
