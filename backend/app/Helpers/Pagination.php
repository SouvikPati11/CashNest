<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Pagination helper.
 *
 * Implements the pagination standard from API_SPECIFICATION.md §1.8: parses
 * `limit`/`page`/`cursor` inputs (clamped to safe bounds) and builds the
 * `meta.pagination` block for responses.
 */
final class Pagination
{
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT     = 100;

    /**
     * @param int         $limit  Clamped page size.
     * @param int         $page   1-based page number (offset mode).
     * @param string|null $cursor Opaque forward cursor.
     */
    private function __construct(
        public readonly int $limit,
        public readonly int $page,
        public readonly ?string $cursor
    ) {
    }

    /**
     * Build from raw query parameters.
     *
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $limit = (int) ($query['limit'] ?? self::DEFAULT_LIMIT);
        $limit = max(1, min($limit, self::MAX_LIMIT));

        $page = (int) ($query['page'] ?? 1);
        $page = max(1, $page);

        $cursor = isset($query['cursor']) && is_string($query['cursor']) && $query['cursor'] !== ''
            ? $query['cursor']
            : null;

        return new self($limit, $page, $cursor);
    }

    /**
     * SQL OFFSET for offset-mode pagination.
     */
    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Build the meta.pagination block.
     *
     * @param int      $count      Number of rows returned this page.
     * @param int|null $total      Total rows (optional; omit for huge tables).
     * @param string|null $nextCursor Cursor for the next page (cursor mode).
     * @param string|null $prevCursor Cursor for the previous page (cursor mode).
     * @return array{pagination: array<string, mixed>}
     */
    public function meta(
        int $count,
        ?int $total = null,
        ?string $nextCursor = null,
        ?string $prevCursor = null
    ): array {
        $hasMore = $nextCursor !== null
            ? true
            : ($total !== null ? ($this->offset() + $count) < $total : $count === $this->limit);

        $pagination = [
            'limit'       => $this->limit,
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor,
            'has_more'    => $hasMore,
        ];

        if ($nextCursor === null && $prevCursor === null) {
            $pagination['page'] = $this->page;
        }

        if ($total !== null) {
            $pagination['total'] = $total;
        }

        return ['pagination' => $pagination];
    }
}
