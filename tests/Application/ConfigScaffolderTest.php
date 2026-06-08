<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application;

use AhmedBhs\Pyra\Application\ConfigScaffolder;
use AhmedBhs\Pyra\Domain\TestLevel;
use AhmedBhs\Pyra\Infrastructure\YamlConfigLoader;
use PHPUnit\Framework\TestCase;

final class ConfigScaffolderTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = sys_get_temp_dir().'/pyra-scaffold-'.uniqid();
        mkdir($this->projectRoot, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectRoot);
    }

    public function testGeneratesAValidConfigForAHexagonalProject(): void
    {
        $this->makeDirs(['tests/Unit', 'tests/Integration', 'features', 'src/Billing/Domain']);

        $config = $this->scaffoldAndLoad();

        $levelNames = array_map(static fn ($level): string => $level->level->value, $config->levels);
        self::assertSame(['unit', 'integration', 'e2e'], $levelNames);
        self::assertNotNull($config->diff);
        self::assertSame([TestLevel::UNIT], $config->diff->sourceAreaFor('src/Billing/Domain/Invoice.php')?->expectedLevels);
    }

    public function testGeneratedE2eUsesGherkinCounter(): void
    {
        $this->makeDirs(['tests/Unit', 'features', 'src/Billing/Domain']);

        $config = $this->scaffoldAndLoad();

        $e2e = array_values(array_filter($config->levels, static fn ($level): bool => TestLevel::E2E === $level->level));
        self::assertSame('gherkin', $e2e[0]->counter);
    }

    public function testOmitsLevelsWhoseDirectoryIsAbsent(): void
    {
        $this->makeDirs(['tests/Unit', 'src']);

        $config = $this->scaffoldAndLoad();

        $levelNames = array_map(static fn ($level): string => $level->level->value, $config->levels);
        self::assertSame(['unit'], $levelNames);
    }

    public function testFallsBackToAFlatSourceRuleWhenNoLayeringDetected(): void
    {
        $this->makeDirs(['tests/Unit', 'src']);

        $config = $this->scaffoldAndLoad();

        self::assertSame([TestLevel::UNIT], $config->diff?->sourceAreaFor('src/Service/Foo.php')?->expectedLevels);
    }

    private function scaffoldAndLoad(): \AhmedBhs\Pyra\Domain\PyramidConfig
    {
        $yaml = (new ConfigScaffolder())->scaffold($this->projectRoot);
        $path = $this->projectRoot.'/pyra.yaml';
        file_put_contents($path, $yaml);

        return (new YamlConfigLoader())->load($path);
    }

    /**
     * @param list<string> $dirs
     */
    private function makeDirs(array $dirs): void
    {
        foreach ($dirs as $dir) {
            mkdir($this->projectRoot.'/'.$dir, 0o777, true);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
