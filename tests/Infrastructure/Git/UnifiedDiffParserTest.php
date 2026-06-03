<?php

declare(strict_types=1);

namespace AhmedBhs\Pyra\Tests\Infrastructure\Git;

use AhmedBhs\Pyra\Domain\Diff\ChangeStatus;
use AhmedBhs\Pyra\Infrastructure\Git\UnifiedDiffParser;
use PHPUnit\Framework\TestCase;

final class UnifiedDiffParserTest extends TestCase
{
    public function testParseLesFichiersAjoutesModifiesEtSupprimes(): void
    {
        $diff = <<<'DIFF'
            diff --git a/src/Domain/Order.php b/src/Domain/Order.php
            index 111..222 100644
            --- a/src/Domain/Order.php
            +++ b/src/Domain/Order.php
            @@ -9,2 +9,3 @@ class Order
            -old
            +new
            +new2
            diff --git a/src/Domain/Invoice.php b/src/Domain/Invoice.php
            new file mode 100644
            index 000..333
            --- /dev/null
            +++ b/src/Domain/Invoice.php
            @@ -0,0 +1,5 @@
            +<?php
            diff --git a/src/Old.php b/src/Old.php
            deleted file mode 100644
            index 444..000
            --- a/src/Old.php
            +++ /dev/null
            @@ -1,3 +0,0 @@
            -gone
            DIFF;

        $changedFiles = (new UnifiedDiffParser())->parse($diff);

        self::assertCount(3, $changedFiles);

        self::assertSame('src/Domain/Order.php', $changedFiles[0]->path);
        self::assertSame(ChangeStatus::MODIFIED, $changedFiles[0]->status);
        self::assertCount(1, $changedFiles[0]->addedLineRanges);
        self::assertSame(9, $changedFiles[0]->addedLineRanges[0]->start);
        self::assertSame(11, $changedFiles[0]->addedLineRanges[0]->end);

        self::assertSame('src/Domain/Invoice.php', $changedFiles[1]->path);
        self::assertSame(ChangeStatus::ADDED, $changedFiles[1]->status);

        self::assertSame(ChangeStatus::DELETED, $changedFiles[2]->status);
        self::assertTrue($changedFiles[2]->isDeletion());
    }
}
