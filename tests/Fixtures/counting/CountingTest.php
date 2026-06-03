<?php

declare(strict_types=1);

namespace Fixtures\Counting;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CountingTest extends TestCase
{
    public function testPrefixed(): void
    {
    }

    #[Test]
    public function withAttribute(): void
    {
    }

    public function helperNotATest(): void
    {
    }

    private function testButPrivate(): void
    {
    }
}
