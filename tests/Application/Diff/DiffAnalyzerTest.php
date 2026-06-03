<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application\Diff;

use AhmedBhs\Pyra\Application\Diff\ClassNameExtractor;
use AhmedBhs\Pyra\Application\Diff\DiffAnalyzer;
use AhmedBhs\Pyra\Application\Diff\FileClassifier;
use AhmedBhs\Pyra\Application\Diff\SourceTestMapper;
use AhmedBhs\Pyra\Application\FileInspector;
use AhmedBhs\Pyra\Domain\Diff\ChangedFile;
use AhmedBhs\Pyra\Domain\Diff\ChangeStatus;
use AhmedBhs\Pyra\Domain\Diff\DiffConfig;
use AhmedBhs\Pyra\Domain\Diff\SourceArea;
use AhmedBhs\Pyra\Domain\LevelThreshold;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\TestLevel;
use PHPUnit\Framework\TestCase;

final class DiffAnalyzerTest extends TestCase
{
    private const string ROOT = __DIR__.'/../../Fixtures/diffproject';

    public function testNeSignalePasUnTestManquantQuandUnTestExistantCouvreLaClasseChangee(): void
    {
        $diffReport = $this->analyzer()->analyze([
            new ChangedFile('src/Domain/Order.php', ChangeStatus::MODIFIED),
        ]);

        self::assertFalse($diffReport->hasGateViolations(), 'An existing unchanged unit test references Order, so nothing is missing');
    }

    public function testSignaleUnTestUnitaireManquantPourUneClasseChangeeSansTest(): void
    {
        $diffReport = $this->analyzer()->analyze([
            new ChangedFile('src/Domain/Invoice.php', ChangeStatus::ADDED),
        ]);

        self::assertTrue($diffReport->hasGateViolations());
        self::assertStringContainsString('Invoice', $diffReport->gateViolations[0]);
        self::assertStringContainsString('unit', $diffReport->gateViolations[0]);
    }

    public function testIgnoreLesFichiersConfiguresCommeIgnores(): void
    {
        $diffReport = $this->analyzer($this->config(['src/Domain/Invoice.php']))->analyze([
            new ChangedFile('src/Domain/Invoice.php', ChangeStatus::ADDED),
        ]);

        self::assertFalse($diffReport->hasGateViolations());
    }

    private function analyzer(?PyramidConfig $pyramidConfig = null): DiffAnalyzer
    {
        $pyramidConfig ??= $this->config();
        $fileInspector = new FileInspector();

        return new DiffAnalyzer(
            $pyramidConfig,
            self::ROOT,
            new FileClassifier($pyramidConfig, self::ROOT),
            new SourceTestMapper($pyramidConfig, $fileInspector),
            new ClassNameExtractor(),
            $fileInspector,
        );
    }

    /**
     * @param list<string> $ignore
     */
    private function config(array $ignore = []): PyramidConfig
    {
        return new PyramidConfig(
            levels: [
                new LevelThreshold(TestLevel::UNIT, [self::ROOT.'/tests/Unit']),
                new LevelThreshold(TestLevel::INTEGRATION, [self::ROOT.'/tests/Integration']),
            ],
            diff: new DiffConfig(
                baseRef: 'HEAD~1',
                sources: [new SourceArea('src/Domain', [TestLevel::UNIT])],
                ignore: $ignore,
            ),
        );
    }
}
