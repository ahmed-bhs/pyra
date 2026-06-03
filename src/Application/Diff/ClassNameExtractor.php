<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Application\Diff;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

final class ClassNameExtractor
{
    private readonly \PhpParser\Parser $parser;

    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * @return list<string> fully-qualified names declared in the file
     */
    public function extract(string $code): array
    {
        $statements = $this->parser->parse($code);

        if (null === $statements) {
            return [];
        }

        $nodeTraverser = new NodeTraverser(new NameResolver());
        $statements = $nodeTraverser->traverse($statements);

        $names = [];
        foreach ([Class_::class, Interface_::class, Trait_::class, Enum_::class] as $nodeType) {
            foreach ($this->nodeFinder->findInstanceOf($statements, $nodeType) as $node) {
                if (isset($node->namespacedName)) {
                    $names[] = $node->namespacedName->toString();
                }
            }
        }

        return array_values(array_unique($names));
    }
}
