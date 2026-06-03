<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Counter;

final class GherkinScenarioCounter implements TestCounter
{
    public function filePattern(): string
    {
        return '*.feature';
    }

    public function count(string $content): CountResult
    {
        $scenarios = preg_match_all('/^\s*(Scenario|Scenario Outline|Scénario|Plan du scénario|Exemple)\s*:/mu', $content);

        return new CountResult(false === $scenarios ? 0 : $scenarios);
    }
}
