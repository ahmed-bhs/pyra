<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console\Output;

use AhmedBhs\Pyra\Domain\Diff\ClassTestStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use AhmedBhs\Pyra\Domain\ImpurityFinding;
use AhmedBhs\Pyra\Domain\TestLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Emits GitHub Actions workflow commands so violations show up as inline
 * annotations on the pull request.
 *
 * @see https://docs.github.com/actions/using-workflows/workflow-commands-for-github-actions
 */
final class GithubDiffReportFormatter implements DiffReportFormatter
{
    public function name(): string
    {
        return 'github';
    }

    public function format(DiffReport $diffReport, OutputInterface $output): void
    {
        foreach ($diffReport->classStatuses as $classTestStatus) {
            $missing = array_map(static fn (TestLevel $level): string => $level->value, $classTestStatus->missingLevels());
            if ([] === $missing) {
                continue;
            }

            $output->writeln(\sprintf(
                '::warning file=%s::Missing %s test(s) for %s',
                $this->escapeProperty($classTestStatus->sourceFile),
                implode(', ', $missing),
                $this->escapeData($classTestStatus->className ?? $classTestStatus->sourceFile),
            ));
        }

        foreach ($diffReport->impurities as $impurity) {
            $output->writeln(\sprintf(
                '::warning file=%s::Unit test depends on %s and behaves like a higher-level test',
                $this->escapeProperty($impurity->file),
                $this->escapeData($impurity->offendingSymbol),
            ));
        }

        foreach ($diffReport->coverageWarnings as $coverageWarning) {
            $output->writeln('::warning ::'.$this->escapeData($coverageWarning));
        }
    }

    private function escapeData(string $value): string
    {
        return str_replace(["%", "\r", "\n"], ['%25', '%0D', '%0A'], $value);
    }

    private function escapeProperty(string $value): string
    {
        return str_replace(['%', "\r", "\n", ':', ','], ['%25', '%0D', '%0A', '%3A', '%2C'], $value);
    }
}
