<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

final readonly class LevelThreshold
{
    /**
     * @param list<string> $paths
     * @param list<string> $forbiddenDependencies fully-qualified class names (or prefixes) a test at this level must not depend on
     */
    public function __construct(
        public TestLevel $level,
        public array $paths,
        public ?float $minPercentage = null,
        public ?float $maxPercentage = null,
        public array $forbiddenDependencies = [],
        public string $counter = 'phpunit',
    ) {
    }
}
