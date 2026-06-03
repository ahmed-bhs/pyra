<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application;

use AhmedBhs\Pyra\Domain\ImpurityFinding;

final readonly class FileInspectionResult
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        public int $tests,
        public array $dependencies,
        public ?ImpurityFinding $impurity,
    ) {
    }
}
