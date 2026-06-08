<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application;

/**
 * Generates a starter pyra.yaml by sniffing a project's layout. The output is
 * meant to be reviewed and edited: the expected test levels per source area are
 * a sensible default, not a fact read from the code.
 */
final class ConfigScaffolder
{
    public function scaffold(string $projectRoot): string
    {
        $root = rtrim($projectRoot, '/');

        $lines = [
            'pyra:',
            '    enforce_ordering: true',
            '    levels:',
        ];

        $unitPath = $this->firstExisting($root, ['tests/Unit', 'tests/unit']) ?? 'tests/Unit';
        $lines[] = '        unit:';
        $lines[] = '            paths: ['.$unitPath.']';

        $integrationPath = $this->firstExisting($root, ['tests/Integration', 'tests/Feature']);
        if (null !== $integrationPath) {
            $lines[] = '        integration:';
            $lines[] = '            paths: ['.$integrationPath.']';
        }

        $e2ePath = $this->firstExisting($root, ['features', 'tests/Behat']);
        if (null !== $e2ePath) {
            $lines[] = '        e2e:';
            $lines[] = '            paths: ['.$e2ePath.']';
            $lines[] = '            counter: gherkin';
        }

        $lines[] = '    diff:';
        $lines[] = '        base: origin/main';
        $lines[] = '        sources:';

        $sourceDir = $this->firstExisting($root, ['src', 'app']) ?? 'src';
        if ($this->looksHexagonal($root, $sourceDir)) {
            $lines[] = '            # Generated defaults for a layered/hexagonal layout — review before relying on them.';
            $lines[] = '            - path: '.$sourceDir.'/*/Domain';
            $lines[] = '              expect: [unit]';
            $lines[] = '            - path: '.$sourceDir.'/*/Application';
            $lines[] = '              expect: [unit]';
            $lines[] = '            - path: '.$sourceDir.'/*/Infrastructure';
            $lines[] = '              expect: [integration]';
        } else {
            $lines[] = '            # Could not detect a layered layout. Declare which areas expect which test level, e.g.:';
            $lines[] = '            # - path: '.$sourceDir.'/Domain';
            $lines[] = '            #   expect: [unit]';
            $lines[] = '            - path: '.$sourceDir;
            $lines[] = '              expect: [unit]';
        }

        $lines[] = '        ignore:';
        $lines[] = '            - migrations';
        $lines[] = '            - config';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param list<string> $candidates
     */
    private function firstExisting(string $root, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_dir($root.'/'.$candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function looksHexagonal(string $root, string $sourceDir): bool
    {
        $base = $root.'/'.$sourceDir;
        if (!is_dir($base)) {
            return false;
        }

        foreach ((array) glob($base.'/*/Domain', \GLOB_ONLYDIR) as $match) {
            if (is_dir((string) $match)) {
                return true;
            }
        }

        return false;
    }
}
