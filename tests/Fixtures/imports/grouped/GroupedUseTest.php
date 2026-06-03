<?php

declare(strict_types=1);

namespace Fixtures\Imports\Grouped;

use Doctrine\ORM\{EntityManager, EntityManagerInterface};
use PHPUnit\Framework\TestCase;

final class GroupedUseTest extends TestCase
{
    public function testSomething(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
    }
}
