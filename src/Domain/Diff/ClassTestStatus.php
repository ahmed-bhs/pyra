<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

use AhmedBhs\Pyra\Domain\TestLevel;

final readonly class ClassTestStatus
{
    /**
     * @param list<TestLevel> $expectedLevels levels the source area declares as expected
     * @param list<TestLevel> $coveredLevels  levels that have at least one test referencing the class
     * @param ?float          $changedLineCoverage percentage of changed lines covered (null when no coverage XML supplied)
     */
    public function __construct(
        public string $sourceFile,
        public ?string $className,
        public array $expectedLevels,
        public array $coveredLevels,
        public ?float $changedLineCoverage = null,
    ) {
    }

    /**
     * @return list<TestLevel>
     */
    public function missingLevels(): array
    {
        return array_values(array_filter(
            $this->expectedLevels,
            fn (TestLevel $level): bool => !\in_array($level, $this->coveredLevels, true),
        ));
    }
}
