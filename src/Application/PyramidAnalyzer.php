<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application;

use AhmedBhs\Pyra\Domain\LevelCount;
use AhmedBhs\Pyra\Domain\PyramidConfig;
use AhmedBhs\Pyra\Domain\PyramidReport;
use AhmedBhs\Pyra\Domain\TestLevel;

final class PyramidAnalyzer
{
    public function __construct(
        private readonly LevelScanner $levelScanner = new LevelScanner(),
    ) {
    }

    public function analyze(PyramidConfig $pyramidConfig): PyramidReport
    {
        $counts = [];
        $counterByLevel = [];
        $totalMethods = 0;
        foreach ($pyramidConfig->levels as $levelThreshold) {
            $levelCount = $this->levelScanner->scan($levelThreshold);
            $counts[$levelThreshold->level->value] = $levelCount;
            $counterByLevel[$levelThreshold->level->value] = $levelThreshold->counter;
            $totalMethods += $levelCount->methods;
        }

        $partialReport = new PyramidReport($counts, $totalMethods, [], $counterByLevel);

        $violations = [];
        foreach ($pyramidConfig->levels as $levelThreshold) {
            $percentage = $partialReport->percentage($levelThreshold->level);
            $label = ucfirst($levelThreshold->level->value);

            if (null !== $levelThreshold->minPercentage && $percentage < $levelThreshold->minPercentage) {
                $violations[] = \sprintf('%s tests are %.1f%% of the suite, below the required minimum of %.1f%%.', $label, $percentage, $levelThreshold->minPercentage);
            }

            if (null !== $levelThreshold->maxPercentage && $percentage > $levelThreshold->maxPercentage) {
                $violations[] = \sprintf('%s tests are %.1f%% of the suite, above the allowed maximum of %.1f%%.', $label, $percentage, $levelThreshold->maxPercentage);
            }

            foreach ($counts[$levelThreshold->level->value]->impurities as $impurity) {
                $violations[] = \sprintf('%s test "%s" depends on "%s", a forbidden dependency for this level (it behaves like a higher-level test).', $label, $impurity->file, $impurity->offendingSymbol);
            }
        }

        if ($pyramidConfig->enforceOrdering) {
            $violations = [...$violations, ...$this->orderingViolations($pyramidConfig, $counts)];
        }

        return new PyramidReport($counts, $totalMethods, $violations, $counterByLevel);
    }

    /**
     * Ordering is only sound between levels counted in the same unit
     * (PHPUnit methods vs Gherkin scenarios are not comparable). Adjacent
     * levels using different counters are skipped rather than compared.
     *
     * @param array<string, LevelCount> $counts
     *
     * @return list<string>
     */
    private function orderingViolations(PyramidConfig $pyramidConfig, array $counts): array
    {
        $counterByLevel = [];
        foreach ($pyramidConfig->levels as $levelThreshold) {
            $counterByLevel[$levelThreshold->level->value] = $levelThreshold->counter;
        }

        $ordered = [
            [TestLevel::UNIT, TestLevel::INTEGRATION, 'unit', 'integration'],
            [TestLevel::INTEGRATION, TestLevel::E2E, 'integration', 'end-to-end'],
        ];

        $violations = [];
        foreach ($ordered as [$wider, $narrower, $widerLabel, $narrowerLabel]) {
            if (!isset($counts[$wider->value], $counts[$narrower->value])) {
                continue;
            }

            if (($counterByLevel[$wider->value] ?? null) !== ($counterByLevel[$narrower->value] ?? null)) {
                continue;
            }

            $widerCount = $counts[$wider->value]->methods;
            $narrowerCount = $counts[$narrower->value]->methods;
            if ($widerCount < $narrowerCount) {
                $violations[] = \sprintf('Inverted pyramid: %d %s tests is fewer than %d %s tests.', $widerCount, $widerLabel, $narrowerCount, $narrowerLabel);
            }
        }

        return $violations;
    }
}
