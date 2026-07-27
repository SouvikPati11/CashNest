<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Notification preference service contract.
 *
 * Serves and updates a user's notification toggles (push master, transactional,
 * promotional) and answers whether a given notification type may be pushed.
 */
interface NotificationPreferenceServiceInterface
{
    /**
     * The user's current notification preferences.
     *
     * @return array{notif_push_enabled: bool, notif_transactional: bool, notif_promotional: bool}
     */
    public function get(int $userId): array;

    /**
     * Apply a partial update of the toggles and return the new state.
     *
     * @param array<string, mixed> $changes Subset of the toggle keys.
     * @return array{notif_push_enabled: bool, notif_transactional: bool, notif_promotional: bool}
     */
    public function update(int $userId, array $changes): array;

    /**
     * Whether a notification of the given type may be delivered as a push,
     * honoring the master toggle and per-category preferences.
     */
    public function allowsPush(int $userId, string $notificationType): bool;
}
