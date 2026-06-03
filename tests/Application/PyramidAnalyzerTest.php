<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application;

use AhmedBhs\Pyra\Application\PyramidAnalyzer;
use AhmedBhs\Pyra\Domain\LevelThreshold;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\TestLevel;
use PHPUnit\Framework\TestCase;

final class PyramidAnalyzerTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/../Fixtures';

    public function testDetecteUnTestUnitaireQuiDependDUnSignalDIntegration(): void
    {
        $pyramidConfig = new PyramidConfig([
            new LevelThreshold(
                level: TestLevel::UNIT,
                paths: [self::FIXTURES.'/imports/grouped'],
                forbiddenDependencies: ['Doctrine\ORM\EntityManagerInterface'],
            ),
        ]);

        $pyramidReport = (new PyramidAnalyzer())->analyze($pyramidConfig);

        self::assertTrue($pyramidReport->hasViolations());
        self::assertStringContainsString('forbidden dependency', $pyramidReport->violations[0]);
    }

    public function testNeComparePasLOrdreEntreNiveauxDeCompteursDifferents(): void
    {
        $pyramidConfig = new PyramidConfig([
            new LevelThreshold(level: TestLevel::INTEGRATION, paths: [self::FIXTURES.'/counting'], counter: 'phpunit'),
            new LevelThreshold(level: TestLevel::E2E, paths: [self::FIXTURES.'/gherkin'], counter: 'gherkin'),
        ]);

        $pyramidReport = (new PyramidAnalyzer())->analyze($pyramidConfig);

        $inverted = array_filter($pyramidReport->violations, static fn (string $violation): bool => str_contains($violation, 'Inverted pyramid'));
        self::assertSame([], $inverted, 'Ordering must not be asserted across heterogeneous counters');
    }

    public function testSignaleUnPourcentageEnDessousDuMinimum(): void
    {
        $pyramidConfig = new PyramidConfig(
            levels: [
                new LevelThreshold(level: TestLevel::UNIT, paths: [self::FIXTURES.'/counting'], minPercentage: 90),
                new LevelThreshold(level: TestLevel::E2E, paths: [self::FIXTURES.'/gherkin'], counter: 'gherkin'),
            ],
            enforceOrdering: false,
        );

        $pyramidReport = (new PyramidAnalyzer())->analyze($pyramidConfig);

        $belowMin = array_filter($pyramidReport->violations, static fn (string $violation): bool => str_contains($violation, 'below the required minimum'));
        self::assertNotSame([], $belowMin);
    }
}
