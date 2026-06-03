<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application\Diff;

use AhmedBhs\Pyra\Application\Diff\ClassNameExtractor;
use PHPUnit\Framework\TestCase;

final class ClassNameExtractorTest extends TestCase
{
    public function testExtraitLeNomQualifieDUneClasse(): void
    {
        $names = (new ClassNameExtractor())->extract(<<<'PHP'
            <?php
            namespace Acme\Domain;
            final class Order {}
            PHP);

        self::assertSame(['Acme\Domain\Order'], $names);
    }

    public function testExtraitInterfacesEnumsEtTraits(): void
    {
        $names = (new ClassNameExtractor())->extract(<<<'PHP'
            <?php
            namespace Acme\Domain;
            enum Status: string { case Open = 'open'; }
            PHP);

        self::assertSame(['Acme\Domain\Status'], $names);
    }

    public function testRetourneVideSansDeclaration(): void
    {
        self::assertSame([], (new ClassNameExtractor())->extract("<?php\n\$x = 1;"));
    }
}
