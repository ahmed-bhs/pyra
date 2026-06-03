<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Git;

use AhmedBhs\Pyra\Domain\Diff\ChangedFile;
use AhmedBhs\Pyra\Domain\Diff\ChangeStatus;
use AhmedBhs\Pyra\Domain\Diff\LineRange;

/**
 * Parses the text of `git diff --unified=0` into ChangedFile objects.
 * Pure string -> objects, so it is fully testable without a git repository.
 */
final class UnifiedDiffParser
{
    /**
     * @return list<ChangedFile>
     */
    public function parse(string $diff): array
    {
        /** @var list<ChangedFile> $files */
        $files = [];
        $currentPath = null;
        $currentStatus = ChangeStatus::MODIFIED;
        /** @var list<LineRange> $ranges */
        $ranges = [];

        foreach (explode("\n", $diff) as $line) {
            if (str_starts_with($line, 'diff --git ')) {
                if (null !== $currentPath) {
                    $files[] = new ChangedFile($currentPath, $currentStatus, $ranges);
                }
                $currentStatus = ChangeStatus::MODIFIED;
                $ranges = [];
                // Capture the path from the header so deletions (whose +++ is
                // /dev/null) are still recorded: "diff --git a/x b/x".
                $currentPath = 1 === preg_match('#^diff --git a/.+ b/(.+)$#', $line, $matches)
                    ? $matches[1]
                    : null;

                continue;
            }

            if (str_starts_with($line, 'new file')) {
                $currentStatus = ChangeStatus::ADDED;

                continue;
            }

            if (str_starts_with($line, 'deleted file')) {
                $currentStatus = ChangeStatus::DELETED;

                continue;
            }

            if (str_starts_with($line, '+++ ')) {
                $path = substr($line, 4);
                if ('/dev/null' === $path) {
                    continue;
                }
                $currentPath = (string) preg_replace('#^b/#', '', $path);

                continue;
            }

            if (str_starts_with($line, '@@')) {
                $lineRange = $this->parseHunkHeader($line);
                if (null !== $lineRange) {
                    $ranges[] = $lineRange;
                }
            }
        }

        if (null !== $currentPath) {
            $files[] = new ChangedFile($currentPath, $currentStatus, $ranges);
        }

        return $files;
    }

    private function parseHunkHeader(string $line): ?LineRange
    {
        // @@ -a,b +c,d @@  -> new side starts at c, spans d lines
        if (1 !== preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $matches)) {
            return null;
        }

        $start = (int) $matches[1];
        $count = isset($matches[2]) ? (int) $matches[2] : 1;

        if (0 === $count) {
            return null;
        }

        return new LineRange($start, $start + $count - 1);
    }
}
