<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Infrastructure\Coverage;

use AhmedBhs\Pyra\Infrastructure\Coverage\CoverageReportParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoverageReportParserTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function reportProvider(): iterable
    {
        yield 'clover' => [__DIR__.'/../../Fixtures/coverage/clover.xml'];
        yield 'cobertura' => [__DIR__.'/../../Fixtures/coverage/cobertura.xml'];
    }

    #[DataProvider('reportProvider')]
    public function testParseLesLignesCouvertesEtNonCouvertes(string $fixture): void
    {
        $coverageReport = (new CoverageReportParser())->parse($fixture);

        $fileCoverage = $coverageReport->forPathSuffix('src/Domain/Order.php');

        self::assertNotNull($fileCoverage);
        self::assertTrue($fileCoverage->isCovered(9));
        self::assertFalse($fileCoverage->isCovered(10));
        self::assertTrue($fileCoverage->isExecutable(11));
    }

    public function testEchoueQuandLeFichierEstAbsent(): void
    {
        $this->expectException(\RuntimeException::class);

        (new CoverageReportParser())->parse(__DIR__.'/nope.xml');
    }
}
