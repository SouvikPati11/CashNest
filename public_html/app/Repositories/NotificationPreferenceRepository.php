<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\NotificationPreferenceRepositoryInterface;
use Core\Database\Database;

/**
 * Notification preference repository.
 *
 * Reads and writes only the notification toggle columns of `user_settings`.
 * A settings row is created lazily on first write (the Settings module, which
 * owns the full row lifecycle, is out of scope here). Column names are guarded
 * against a fixed allowlist before use in SQL.
 */
final class NotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    /** Toggle columns this repository is permitted to read/write. */
    private const TOGGLE_COLUMNS = [
        'notif_push_enabled',
        'notif_transactional',
        'notif_promotional',
    ];

    public function __construct(private Database $db)
    {
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->selectOne('SELECT * FROM `user_settings` WHERE `user_id` = ? LIMIT 1', [$userId]);
    }

    public function upsertToggles(int $userId, array $toggles): void
    {
        $toggles = $this->filterColumns($toggles);

        if ($toggles === []) {
            return;
        }

        if ($this->findByUserId($userId) === null) {
            $this->insert($userId, $toggles);

            return;
        }

        $this->updateToggles($userId, $toggles);
    }

    /**
     * @param array<string, int> $toggles
     */
    private function insert(int $userId, array $toggles): void
    {
        $columns  = array_merge(['user_id'], array_keys($toggles));
        $bindings = array_merge([$userId], array_values($toggles));

        $columnList   = implode(', ', array_map(static fn(string $c): string => "`$c`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $this->db->insert(
            sprintf('INSERT INTO `user_settings` (%s) VALUES (%s)', $columnList, $placeholders),
            $bindings
        );
    }

    /**
     * @param array<string, int> $toggles
     */
    private function updateToggles(int $userId, array $toggles): void
    {
        $assignments = [];
        $bindings    = [];

        foreach ($toggles as $column => $value) {
            $assignments[] = "`$column` = ?";
            $bindings[]    = $value;
        }

        $bindings[] = $userId;

        $this->db->affectingStatement(
            sprintf('UPDATE `user_settings` SET %s WHERE `user_id` = ?', implode(', ', $assignments)),
            $bindings
        );
    }

    /**
     * Keep only recognised toggle columns coerced to 0/1.
     *
     * @param array<string, int> $toggles
     * @return array<string, int>
     */
    private function filterColumns(array $toggles): array
    {
        $filtered = [];

        foreach (self::TOGGLE_COLUMNS as $column) {
            if (array_key_exists($column, $toggles)) {
                $filtered[$column] = $toggles[$column] ? 1 : 0;
            }
        }

        return $filtered;
    }
}
