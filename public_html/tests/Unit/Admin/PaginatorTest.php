<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Support\Paginator;
use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase
{
    public function testComputesPagesAndOffset(): void
    {
        $p = new Paginator(45, 20, 2);

        self::assertSame(3, $p->totalPages);
        self::assertSame(2, $p->page);
        self::assertSame(20, $p->offset());
        self::assertTrue($p->hasPrevious());
        self::assertTrue($p->hasNext());
    }

    public function testClampsPageIntoRange(): void
    {
        self::assertSame(3, (new Paginator(45, 20, 99))->page);
        self::assertSame(1, (new Paginator(45, 20, 0))->page);
    }

    public function testEmptyResultHasOnePage(): void
    {
        $p = new Paginator(0, 20, 1);

        self::assertSame(1, $p->totalPages);
        self::assertSame(0, $p->offset());
        self::assertFalse($p->hasNext());
        self::assertFalse($p->hasPrevious());
    }
}
