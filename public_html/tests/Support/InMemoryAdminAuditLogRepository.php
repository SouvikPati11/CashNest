<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AdminAuditLogRepositoryInterface;

/**
 * In-memory admin audit log repository for DB-free tests.
 */
final class InMemoryAdminAuditLogRepository implements AdminAuditLogRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function create(array $data): string
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge($data, ['id' => $id, 'created_at' => gmdate('Y-m-d H:i:s')]);

        return (string) $id;
    }

    public function paginate(?string $action, ?int $adminId, int $limit, int $offset): array
    {
        $rows = $this->filtered($action, $adminId);
        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }

    public function countFiltered(?string $action, ?int $adminId): int
    {
        return count($this->filtered($action, $adminId));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filtered(?string $action, ?int $adminId): array
    {
        return array_values(array_filter($this->rows, static function (array $r) use ($action, $adminId): bool {
            if ($action !== null && ($r['action'] ?? null) !== $action) {
                return false;
            }

            return $adminId === null || (int) ($r['admin_id'] ?? 0) === $adminId;
        }));
    }
}
