<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain;

enum TestLevel: string
{
    case UNIT = 'unit';
    case INTEGRATION = 'integration';
    case E2E = 'e2e';
}
