<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

interface DiffProvider
{
    /**
     * @return list<ChangedFile>
     */
    public function changedFiles(string $baseRef): array;
}
