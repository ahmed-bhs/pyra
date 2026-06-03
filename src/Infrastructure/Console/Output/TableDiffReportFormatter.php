<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console\Output;

use AhmedBhs\Pyra\Domain\Diff\ClassTestStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use AhmedBhs\Pyra\Domain\ImpurityFinding;
use AhmedBhs\Pyra\Domain\TestLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TableDiffReportFormatter implements DiffReportFormatter
{
    public function name(): string
    {
        return 'table';
    }

    public function format(DiffReport $diffReport, OutputInterface $output): void
    {
        $symfonyStyle = new SymfonyStyle(new \Symfony\Component\Console\Input\ArrayInput([]), $output);

        $rows = [];
        foreach ($diffReport->classStatuses as $classTestStatus) {
            $missing = array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->missingLevels());
            $rows[] = [
                $classTestStatus->className ?? $classTestStatus->sourceFile,
                implode(', ', array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->expectedLevels)),
                implode(', ', array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->coveredLevels)) ?: '-',
                [] === $missing ? 'ok' : 'MISSING: '.implode(', ', $missing),
                null === $classTestStatus->changedLineCoverage ? 'n/a' : \sprintf('%.1f%%', $classTestStatus->changedLineCoverage),
            ];
        }

        if ([] !== $rows) {
            $symfonyStyle->table(['Changed class', 'Expected', 'Tested at', 'Status', 'Changed-line coverage'], $rows);
        }

        if ([] !== $diffReport->impurities) {
            $symfonyStyle->section('Impure changed tests');
            $symfonyStyle->listing(array_map(
                static fn (ImpurityFinding $impurity): string => \sprintf('%s depends on %s (behaves like a higher-level test).', $impurity->file, $impurity->offendingSymbol),
                $diffReport->impurities,
            ));
        }

        if ([] !== $diffReport->coverageWarnings) {
            $symfonyStyle->section('Coverage');
            $symfonyStyle->listing($diffReport->coverageWarnings);
        }

        if ($diffReport->hasGateViolations()) {
            $symfonyStyle->section('Missing tests');
            $symfonyStyle->listing($diffReport->gateViolations);
        }
    }
}
