<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * Offset pagination calculator for admin data tables.
 *
 * Immutable value object computing the current page, offset, total pages, and
 * navigation flags from a total count, per-page size, and a (clamped) page.
 */
final class Paginator
{
    public readonly int $page;
    public readonly int $perPage;
    public readonly int $total;
    public readonly int $totalPages;

    public function __construct(int $total, int $perPage, int $page)
    {
        $this->total      = max(0, $total);
        $this->perPage    = max(1, $perPage);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page       = min(max(1, $page), $this->totalPages);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    public function previousPage(): int
    {
        return max(1, $this->page - 1);
    }

    public function nextPage(): int
    {
        return min($this->totalPages, $this->page + 1);
    }

    /**
     * @return array<string, int|bool>
     */
    public function toArray(): array
    {
        return [
            'page'         => $this->page,
            'per_page'     => $this->perPage,
            'total'        => $this->total,
            'total_pages'  => $this->totalPages,
            'has_previous' => $this->hasPrevious(),
            'has_next'     => $this->hasNext(),
        ];
    }
}
