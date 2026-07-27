<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * Typed definition of a generic admin management resource.
 *
 * Keeps table/column identifiers strongly typed (they are the trusted SQL
 * allowlist) without verbose array-shape docblocks at every call site.
 */
final class AdminResourceDef
{
    /**
     * @param array<int, string> $columns
     * @param array<int, string> $search
     */
    public function __construct(
        public readonly string $title,
        public readonly string $table,
        public readonly array $columns,
        public readonly array $search,
        public readonly string $permission
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'      => $this->title,
            'table'      => $this->table,
            'columns'    => $this->columns,
            'search'     => $this->search,
            'permission' => $this->permission,
        ];
    }
}
