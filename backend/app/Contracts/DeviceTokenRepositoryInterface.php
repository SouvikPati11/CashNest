<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Device token repository contract — reads push-enabled FCM tokens.
 *
 * Used by the push dispatch job to resolve a user's delivery targets from
 * `user_devices`; controllers never touch tokens directly.
 */
interface DeviceTokenRepositoryInterface
{
    /**
     * Active FCM tokens for a user's push-enabled devices.
     *
     * @return array<int, string>
     */
    public function activeTokensForUser(int $userId): array;
}
