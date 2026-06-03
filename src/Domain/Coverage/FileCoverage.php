<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Coverage;

final readonly class FileCoverage
{
    /**
     * @param array<int, bool> $lines line number => covered (true) / executable-but-uncovered (false)
     */
    public function __construct(
        public string $path,
        public array $lines,
    ) {
    }

    public function isCovered(int $line): bool
    {
        return $this->lines[$line] ?? false;
    }

    public function isExecutable(int $line): bool
    {
        return \array_key_exists($line, $this->lines);
    }
}
