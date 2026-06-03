<?php

declare(strict_types=1);

namespace Fixtures\Imports\Inline;

use PHPUnit\Framework\TestCase;

final class InlineFqcnTest extends TestCase
{
    public function testSomething(): void
    {
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
    }
}
