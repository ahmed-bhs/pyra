<?php

declare(strict_types=1);

namespace Fixtures\Imports\Aliased;

use Doctrine\ORM\EntityManagerInterface as Em;
use PHPUnit\Framework\TestCase;

final class AliasedUseTest extends TestCase
{
    public function testSomething(): void
    {
        $entityManager = $this->createMock(Em::class);
    }
}
