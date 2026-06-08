<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Diff;

use AhmedBhs\Pyra\Application\Coverage\CoverageIntersector;
use AhmedBhs\Pyra\Application\FileInspector;
use AhmedBhs\Pyra\Domain\Coverage\CoverageReport;
use AhmedBhs\Pyra\Domain\Diff\ChangedFile;
use AhmedBhs\Pyra\Domain\Diff\ClassTestStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\TestLevel;

final class DiffAnalyzer
{
    public function __construct(
        private readonly PyramidConfig $pyramidConfig,
        private readonly string $projectRoot,
        private readonly FileClassifier $fileClassifier,
        private readonly SourceTestMapper $sourceTestMapper,
        private readonly ClassNameExtractor $classNameExtractor = new ClassNameExtractor(),
        private readonly FileInspector $fileInspector = new FileInspector(),
        private readonly CoverageIntersector $coverageIntersector = new CoverageIntersector(),
    ) {
    }

    /**
     * @param list<ChangedFile> $changedFiles
     */
    public function analyze(array $changedFiles, ?CoverageReport $coverageReport = null): DiffReport
    {
        $diffConfig = $this->pyramidConfig->diff;
        if (null === $diffConfig) {
            return new DiffReport([], [], []);
        }

        $classStatuses = [];
        $gateViolations = [];
        $impurities = [];
        $coverageWarnings = [];

        foreach ($changedFiles as $changedFile) {
            if ($changedFile->isDeletion() || $diffConfig->isIgnored($changedFile->path)) {
                continue;
            }

            $testLevel = $this->fileClassifier->testLevelFor($changedFile->path);
            if (null !== $testLevel) {
                $impurity = $this->purityOfChangedTest($changedFile, $testLevel);
                if (null !== $impurity) {
                    $impurities[] = $impurity;
                }

                continue;
            }

            $sourceArea = $diffConfig->sourceAreaFor($changedFile->path);
            if (null === $sourceArea) {
                continue;
            }

            $classStatus = $this->statusForChangedSource($changedFile, $sourceArea->expectedLevels, $coverageReport);
            if (null === $classStatus) {
                continue;
            }
            $classStatuses[] = $classStatus;

            foreach ($classStatus->missingLevels() as $missingLevel) {
                $gateViolations[] = \sprintf(
                    'Changed source "%s" expects a %s test but none references %s.',
                    $changedFile->path,
                    $missingLevel->value,
                    $classStatus->className ?? $changedFile->path,
                );
            }

            if (null !== $classStatus->changedLineCoverage && $classStatus->changedLineCoverage < 100.0) {
                $coverageWarnings[] = \sprintf(
                    'Changed lines of "%s" are %.1f%% covered.',
                    $changedFile->path,
                    $classStatus->changedLineCoverage,
                );
            }
        }

        return new DiffReport($classStatuses, $gateViolations, $impurities, $coverageWarnings);
    }

    /**
     * @param list<TestLevel> $expectedLevels
     */
    private function statusForChangedSource(ChangedFile $changedFile, array $expectedLevels, ?CoverageReport $coverageReport): ?ClassTestStatus
    {
        $absolutePath = $this->projectRoot.\DIRECTORY_SEPARATOR.$changedFile->path;
        $classNames = is_file($absolutePath) ? $this->classNameExtractor->extractTestable((string) file_get_contents($absolutePath)) : [];

        if ([] === $classNames) {
            return null;
        }

        $className = $classNames[0] ?? null;

        $coveredLevels = [];
        foreach ($classNames as $fqcn) {
            foreach ($this->sourceTestMapper->levelsReferencing($fqcn) as $level) {
                if (!\in_array($level, $coveredLevels, true)) {
                    $coveredLevels[] = $level;
                }
            }
        }

        $changedLineCoverage = null;
        if (null !== $coverageReport) {
            $coverageIntersection = $this->coverageIntersector->intersect($changedFile, $coverageReport);
            $changedLineCoverage = $coverageIntersection?->percentage;
        }

        return new ClassTestStatus($changedFile->path, $className, $expectedLevels, $coveredLevels, $changedLineCoverage);
    }

    private function purityOfChangedTest(ChangedFile $changedFile, TestLevel $testLevel): ?\AhmedBhs\Pyra\Domain\ImpurityFinding
    {
        $levelThreshold = $this->fileClassifier->levelThresholdFor($testLevel);
        if (null === $levelThreshold) {
            return null;
        }

        $absolutePath = $this->projectRoot.\DIRECTORY_SEPARATOR.$changedFile->path;

        return $this->fileInspector->inspect($absolutePath, $levelThreshold)->impurity;
    }
}
