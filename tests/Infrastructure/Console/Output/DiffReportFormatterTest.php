<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Infrastructure\Console\Output;

use AhmedBhs\Pyra\Domain\Diff\ClassTestStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffReport;
use AhmedBhs\Pyra\Domain\ImpurityFinding;
use AhmedBhs\Pyra\Domain\TestLevel;
use AhmedBhs\Pyra\Infrastructure\Console\Output\GithubDiffReportFormatter;
use AhmedBhs\Pyra\Infrastructure\Console\Output\JsonDiffReportFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class DiffReportFormatterTest extends TestCase
{
    private function report(): DiffReport
    {
        return new DiffReport(
            classStatuses: [
                new ClassTestStatus('src/Domain/Order.php', 'Acme\Domain\Order', [TestLevel::UNIT], [], 40.0),
            ],
            gateViolations: ['Changed source "src/Domain/Order.php" expects a unit test but none references Acme\Domain\Order.'],
            impurities: [new ImpurityFinding('tests/Unit/FooTest.php', 'Doctrine\ORM\EntityManagerInterface')],
            coverageWarnings: ['Changed lines of "src/Domain/Order.php" are 40.0% covered.'],
        );
    }

    public function testJsonOutputIsStructured(): void
    {
        $bufferedOutput = new BufferedOutput();

        (new JsonDiffReportFormatter())->format($this->report(), $bufferedOutput);

        $decoded = json_decode($bufferedOutput->fetch(), true);

        self::assertIsArray($decoded);
        self::assertSame(['unit'], $decoded['classes'][0]['missing']);
        self::assertEqualsWithDelta(40.0, $decoded['classes'][0]['changedLineCoverage'], 0.01);
        self::assertCount(1, $decoded['impurities']);
    }

    public function testGithubOutputEmitsWorkflowWarnings(): void
    {
        $bufferedOutput = new BufferedOutput();

        (new GithubDiffReportFormatter())->format($this->report(), $bufferedOutput);

        $output = $bufferedOutput->fetch();

        self::assertStringContainsString('::warning file=src/Domain/Order.php::Missing unit test(s)', $output);
        self::assertStringContainsString('::warning file=tests/Unit/FooTest.php::', $output);
    }
}
