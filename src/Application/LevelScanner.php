<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application;

use AhmedBhs\Pyra\Application\Counter\CounterRegistry;
use AhmedBhs\Pyra\Domain\LevelCount;
use AhmedBhs\Pyra\Domain\LevelThreshold;
use Symfony\Component\Finder\Finder;

final class LevelScanner
{
    private readonly FileInspector $fileInspector;

    public function __construct(
        private readonly CounterRegistry $counterRegistry = new CounterRegistry(),
        ?FileInspector $fileInspector = null,
    ) {
        $this->fileInspector = $fileInspector ?? new FileInspector($this->counterRegistry);
    }

    public function scan(LevelThreshold $levelThreshold): LevelCount
    {
        $existingPaths = array_values(array_filter($levelThreshold->paths, 'is_dir'));

        if ([] === $existingPaths) {
            return new LevelCount($levelThreshold->level, 0, 0);
        }

        $testCounter = $this->counterRegistry->get($levelThreshold->counter);
        $finder = (new Finder())->files()->in($existingPaths)->name($testCounter->filePattern());

        $tests = 0;
        $files = 0;
        $impurities = [];
        foreach ($finder as $file) {
            $fileInspectionResult = $this->fileInspector->inspect($file->getRealPath() ?: $file->getPathname(), $levelThreshold);

            if ($fileInspectionResult->tests <= 0) {
                continue;
            }

            ++$files;
            $tests += $fileInspectionResult->tests;

            if (null !== $fileInspectionResult->impurity) {
                $impurities[] = $fileInspectionResult->impurity;
            }
        }

        return new LevelCount($levelThreshold->level, $tests, $files, $impurities);
    }
}
