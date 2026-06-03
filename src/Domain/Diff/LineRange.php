<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

final readonly class LineRange
{
    public function __construct(
        public int $start,
        public int $end,
    ) {
    }

    public function contains(int $line): bool
    {
        return $line >= $this->start && $line <= $this->end;
    }

    /**
     * @return list<int>
     */
    public function lines(): array
    {
        return range($this->start, $this->end);
    }
}
