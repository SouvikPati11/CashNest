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
        private AdminQueryRepositoryInterface $query
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
}
