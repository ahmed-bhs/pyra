<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Console\Output;

final class DiffReportFormatterRegistry
{
    /** @var array<string, DiffReportFormatter> */
    private array $formatters;

    public function __construct()
    {
        $this->formatters = [];
        foreach ([new TableDiffReportFormatter(), new JsonDiffReportFormatter(), new GithubDiffReportFormatter()] as $formatter) {
            $this->formatters[$formatter->name()] = $formatter;
        }
    }

    public function get(string $name): DiffReportFormatter
    {
        return $this->formatters[$name]
            ?? throw new \InvalidArgumentException(\sprintf('Unknown format "%s". Available: %s.', $name, implode(', ', array_keys($this->formatters))));
    }
}
