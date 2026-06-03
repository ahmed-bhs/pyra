<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Counter;

final readonly class CountResult
{
    /**
     * @param list<string> $dependencies fully-qualified class symbols referenced by the file (empty for non-PHP styles)
     */
    public function __construct(
        public int $tests,
        public array $dependencies = [],
    ) {
    }
}
