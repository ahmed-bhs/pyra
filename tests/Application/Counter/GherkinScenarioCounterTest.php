<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Application\Counter;

use AhmedBhs\Pyra\Application\Counter\GherkinScenarioCounter;
use PHPUnit\Framework\TestCase;

final class GherkinScenarioCounterTest extends TestCase
{
    public function testCountsScenariosAndOutlinesAsOneEach(): void
    {
        $countResult = (new GherkinScenarioCounter())->count(file_get_contents(__DIR__.'/../../Fixtures/gherkin/sample.feature'));

        self::assertSame(3, $countResult->tests);
    }
}
