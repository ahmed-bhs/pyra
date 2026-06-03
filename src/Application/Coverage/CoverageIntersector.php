<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Coverage;

use AhmedBhs\Pyra\Domain\Coverage\CoverageReport;
use AhmedBhs\Pyra\Domain\Diff\ChangedFile;

final class CoverageIntersector
{
    public function intersect(ChangedFile $changedFile, CoverageReport $coverageReport): ?CoverageIntersection
    {
        $fileCoverage = $coverageReport->forPathSuffix($changedFile->path);
        if (null === $fileCoverage) {
            return null;
        }

        $executable = 0;
        $covered = 0;
        $uncoveredLines = [];
        foreach ($changedFile->addedLineRanges as $lineRange) {
            foreach ($lineRange->lines() as $line) {
                if (!$fileCoverage->isExecutable($line)) {
                    continue;
                }

                ++$executable;
                if ($fileCoverage->isCovered($line)) {
                    ++$covered;
                } else {
                    $uncoveredLines[] = $line;
                }
            }
        }

        if (0 === $executable) {
            return null;
        }

        return new CoverageIntersection(
            percentage: round($covered * 100 / $executable, 1),
            uncoveredLines: $uncoveredLines,
            executableChangedLines: $executable,
        );
    }
}
