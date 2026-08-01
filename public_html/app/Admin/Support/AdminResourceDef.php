<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * Typed definition of a generic admin management resource.
 *
 * Keeps table/column identifiers strongly typed (they are the trusted SQL
 * allowlist) without verbose array-shape docblocks at every call site.
 *
 * [editable] lists the columns the generic editor may write; an empty list keeps
 * the resource read-only (list only). [managePermission] gates write actions.
 */
final class AdminResourceDef
{
    /**
     * @param array<int, string> $columns
     * @param array<int, string> $search
     * @param array<int, string> $editable
     */
    public function __construct(
        public readonly string $title,
        public readonly string $table,
        public readonly array $columns,
        public readonly array $search,
        public readonly string $permission,
        public readonly array $editable = [],
        public readonly string $managePermission = '',
        public readonly bool $creatable = false,
        public readonly bool $deletable = false
    ) {
    }

    public function isEditable(): bool
    {
        return $this->editable !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'             => $this->title,
            'table'            => $this->table,
            'columns'          => $this->columns,
            'search'           => $this->search,
            'permission'       => $this->permission,
            'editable'         => $this->editable,
            'managePermission' => $this->managePermission,
            'creatable'        => $this->creatable,
            'deletable'        => $this->deletable,
        ];
    }
}
