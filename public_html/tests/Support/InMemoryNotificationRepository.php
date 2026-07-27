<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\NotificationRepositoryInterface;

/**
 * In-memory notification repository for DB-free service/job tests.
 */
final class InMemoryNotificationRepository implements NotificationRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function create(array $data): string
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge($data, [
            'id'         => $id,
            'created_at' => $data['created_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);

        return (string) $id;
    }

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $row = $this->rows[$id] ?? null;

        return $row !== null && (int) $row['user_id'] === $userId ? $row : null;
    }

    public function listForUser(int $userId, int $limit, int $offset, ?string $type, ?bool $isRead): array
    {
        $rows = array_values(array_filter($this->rows, static function (array $r) use ($userId, $type, $isRead): bool {
            if ((int) $r['user_id'] !== $userId) {
                return false;
            }
            if ($type !== null && $type !== '' && ($r['type'] ?? null) !== $type) {
                return false;
            }
            if ($isRead !== null && (bool) ($r['is_read'] ?? false) !== $isRead) {
                return false;
            }

            return true;
        }));

        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }

    public function unreadCount(int $userId): int
    {
        $count = 0;
        foreach ($this->rows as $row) {
            if ((int) $row['user_id'] === $userId && !(bool) ($row['is_read'] ?? false)) {
                $count++;
            }
        }

        return $count;
    }

    public function markRead(int $id, int $userId, string $readAt): int
    {
        $row = $this->rows[$id] ?? null;
        if ($row === null || (int) $row['user_id'] !== $userId || (bool) ($row['is_read'] ?? false)) {
            return 0;
        }

        $this->rows[$id]['is_read'] = 1;
        $this->rows[$id]['read_at'] = $readAt;

        return 1;
    }

    public function markAllRead(int $userId, string $readAt): int
    {
        $updated = 0;
        foreach ($this->rows as $id => $row) {
            if ((int) $row['user_id'] === $userId && !(bool) ($row['is_read'] ?? false)) {
                $this->rows[$id]['is_read'] = 1;
                $this->rows[$id]['read_at'] = $readAt;
                $updated++;
            }
        }

        return $updated;
    }

    public function updatePushStatus(int $id, string $pushStatus): int
    {
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id]['push_status'] = $pushStatus;

        return 1;
    }
}
