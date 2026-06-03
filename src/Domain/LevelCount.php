<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

final readonly class LevelCount
{
    /**
     * @param list<ImpurityFinding> $impurities tests at this level that depend on a forbidden integration signal
     */
    public function __construct(
        public TestLevel $level,
        public int $methods,
        public int $files,
        public array $impurities = [],
    ) {
    }
}
