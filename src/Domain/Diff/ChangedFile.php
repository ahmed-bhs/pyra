<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

final readonly class ChangedFile
{
    /**
     * @param list<LineRange> $addedLineRanges line ranges added/modified on the new side of the diff
     */
    public function __construct(
        public string $path,
        public ChangeStatus $status,
        public array $addedLineRanges = [],
    ) {
    }

    public function isDeletion(): bool
    {
        return ChangeStatus::DELETED === $this->status;
    }
}
