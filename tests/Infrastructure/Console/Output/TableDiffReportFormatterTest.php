<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Infrastructure\Console\Output;

use AhmedBhs\Pyra\Domain\Diff\ClassTestStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use AhmedBhs\Pyra\Domain\TestLevel;
use AhmedBhs\Pyra\Infrastructure\Console\Output\TableDiffReportFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class TableDiffReportFormatterTest extends TestCase
{
    public function testShowsTheShortClassNameNotTheFqcn(): void
    {
        $output = $this->format(new ClassTestStatus(
            'src/Domain/Order.php',
            'Acme\Domain\Very\Deep\Order',
            [TestLevel::UNIT],
            [TestLevel::UNIT],
        ));

        self::assertStringContainsString('Order', $output);
        self::assertStringNotContainsString('Acme\Domain\Very\Deep\Order', $output);
    }

    public function testMissingStatusKeepsTheLevelOnTheSameToken(): void
    {
        $output = $this->format(new ClassTestStatus(
            'src/Domain/Order.php',
            'Acme\Domain\Order',
            [TestLevel::UNIT],
            [],
        ));

        self::assertStringContainsString('missing: unit', $output);
    }

    public function testOkStatusWhenEveryExpectedLevelIsCovered(): void
    {
        $output = $this->format(new ClassTestStatus(
            'src/Domain/Order.php',
            'Acme\Domain\Order',
            [TestLevel::UNIT],
            [TestLevel::UNIT],
        ));

        self::assertStringContainsString('ok', $output);
    }

    private function format(ClassTestStatus $status): string
    {
        $bufferedOutput = new BufferedOutput();
        (new TableDiffReportFormatter())->format(new DiffReport([$status], [], []), $bufferedOutput);

        return $bufferedOutput->fetch();
    }
}
