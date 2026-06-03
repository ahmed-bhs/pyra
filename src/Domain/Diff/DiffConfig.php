<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Domain\Diff;

final readonly class DiffConfig
{
    /**
     * @param list<SourceArea> $sources
     * @param list<string>     $ignore  path prefixes whose change never requires tests
     */
    public function __construct(
        public string $baseRef,
        public array $sources,
        public array $ignore = [],
    ) {
    }

    public function isIgnored(string $filePath): bool
    {
        $normalizedFile = ltrim(str_replace('\\', '/', $filePath), '/');
        foreach ($this->ignore as $prefix) {
            $normalizedPrefix = ltrim(str_replace('\\', '/', $prefix), '/');
            if ($normalizedFile === $normalizedPrefix || str_starts_with($normalizedFile, $normalizedPrefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public function sourceAreaFor(string $filePath): ?SourceArea
    {
        foreach ($this->sources as $source) {
            if ($source->matches($filePath)) {
                return $source;
            }
        }

        return null;
    }
}
