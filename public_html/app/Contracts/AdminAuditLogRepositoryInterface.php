<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Admin audit log repository contract — append-only `admin_audit_logs`.
 */
interface AdminAuditLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * A page of audit entries, newest first, optionally filtered by action/admin.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginate(?string $action, ?int $adminId, int $limit, int $offset): array;

    public function countFiltered(?string $action, ?int $adminId): int;
}
