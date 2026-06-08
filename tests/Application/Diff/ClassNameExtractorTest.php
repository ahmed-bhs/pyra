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

    public function testTestableIgnoreLesInterfaces(): void
    {
        $names = (new ClassNameExtractor())->extractTestable(<<<'PHP'
            <?php
            namespace Acme\Domain;
            interface OrderRepository { public function save(): void; }
            PHP);

        self::assertSame([], $names);
    }

    public function testTestableIgnoreLesEnumsSansMethode(): void
    {
        $names = (new ClassNameExtractor())->extractTestable(<<<'PHP'
            <?php
            namespace Acme\Domain;
            enum Transition: string { case Open = 'open'; case Close = 'close'; }
            PHP);

        self::assertSame([], $names);
    }

    public function testTestableGardeLesEnumsAvecMethode(): void
    {
        $names = (new ClassNameExtractor())->extractTestable(<<<'PHP'
            <?php
            namespace Acme\Domain;
            enum Status: string {
                case Open = 'open';
                public function isTerminal(): bool { return false; }
            }
            PHP);

        self::assertSame(['Acme\Domain\Status'], $names);
    }

    public function testTestableIgnoreLesClassesMarqueurVides(): void
    {
        $names = (new ClassNameExtractor())->extractTestable(<<<'PHP'
            <?php
            namespace Acme\Domain;
            final class OrderPlaced {}
            PHP);

        self::assertSame([], $names);
    }

    public function testTestableGardeLesValueObjectsAConstructeurSeul(): void
    {
        $names = (new ClassNameExtractor())->extractTestable(<<<'PHP'
            <?php
            namespace Acme\Domain;
            final readonly class Email {
                public function __construct(public string $value) {}
            }
            PHP);

        self::assertSame(['Acme\Domain\Email'], $names);
    }

    public function testTestableGardeLesClassesAProprietes(): void
    {
        $names = (new ClassNameExtractor())->extractTestable(<<<'PHP'
            <?php
            namespace Acme\Domain;
            final class Counter { private int $count = 0; }
            PHP);

        self::assertSame(['Acme\Domain\Counter'], $names);
    }
}
