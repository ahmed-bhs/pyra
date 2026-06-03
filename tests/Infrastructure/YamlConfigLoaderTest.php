<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Infrastructure;

use AhmedBhs\Pyra\Domain\TestLevel;
use AhmedBhs\Pyra\Infrastructure\YamlConfigLoader;
use PHPUnit\Framework\TestCase;

final class YamlConfigLoaderTest extends TestCase
{
    public function testEchoueQuandLeFichierEstAbsent(): void
    {
        $this->expectException(\RuntimeException::class);

        (new YamlConfigLoader())->load(__DIR__.'/does-not-exist.yaml');
    }

    public function testEchoueSansCleRacinePyra(): void
    {
        $path = $this->writeConfig("foo:\n    bar: baz\n");

        $this->expectException(\RuntimeException::class);

        try {
            (new YamlConfigLoader())->load($path);
        } finally {
            @unlink($path);
        }
    }

    public function testChargeLesNiveauxAvecLeursOptions(): void
    {
        $path = $this->writeConfig(<<<'YAML'
            pyra:
                enforce_ordering: false
                levels:
                    unit:
                        paths: [tests/Unit]
                        min_percentage: 70
                        forbidden_dependencies:
                            - Doctrine\ORM\EntityManagerInterface
                    e2e:
                        paths: [features]
                        counter: gherkin
            YAML);

        $pyramidConfig = (new YamlConfigLoader())->load($path);
        @unlink($path);

        self::assertFalse($pyramidConfig->enforceOrdering);
        self::assertCount(2, $pyramidConfig->levels);
        self::assertSame(TestLevel::UNIT, $pyramidConfig->levels[0]->level);
        self::assertSame(70.0, $pyramidConfig->levels[0]->minPercentage);
        self::assertSame(['Doctrine\ORM\EntityManagerInterface'], $pyramidConfig->levels[0]->forbiddenDependencies);
        self::assertSame('gherkin', $pyramidConfig->levels[1]->counter);
    }

    private function writeConfig(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pyra').'.yaml';
        file_put_contents($path, $contents);

        return $path;
    }
}
