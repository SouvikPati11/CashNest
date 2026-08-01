<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Support\AdminResources;
use App\Admin\Support\Paginator;
use App\Contracts\AdminQueryRepositoryInterface;
use App\Exceptions\NotFoundException;

/**
 * Generic management browser service.
 *
 * Serves paginated, searchable list views for the registered content/config
 * resources through the generic query repository. The resource definition (table
 * + columns) is the trusted allowlist; the search term is bound.
 */
final class ResourceAdminService
{
    public function __construct(
        private AdminResources $resources,
        private AdminQueryRepositoryInterface $query,
        private AuditLogService $audit
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function list(string $key, ?string $search, int $page, int $perPage): array
    {
        $resource = $this->resources->get($key);

        if ($resource === null) {
            throw new NotFoundException('Unknown admin resource.');
        }

        $search = $search !== null ? trim($search) : '';
        $term   = $search !== '' ? $search : null;

        $total     = $this->query->countRows($resource->table, $resource->search, $term);
        $paginator = new Paginator($total, $perPage, $page);
        $rows      = $this->query->paginate(
            $resource->table,
            $resource->search,
            $term,
            $paginator->perPage,
            $paginator->offset()
        );

        return [
            'key'        => $key,
            'resource'   => $resource->toArray(),
            'permission' => $resource->permission,
            'rows'       => $rows,
            'paginator'  => $paginator,
            'search'     => $search,
        ];
    }

    /**
     * The permission required to edit the given editable resource.
     *
     * @throws NotFoundException when the resource is unknown or read-only
     */
    public function managePermission(string $key): string
    {
        return $this->editableResource($key)->managePermission;
    }

    /**
     * Load a single record for the edit form.
     *
     * @return array<string, mixed>
     *
     * @throws NotFoundException
     */
    public function find(string $key, int $id): array
    {
        $resource = $this->editableResource($key);
        $row      = $this->query->find($resource->table, $id);

        if ($row === null) {
            throw new NotFoundException('Record not found.');
        }

        return [
            'key'      => $key,
            'resource' => $resource->toArray(),
            'row'      => $row,
        ];
    }

    /**
     * Apply an edit to an editable resource, writing only allowlisted columns and
     * recording an audit entry.
     *
     * @param array<string, mixed> $input
     *
     * @throws NotFoundException
     */
    public function update(string $key, int $id, array $input, int $adminId, string $ip): void
    {
        $resource = $this->editableResource($key);

        if ($this->query->find($resource->table, $id) === null) {
            throw new NotFoundException('Record not found.');
        }

        $data = [];
        foreach ($resource->editable as $column) {
            if (array_key_exists($column, $input)) {
                $data[$column] = $input[$column];
            }
        }

        if ($data === []) {
            return;
        }

        $this->query->update($resource->table, $id, $data);

        $this->audit->log($adminId, 'resource.update', [
            'target_type' => $resource->table,
            'target_id'   => $id,
            'after'       => $data,
            'ip'          => $ip,
        ]);
    }

    private function editableResource(string $key): \App\Admin\Support\AdminResourceDef
    {
        $resource = $this->resources->get($key);

        if ($resource === null || !$resource->isEditable()) {
            throw new NotFoundException('Unknown or read-only admin resource.');
        }

        return $resource;
    }
}
