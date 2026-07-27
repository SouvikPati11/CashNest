<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserSettingsRepositoryInterface;
use Core\Database\Database;

/**
 * User settings repository — owns `user_settings`.
 *
 * Writes are restricted to a fixed allowlist of preference columns and a row is
 * created lazily on first write.
 */
final class UserSettingsRepository implements UserSettingsRepositoryInterface
{
    /** Columns callers may write. */
    private const WRITABLE = [
        'notif_push_enabled',
        'notif_transactional',
        'notif_promotional',
        'language',
        'theme_mode',
    ];

    public function __construct(private Database $db)
    {
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->selectOne('SELECT * FROM `user_settings` WHERE `user_id` = ? LIMIT 1', [$userId]);
    }

    public function upsert(int $userId, array $data): void
    {
        $data = array_intersect_key($data, array_flip(self::WRITABLE));

        if ($data === []) {
            return;
        }

        if ($this->findByUserId($userId) === null) {
            $this->insert($userId, $data);

            return;
        }

        $this->updateColumns($userId, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insert(int $userId, array $data): void
    {
        $columns  = array_merge(['user_id'], array_keys($data));
        $bindings = array_merge([$userId], array_values($data));

        $columnList   = implode(', ', array_map(static fn(string $c): string => "`$c`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $this->db->insert(
            sprintf('INSERT INTO `user_settings` (%s) VALUES (%s)', $columnList, $placeholders),
            $bindings
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateColumns(int $userId, array $data): void
    {
        $assignments = [];
        $bindings    = [];

        foreach ($data as $column => $value) {
            $assignments[] = "`$column` = ?";
            $bindings[]    = $value;
        }

        $bindings[] = $userId;

        $this->db->affectingStatement(
            sprintf('UPDATE `user_settings` SET %s WHERE `user_id` = ?', implode(', ', $assignments)),
            $bindings
        );
    }
}
