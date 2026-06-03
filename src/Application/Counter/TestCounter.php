<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Counter;

/**
 * Strategy that counts the tests contained in a single source file and
 * reports the class symbols that file statically depends on.
 *
 * Different test styles (PHPUnit methods, Gherkin scenarios, Pest closures)
 * are counted by different implementations, so Pyra stays framework-agnostic.
 */
interface TestCounter
{
    public function filePattern(): string;

    public function count(string $content): CountResult;
}
