<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Infrastructure\Coverage;

use AhmedBhs\Pyra\Domain\Coverage\CoverageReport;
use AhmedBhs\Pyra\Domain\Coverage\FileCoverage;

/**
 * Parses Clover and Cobertura XML coverage reports into a CoverageReport.
 * Format is auto-detected from the root element.
 */
final class CoverageReportParser
{
    public function parse(string $path): CoverageReport
    {
        if (!is_file($path)) {
            throw new \RuntimeException(\sprintf('Coverage file "%s" not found.', $path));
        }

        $xml = simplexml_load_file($path);
        if (false === $xml) {
            throw new \RuntimeException(\sprintf('Could not parse coverage XML "%s".', $path));
        }

        // Clover and Cobertura share the <coverage> root element; distinguish
        // by structure: Clover nests <project>, Cobertura nests <packages>.
        if (isset($xml->project)) {
            return $this->parseClover($xml);
        }

        return $this->parseCobertura($xml);
    }

    private function parseClover(\SimpleXMLElement $xml): CoverageReport
    {
        $files = [];
        foreach ($xml->xpath('//file') ?: [] as $fileNode) {
            $path = (string) $fileNode['name'];
            if ('' === $path) {
                continue;
            }

            $lines = [];
            foreach ($fileNode->line as $lineNode) {
                if ('stmt' !== (string) $lineNode['type'] && '' !== (string) $lineNode['type']) {
                    continue;
                }
                $number = (int) $lineNode['num'];
                $lines[$number] = ((int) $lineNode['count']) > 0;
            }

            $files[$this->normalize($path)] = new FileCoverage($this->normalize($path), $lines);
        }

        return new CoverageReport($files);
    }

    private function parseCobertura(\SimpleXMLElement $xml): CoverageReport
    {
        $files = [];
        foreach ($xml->xpath('//class') ?: [] as $classNode) {
            $path = (string) $classNode['filename'];
            if ('' === $path) {
                continue;
            }

            $normalized = $this->normalize($path);
            $lines = $files[$normalized]->lines ?? [];
            foreach ($classNode->lines->line ?? [] as $lineNode) {
                $number = (int) $lineNode['number'];
                $lines[$number] = ((int) $lineNode['hits']) > 0;
            }

            $files[$normalized] = new FileCoverage($normalized, $lines);
        }

        return new CoverageReport($files);
    }

    private function normalize(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
