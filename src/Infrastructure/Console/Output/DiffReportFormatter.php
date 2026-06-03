<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console\Output;

use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use Symfony\Component\Console\Output\OutputInterface;

interface DiffReportFormatter
{
    public function name(): string;

    public function format(DiffReport $diffReport, OutputInterface $output): void;
}
