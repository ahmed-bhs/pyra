<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

use AhmedBhs\Pyra\Domain\ImpurityFinding;

final readonly class DiffReport
{
    /**
     * @param list<ClassTestStatus> $classStatuses
     * @param list<string>          $gateViolations hard signals (missing expected test level for a changed class)
     * @param list<ImpurityFinding> $impurities     purity findings on changed test files
     * @param list<string>          $coverageWarnings changed lines not covered (only when coverage XML supplied)
     */
    public function __construct(
        public array $classStatuses,
        public array $gateViolations,
        public array $impurities,
        public array $coverageWarnings = [],
    ) {
    }

    public function hasGateViolations(): bool
    {
        return [] !== $this->gateViolations;
    }
}
