<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console\Output;

use AhmedBhs\Pyra\Domain\Diff\ClassTestStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use AhmedBhs\Pyra\Domain\ImpurityFinding;
use AhmedBhs\Pyra\Domain\TestLevel;
use Symfony\Component\Console\Output\OutputInterface;

final class JsonDiffReportFormatter implements DiffReportFormatter
{
    public function name(): string
    {
        return 'json';
    }

    public function format(DiffReport $diffReport, OutputInterface $output): void
    {
        $payload = [
            'gateViolations' => array_values($diffReport->gateViolations),
            'coverageWarnings' => array_values($diffReport->coverageWarnings),
            'impurities' => array_map(
                static fn (ImpurityFinding $impurity): array => [
                    'file' => $impurity->file,
                    'offendingSymbol' => $impurity->offendingSymbol,
                ],
                $diffReport->impurities,
            ),
            'classes' => array_map(
                static fn (ClassTestStatus $classTestStatus): array => [
                    'sourceFile' => $classTestStatus->sourceFile,
                    'className' => $classTestStatus->className,
                    'expected' => array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->expectedLevels),
                    'covered' => array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->coveredLevels),
                    'missing' => array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->missingLevels()),
                    'changedLineCoverage' => $classTestStatus->changedLineCoverage,
                ],
                $diffReport->classStatuses,
            ),
        ];

        $output->writeln((string) json_encode($payload, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
    }
}
