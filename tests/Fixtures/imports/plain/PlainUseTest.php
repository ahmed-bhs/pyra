<?php

declare(strict_types=1);

namespace Fixtures\Imports\Plain;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PlainUseTest extends TestCase
{
    public function testSomething(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
    }
}
