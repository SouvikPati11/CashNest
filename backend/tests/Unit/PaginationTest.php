<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testDefaults(): void
    {
        $pagination = Pagination::fromQuery([]);

        self::assertSame(Pagination::DEFAULT_LIMIT, $pagination->limit);
        self::assertSame(1, $pagination->page);
        self::assertNull($pagination->cursor);
    }

    public function testLimitIsClamped(): void
    {
        $pagination = Pagination::fromQuery(['limit' => 5000]);

        self::assertSame(Pagination::MAX_LIMIT, $pagination->limit);
    }

    public function testOffsetCalculation(): void
    {
        $pagination = Pagination::fromQuery(['page' => 3, 'limit' => 20]);

        self::assertSame(40, $pagination->offset());
    }

    public function testMetaWithTotal(): void
    {
        $pagination = Pagination::fromQuery(['page' => 1, 'limit' => 20]);
        $meta       = $pagination->meta(20, 100);

        self::assertTrue($meta['pagination']['has_more']);
        self::assertSame(100, $meta['pagination']['total']);
    }

    public function testMetaWithCursor(): void
    {
        $pagination = Pagination::fromQuery(['limit' => 20]);
        $meta       = $pagination->meta(20, null, 'next123');

        self::assertSame('next123', $meta['pagination']['next_cursor']);
        self::assertTrue($meta['pagination']['has_more']);
    }
}
