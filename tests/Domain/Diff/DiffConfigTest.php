<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Domain\Diff;

use AhmedBhs\Pyra\Domain\Diff\DiffConfig;
use AhmedBhs\Pyra\Domain\Diff\SourceArea;
use AhmedBhs\Pyra\Domain\TestLevel;
use PHPUnit\Framework\TestCase;

final class DiffConfigTest extends TestCase
{
    public function testMostSpecificSourceWinsWhenBroadRuleIsDeclaredFirst(): void
    {
        $config = new DiffConfig('origin/main', [
            new SourceArea('src/Admin', [TestLevel::E2E]),
            new SourceArea('src/Admin/Renderer.php', [TestLevel::UNIT]),
        ]);

        $area = $config->sourceAreaFor('src/Admin/Renderer.php');

        self::assertSame([TestLevel::UNIT], $area?->expectedLevels);
    }

    public function testMostSpecificSourceWinsWhenSpecificRuleIsDeclaredFirst(): void
    {
        $config = new DiffConfig('origin/main', [
            new SourceArea('src/Admin/Renderer.php', [TestLevel::UNIT]),
            new SourceArea('src/Admin', [TestLevel::E2E]),
        ]);

        $area = $config->sourceAreaFor('src/Admin/Renderer.php');

        self::assertSame([TestLevel::UNIT], $area?->expectedLevels);
    }

    public function testFallsBackToTheBroadAreaForOtherFiles(): void
    {
        $config = new DiffConfig('origin/main', [
            new SourceArea('src/Admin', [TestLevel::E2E]),
            new SourceArea('src/Admin/Renderer.php', [TestLevel::UNIT]),
        ]);

        $area = $config->sourceAreaFor('src/Admin/Controller.php');

        self::assertSame([TestLevel::E2E], $area?->expectedLevels);
    }

    public function testReturnsNullWhenNoAreaMatches(): void
    {
        $config = new DiffConfig('origin/main', [
            new SourceArea('src/Admin', [TestLevel::E2E]),
        ]);

        self::assertNull($config->sourceAreaFor('src/Domain/Order.php'));
    }
}
