<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\DeviceTokenRepositoryInterface;
use Core\Database\Database;

/**
 * Device token repository — reads push-enabled FCM tokens from `user_devices`.
 */
final class DeviceTokenRepository implements DeviceTokenRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeTokensForUser(int $userId): array
    {
        $rows = $this->db->select(
            'SELECT `fcm_token` FROM `user_devices`'
            . ' WHERE `user_id` = ? AND `push_enabled` = 1 AND `fcm_token` IS NOT NULL',
            [$userId]
        );

        $tokens = [];
        foreach ($rows as $row) {
            $token = $row['fcm_token'] ?? null;
            if (is_string($token) && $token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }
}
