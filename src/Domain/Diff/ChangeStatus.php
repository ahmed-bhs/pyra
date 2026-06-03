<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

enum ChangeStatus: string
{
    case ADDED = 'added';
    case MODIFIED = 'modified';
    case DELETED = 'deleted';
    case RENAMED = 'renamed';
}
