<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

final readonly class PyramidReport
{
    /**
     * @param array<string, LevelCount> $counts     indexed by TestLevel value
     * @param list<string>              $violations
     */
    public function __construct(
        public array $counts,
        public int $totalMethods,
        public array $violations,
    ) {
    }

    public function percentage(TestLevel $testLevel): float
    {
        if (0 === $this->totalMethods) {
            return 0.0;
        }

        $methods = isset($this->counts[$testLevel->value]) ? $this->counts[$testLevel->value]->methods : 0;

        return round($methods * 100 / $this->totalMethods, 1);
    }

    public function hasViolations(): bool
    {
        return [] !== $this->violations;
    }
}
