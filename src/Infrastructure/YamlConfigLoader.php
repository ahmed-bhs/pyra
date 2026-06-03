<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure;

use AhmedBhs\Pyra\Domain\Diff\DiffConfig;
use AhmedBhs\Pyra\Domain\Diff\SourceArea;
use AhmedBhs\Pyra\Domain\LevelThreshold;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\TestLevel;
use Symfony\Component\Yaml\Yaml;

final class YamlConfigLoader
{
    public function load(string $path): PyramidConfig
    {
        if (!is_file($path)) {
            throw new \RuntimeException(\sprintf('Configuration file "%s" not found.', $path));
        }

        $parsed = Yaml::parseFile($path);

        if (!\is_array($parsed) || !isset($parsed['pyra']) || !\is_array($parsed['pyra'])) {
            throw new \RuntimeException('Configuration must contain a "pyra" root key.');
        }

        $config = $parsed['pyra'];
        $baseDir = \dirname((string) realpath($path) ?: $path);

        $levels = [];
        foreach (($config['levels'] ?? []) as $levelName => $levelConfig) {
            $levels[] = new LevelThreshold(
                level: TestLevel::from((string) $levelName),
                paths: array_map(
                    static fn (string $relative): string => $baseDir.\DIRECTORY_SEPARATOR.$relative,
                    (array) ($levelConfig['paths'] ?? []),
                ),
                minPercentage: isset($levelConfig['min_percentage']) ? (float) $levelConfig['min_percentage'] : null,
                maxPercentage: isset($levelConfig['max_percentage']) ? (float) $levelConfig['max_percentage'] : null,
                forbiddenDependencies: array_values(array_map('strval', (array) ($levelConfig['forbidden_dependencies'] ?? []))),
                counter: (string) ($levelConfig['counter'] ?? 'phpunit'),
            );
        }

        return new PyramidConfig(
            levels: $levels,
            enforceOrdering: (bool) ($config['enforce_ordering'] ?? true),
            diff: $this->parseDiff($config['diff'] ?? null),
        );
    }

    /**
     * @param array<string, mixed>|null $diff
     */
    private function parseDiff(?array $diff): ?DiffConfig
    {
        if (null === $diff) {
            return null;
        }

        $sources = [];
        foreach (($diff['sources'] ?? []) as $source) {
            $expected = array_map(
                static fn (string $level): TestLevel => TestLevel::from($level),
                array_map('strval', (array) ($source['expect'] ?? [])),
            );
            $sources[] = new SourceArea((string) ($source['path'] ?? ''), array_values($expected));
        }

        return new DiffConfig(
            baseRef: (string) ($diff['base'] ?? 'HEAD~1'),
            sources: $sources,
            ignore: array_values(array_map('strval', (array) ($diff['ignore'] ?? []))),
        );
    }
}
