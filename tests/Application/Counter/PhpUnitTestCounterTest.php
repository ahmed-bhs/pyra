<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application\Counter;

use AhmedBhs\Pyra\Application\Counter\PhpUnitTestCounter;
use PHPUnit\Framework\TestCase;

final class PhpUnitTestCounterTest extends TestCase
{
    private const string EM = 'Doctrine\ORM\EntityManagerInterface';

    public function testCountsPrefixedAndAttributedPublicMethodsOnly(): void
    {
        $countResult = (new PhpUnitTestCounter())->count(file_get_contents(__DIR__.'/../../Fixtures/counting/CountingTest.php'));

        self::assertSame(2, $countResult->tests);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function importStyleProvider(): iterable
    {
        yield 'plain use' => [__DIR__.'/../../Fixtures/imports/plain/PlainUseTest.php'];
        yield 'grouped use' => [__DIR__.'/../../Fixtures/imports/grouped/GroupedUseTest.php'];
        yield 'aliased use' => [__DIR__.'/../../Fixtures/imports/aliased/AliasedUseTest.php'];
        yield 'inline fqcn' => [__DIR__.'/../../Fixtures/imports/inline/InlineFqcnTest.php'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('importStyleProvider')]
    public function testResolvesEntityManagerDependencyRegardlessOfImportStyle(string $fixture): void
    {
        $countResult = (new PhpUnitTestCounter())->count(file_get_contents($fixture));

        self::assertContains(self::EM, $countResult->dependencies);
    }
}
