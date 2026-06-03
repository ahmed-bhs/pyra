<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

final readonly class PyramidReport
{
    /**
     * @param array<string, LevelCount> $counts          indexed by TestLevel value
     * @param array<string, string>     $counterByLevel  TestLevel value => counter name
     * @param list<string>              $violations
     */
    public function __construct(
        public array $counts,
        public int $totalMethods,
        public array $violations,
        public array $counterByLevel = [],
    ) {
    }

    /**
     * Share of this level among the levels counted in the SAME unit (PHPUnit
     * methods vs Gherkin scenarios are not comparable, so they are not summed
     * into one denominator).
     */
    public function percentage(TestLevel $testLevel): float
    {
        $counter = $this->counterByLevel[$testLevel->value] ?? null;

        $denominator = 0;
        foreach ($this->counts as $levelValue => $levelCount) {
            if (null === $counter || ($this->counterByLevel[$levelValue] ?? null) === $counter) {
                $denominator += $levelCount->methods;
            }
        }

        if (0 === $denominator) {
            return 0.0;
        }

        $methods = isset($this->counts[$testLevel->value]) ? $this->counts[$testLevel->value]->methods : 0;

        return round($methods * 100 / $denominator, 1);
    }

    public function hasViolations(): bool
    {
        return [] !== $this->violations;
    }
}
