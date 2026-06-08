<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

use AhmedBhs\Pyra\Domain\TestLevel;

final readonly class SourceArea
{
    /**
     * @param list<TestLevel> $expectedLevels
     */
    public function __construct(
        public string $path,
        public array $expectedLevels,
    ) {
    }

    public function matches(string $filePath): bool
    {
        $normalizedPath = $this->normalizedPath();
        $normalizedFile = ltrim(str_replace('\\', '/', $filePath), '/');

        if (!str_contains($normalizedPath, '*')) {
            return $normalizedFile === $normalizedPath || str_starts_with($normalizedFile, $normalizedPath.'/');
        }

        return 1 === preg_match($this->toRegex($normalizedPath), $normalizedFile);
    }

    /**
     * Specificity used to pick the most specific area when several overlap.
     * Concrete path segments count; a "*" wildcard counts for nothing, so a
     * literal path always beats a glob covering the same depth.
     */
    public function specificity(): int
    {
        $literal = str_replace('*', '', $this->normalizedPath());

        return \strlen($literal);
    }

    private function toRegex(string $globPath): string
    {
        $segments = explode('/', $globPath);
        $pattern = implode('/', array_map(
            static fn (string $segment): string => '*' === $segment ? '[^/]+' : preg_quote($segment, '#'),
            $segments,
        ));

        return '#^'.$pattern.'(/.*)?$#';
    }

    private function normalizedPath(): string
    {
        return ltrim(str_replace('\\', '/', $this->path), '/');
    }
}
