<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Coverage;

final readonly class CoverageIntersection
{
    /**
     * @param list<int> $uncoveredLines
     */
    public function __construct(
        public float $percentage,
        public array $uncoveredLines,
        public int $executableChangedLines,
    ) {
    }
}
