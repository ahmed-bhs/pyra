<?php

declare(strict_types=1);

namespace Acme\Tests\Unit;

use Acme\Domain\Order;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testTotal(): void
    {
        self::assertSame(0, (new Order())->total());
    }
}
