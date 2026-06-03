<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Diff;

use AhmedBhs\Pyra\Application\FileInspector;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\TestLevel;
use Symfony\Component\Finder\Finder;

/**
 * Builds, once, an index of which fully-qualified class names each test level
 * references (across the WHOLE configured test suite, not only changed tests),
 * then answers which levels reference a given class. Scanning all tests is what
 * kills the false positive "an existing, unchanged test already covers it".
 */
final class SourceTestMapper
{
    /** @var array<string, list<TestLevel>>|null fqcn => levels referencing it */
    private ?array $index = null;

    public function __construct(
        private readonly PyramidConfig $pyramidConfig,
        private readonly FileInspector $fileInspector = new FileInspector(),
    ) {
    }

    /**
     * @return list<TestLevel>
     */
    public function levelsReferencing(string $fqcn): array
    {
        $this->index ??= $this->buildIndex();
        $normalized = ltrim($fqcn, '\\');

        return $this->index[$normalized] ?? [];
    }

    /**
     * @return array<string, list<TestLevel>>
     */
    private function buildIndex(): array
    {
        $index = [];
        foreach ($this->pyramidConfig->levels as $levelThreshold) {
            $existingPaths = array_values(array_filter($levelThreshold->paths, 'is_dir'));
            if ([] === $existingPaths) {
                continue;
            }

            $finder = (new Finder())->files()->in($existingPaths)->name('*.php');
            foreach ($finder as $file) {
                $fileInspectionResult = $this->fileInspector->inspect($file->getRealPath() ?: $file->getPathname(), $levelThreshold);
                if ($fileInspectionResult->tests <= 0) {
                    continue;
                }

                foreach ($fileInspectionResult->dependencies as $dependency) {
                    $normalized = ltrim($dependency, '\\');
                    $index[$normalized] ??= [];
                    if (!\in_array($levelThreshold->level, $index[$normalized], true)) {
                        $index[$normalized][] = $levelThreshold->level;
                    }
                }
            }
        }

        return $index;
    }
}
