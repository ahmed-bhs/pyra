<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Git;

use AhmedBhs\Pyra\Domain\Diff\DiffProvider;

final class GitDiffProvider implements DiffProvider
{
    public function __construct(
        private readonly string $workingDirectory,
        private readonly UnifiedDiffParser $unifiedDiffParser = new UnifiedDiffParser(),
    ) {
    }

    public function changedFiles(string $baseRef): array
    {
        return $this->unifiedDiffParser->parse($this->run(['git', 'diff', '--unified=0', '--no-color', $baseRef]));
    }

    /**
     * @param list<string> $command
     */
    private function run(array $command): string
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptors, $pipes, $this->workingDirectory);

        if (!\is_resource($process)) {
            throw new \RuntimeException('Unable to run git.');
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if (0 !== $exitCode) {
            throw new \RuntimeException(\sprintf('git failed: %s', trim($stderr)));
        }

        return $stdout;
    }
}
