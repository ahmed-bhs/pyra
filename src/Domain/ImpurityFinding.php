<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

final readonly class ImpurityFinding
{
    public function __construct(
        public string $file,
        public string $offendingSymbol,
    ) {
    }
}
