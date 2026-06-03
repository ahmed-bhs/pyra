<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Coverage;

final readonly class CoverageReport
{
    /**
     * @param array<string, FileCoverage> $files indexed by normalized file path (forward slashes)
     */
    public function __construct(
        public array $files,
    ) {
    }

    public function forPathSuffix(string $relativePath): ?FileCoverage
    {
        $needle = ltrim(str_replace('\\', '/', $relativePath), '/');

        foreach ($this->files as $path => $fileCoverage) {
            if ($path === $needle || str_ends_with($path, '/'.$needle)) {
                return $fileCoverage;
            }
        }

        return null;
    }
}
