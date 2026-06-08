<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console;

use AhmedBhs\Pyra\Application\ConfigScaffolder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'init', description: 'Generate a starter pyra.yaml by sniffing the project layout.')]
final class InitCommand extends Command
{
    public function __construct(
        private readonly ConfigScaffolder $configScaffolder = new ConfigScaffolder(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path of the file to write', 'pyra.yaml')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite the file if it already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $configPath = (string) $input->getOption('config');

        if (is_file($configPath) && false === $input->getOption('force')) {
            $symfonyStyle->error(\sprintf('"%s" already exists. Use --force to overwrite.', $configPath));

            return Command::FAILURE;
        }

        file_put_contents($configPath, $this->configScaffolder->scaffold((string) getcwd()));

        $symfonyStyle->success(\sprintf('Wrote %s. Review the expected test levels before running "pyra diff".', $configPath));

        return Command::SUCCESS;
    }
}
