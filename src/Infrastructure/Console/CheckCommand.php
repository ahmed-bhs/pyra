<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console;

use AhmedBhs\Pyra\Application\PyramidAnalyzer;
use AhmedBhs\Pyra\Domain\TestLevel;
use AhmedBhs\Pyra\Infrastructure\YamlConfigLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'check', description: 'Validate the test pyramid shape and the purity of unit tests.')]
final class CheckCommand extends Command
{
    public function __construct(
        private readonly YamlConfigLoader $yamlConfigLoader = new YamlConfigLoader(),
        private readonly PyramidAnalyzer $pyramidAnalyzer = new PyramidAnalyzer(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration file', 'pyra.yaml')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Exit with a non-zero status when violations are found');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $configPath = (string) $input->getOption('config');

        $pyramidReport = $this->pyramidAnalyzer->analyze($this->yamlConfigLoader->load($configPath));

        $rows = [];
        foreach (TestLevel::cases() as $testLevel) {
            $levelCount = $pyramidReport->counts[$testLevel->value] ?? null;
            if (null === $levelCount) {
                continue;
            }

            $rows[] = [
                ucfirst($testLevel->value),
                $levelCount->methods,
                $levelCount->files,
                \sprintf('%.1f%%', $pyramidReport->percentage($testLevel)),
                [] === $levelCount->impurities ? '-' : \sprintf('%d impure', \count($levelCount->impurities)),
            ];
        }

        $symfonyStyle->table(['Level', 'Tests', 'Files', 'Share', 'Purity'], $rows);

        if (!$pyramidReport->hasViolations()) {
            $symfonyStyle->success(\sprintf('Test pyramid looks healthy (%d tests analysed).', $pyramidReport->totalMethods));

            return Command::SUCCESS;
        }

        $symfonyStyle->section('Violations');
        $symfonyStyle->listing($pyramidReport->violations);

        if ((bool) $input->getOption('strict')) {
            $symfonyStyle->error(\sprintf('%d pyramid violation(s) found.', \count($pyramidReport->violations)));

            return Command::FAILURE;
        }

        $symfonyStyle->warning(\sprintf('%d pyramid violation(s) found (non-strict mode, exit 0).', \count($pyramidReport->violations)));

        return Command::SUCCESS;
    }
}
