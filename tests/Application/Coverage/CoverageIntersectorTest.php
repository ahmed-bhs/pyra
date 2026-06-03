<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application\Coverage;

use AhmedBhs\Pyra\Application\Coverage\CoverageIntersector;
use AhmedBhs\Pyra\Domain\Diff\ChangedFile;
use AhmedBhs\Pyra\Domain\Diff\ChangeStatus;
use AhmedBhs\Pyra\Domain\Diff\LineRange;
use AhmedBhs\Pyra\Infrastructure\Coverage\CoverageReportParser;
use PHPUnit\Framework\TestCase;

final class CoverageIntersectorTest extends TestCase
{
    public function testCalculeLaCouvertureDesLignesChangees(): void
    {
        $coverageReport = (new CoverageReportParser())->parse(__DIR__.'/../../Fixtures/coverage/clover.xml');

        $changedFile = new ChangedFile('src/Domain/Order.php', ChangeStatus::MODIFIED, [new LineRange(9, 11)]);

        $coverageIntersection = (new CoverageIntersector())->intersect($changedFile, $coverageReport);

        self::assertNotNull($coverageIntersection);
        self::assertSame(3, $coverageIntersection->executableChangedLines);
        self::assertEqualsWithDelta(33.3, $coverageIntersection->percentage, 0.1);
        self::assertSame([10, 11], $coverageIntersection->uncoveredLines);
    }
}
