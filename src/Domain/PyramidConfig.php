<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

use AhmedBhs\Pyra\Domain\Diff\DiffConfig;

final readonly class PyramidConfig
{
    /**
     * @param list<LevelThreshold> $levels
     */
    public function __construct(
        public array $levels,
        public bool $enforceOrdering = true,
        public ?DiffConfig $diff = null,
    ) {
    }
}
