<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AnnouncementRepositoryInterface;

/**
 * In-memory announcement repository for DB-free service tests, including per-user
 * dismissal state.
 */
final class InMemoryAnnouncementRepository implements AnnouncementRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, bool> Keyed by "announcementId:userId". */
    public array $seen = [];

    private int $nextId = 1;

    /**
     * @param array<string, mixed> $data
     */
    public function seed(array $data): int
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge([
            'id'           => $id,
            'title'        => 'Notice',
            'body'         => 'Body',
            'display_type' => 'popup',
            'priority'     => 100,
            'is_active'    => 1,
        ], $data, ['id' => $id]);

        return $id;
    }

    public function activeForUser(int $userId, string $now): array
    {
        $rows = array_values(array_filter($this->rows, function (array $r) use ($userId): bool {
            if ((int) $r['is_active'] !== 1) {
                return false;
            }

            return !isset($this->seen[$r['id'] . ':' . $userId]);
        }));

        usort($rows, static fn(array $a, array $b): int => (int) $a['priority'] <=> (int) $b['priority']);

        return $rows;
    }

    public function findActive(int $id, string $now): ?array
    {
        $row = $this->rows[$id] ?? null;

        return $row !== null && (int) $row['is_active'] === 1 ? $row : null;
    }

    public function markSeen(int $announcementId, int $userId, string $seenAt): void
    {
        $this->seen[$announcementId . ':' . $userId] = true;
    }
}
