<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Counter;

final class CounterRegistry
{
    /** @var array<string, TestCounter> */
    private array $counters;

    public function __construct()
    {
        $this->counters = [
            'phpunit' => new PhpUnitTestCounter(),
            'gherkin' => new GherkinScenarioCounter(),
        ];
    }

    public function get(string $name): TestCounter
    {
        return $this->counters[$name]
            ?? throw new \InvalidArgumentException(\sprintf('Unknown test counter "%s". Available: %s.', $name, implode(', ', array_keys($this->counters))));
    }
}
