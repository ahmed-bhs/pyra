<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console;

use AhmedBhs\Pyra\Application\Diff\ClassNameExtractor;
use AhmedBhs\Pyra\Application\Diff\DiffAnalyzer;
use AhmedBhs\Pyra\Application\Diff\FileClassifier;
use AhmedBhs\Pyra\Application\Diff\SourceTestMapper;
use AhmedBhs\Pyra\Application\FileInspector;
use AhmedBhs\Pyra\Domain\Coverage\CoverageReport;
use AhmedBhs\Pyra\Infrastructure\Coverage\CoverageReportParser;
use AhmedBhs\Pyra\Infrastructure\Git\GitDiffProvider;
use AhmedBhs\Pyra\Infrastructure\YamlConfigLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'diff', description: 'Check that changed code is backed by the expected test levels (per pull request).')]
final class DiffCommand extends Command
{
    public function __construct(
        private readonly YamlConfigLoader $yamlConfigLoader = new YamlConfigLoader(),
        private readonly CoverageReportParser $coverageReportParser = new CoverageReportParser(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration file', 'pyra.yaml')
            ->addOption('base', 'b', InputOption::VALUE_REQUIRED, 'Base git ref to diff against')
            ->addOption('coverage', null, InputOption::VALUE_REQUIRED, 'Path to a clover/cobertura coverage XML to assess changed-line coverage')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Exit with a non-zero status when a gate violation is found');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $pyramidConfig = $this->yamlConfigLoader->load((string) $input->getOption('config'));

        if (null === $pyramidConfig->diff) {
            $symfonyStyle->error('No "diff" section in the configuration. Add pyra.diff.sources to use this command.');

            return Command::FAILURE;
        }

        $projectRoot = getcwd() ?: '.';
        $baseRef = (string) ($input->getOption('base') ?? '') ?: $pyramidConfig->diff->baseRef;

        $changedFiles = (new GitDiffProvider($projectRoot))->changedFiles($baseRef);

        $coverageReport = $this->loadCoverage($input);

        $fileClassifier = new FileClassifier($pyramidConfig, $projectRoot);
        $sourceTestMapper = new SourceTestMapper($pyramidConfig);
        $diffAnalyzer = new DiffAnalyzer(
            $pyramidConfig,
            $projectRoot,
            $fileClassifier,
            $sourceTestMapper,
            new ClassNameExtractor(),
            new FileInspector(),
        );

        $diffReport = $diffAnalyzer->analyze($changedFiles, $coverageReport);

        $rows = [];
        foreach ($diffReport->classStatuses as $classTestStatus) {
            $missing = array_map(static fn ($level): string => $level->value, $classTestStatus->missingLevels());
            $rows[] = [
                $classTestStatus->className ?? $classTestStatus->sourceFile,
                implode(', ', array_map(static fn ($level): string => $level->value, $classTestStatus->expectedLevels)),
                implode(', ', array_map(static fn ($level): string => $level->value, $classTestStatus->coveredLevels)) ?: '-',
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
                static fn ($impurity): string => \sprintf('%s depends on %s (behaves like a higher-level test).', $impurity->file, $impurity->offendingSymbol),
                $diffReport->impurities,
            ));
        }

        if ([] !== $diffReport->coverageWarnings) {
            $symfonyStyle->section('Coverage');
            $symfonyStyle->listing($diffReport->coverageWarnings);
        }

        if (!$diffReport->hasGateViolations()) {
            $symfonyStyle->success('Changed code is backed by the expected test levels.');

            return Command::SUCCESS;
        }

        $symfonyStyle->section('Missing tests');
        $symfonyStyle->listing($diffReport->gateViolations);

        if ((bool) $input->getOption('strict')) {
            $symfonyStyle->error(\sprintf('%d missing-test gate violation(s).', \count($diffReport->gateViolations)));

            return Command::FAILURE;
        }

        $symfonyStyle->warning(\sprintf('%d missing-test gate violation(s) (non-strict mode, exit 0).', \count($diffReport->gateViolations)));

        return Command::SUCCESS;
    }

    private function loadCoverage(InputInterface $input): ?CoverageReport
    {
        $coveragePath = $input->getOption('coverage');
        if (null === $coveragePath) {
            return null;
        }

        return $this->coverageReportParser->parse((string) $coveragePath);
    }
}
