<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\NotificationPreferenceRepositoryInterface;
use App\Contracts\NotificationPreferenceServiceInterface;
use App\Models\Notification;

/**
 * Notification preference service.
 *
 * Serves and updates the notification toggles stored in `user_settings` and
 * answers whether a notification type may be pushed. Defaults follow the schema
 * (all enabled) when a user has no settings row yet.
 */
final class NotificationPreferenceService implements NotificationPreferenceServiceInterface
{
    public function __construct(private NotificationPreferenceRepositoryInterface $preferences)
    {
    }

    public function get(int $userId): array
    {
        $row = $this->preferences->findByUserId($userId);

        return [
            'notif_push_enabled'  => $this->flag($row, 'notif_push_enabled'),
            'notif_transactional' => $this->flag($row, 'notif_transactional'),
            'notif_promotional'   => $this->flag($row, 'notif_promotional'),
        ];
    }

    public function update(int $userId, array $changes): array
    {
        $toggles = [];

        foreach (['notif_push_enabled', 'notif_transactional', 'notif_promotional'] as $key) {
            if (array_key_exists($key, $changes)) {
                $toggles[$key] = $this->toBool($changes[$key]) ? 1 : 0;
            }
        }

        if ($toggles !== []) {
            $this->preferences->upsertToggles($userId, $toggles);
        }

        return $this->get($userId);
    }

    public function allowsPush(int $userId, string $notificationType): bool
    {
        $prefs = $this->get($userId);

        if (!$prefs['notif_push_enabled']) {
            return false;
        }

        return match ($notificationType) {
            Notification::TYPE_TRANSACTIONAL => $prefs['notif_transactional'],
            Notification::TYPE_PROMOTIONAL   => $prefs['notif_promotional'],
            default                          => true,
        };
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function flag(?array $row, string $column): bool
    {
        if ($row === null || !array_key_exists($column, $row)) {
            return true; // schema default is enabled
        }

        return (bool) $row[$column];
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
