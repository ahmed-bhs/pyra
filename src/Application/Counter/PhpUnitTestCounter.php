<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Counter;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

final class PhpUnitTestCounter implements TestCounter
{
    private readonly \PhpParser\Parser $parser;

    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    public function filePattern(): string
    {
        return '*.php';
    }

    public function count(string $content): CountResult
    {
        $statements = $this->parser->parse($content);

        if (null === $statements) {
            return new CountResult(0);
        }

        $nodeTraverser = new NodeTraverser(new NameResolver());
        $statements = $nodeTraverser->traverse($statements);

        $tests = 0;
        foreach ($this->nodeFinder->findInstanceOf($statements, ClassMethod::class) as $classMethod) {
            if ($this->isTestMethod($classMethod)) {
                ++$tests;
            }
        }

        if (0 === $tests) {
            return new CountResult(0);
        }

        $dependencies = [];
        foreach ($this->nodeFinder->findInstanceOf($statements, Node\Name::class) as $name) {
            $resolved = $name->getAttribute('resolvedName');
            if ($resolved instanceof FullyQualified) {
                $dependencies[$resolved->toString()] = true;

                continue;
            }

            if ($name instanceof FullyQualified) {
                $dependencies[$name->toString()] = true;
            }
        }

        return new CountResult($tests, array_keys($dependencies));
    }

    private function isTestMethod(ClassMethod $classMethod): bool
    {
        if (!$classMethod->isPublic()) {
            return false;
        }

        if (str_starts_with($classMethod->name->toString(), 'test')) {
            return true;
        }

        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $parts = $attr->name->getParts();
                if ('Test' === end($parts)) {
                    return true;
                }
            }
        }

        return false;
    }
}
