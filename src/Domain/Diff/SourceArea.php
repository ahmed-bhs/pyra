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

        return $normalizedFile === $normalizedPath || str_starts_with($normalizedFile, $normalizedPath.'/');
    }

    /**
     * Length of the declared path once normalized; used to pick the most
     * specific area when several overlap (longest prefix wins).
     */
    public function specificity(): int
    {
        return \strlen($this->normalizedPath());
    }

    private function normalizedPath(): string
    {
        return ltrim(str_replace('\\', '/', $this->path), '/');
    }
}
