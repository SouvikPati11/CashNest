<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\FraudFlagRepositoryInterface;
use Core\Database\Database;

/**
 * Fraud flag repository — reads `fraud_flags` for the withdrawal hold gate.
 *
 * An open flag of high or critical severity holds the user's withdrawals until
 * an admin resolves it (DATABASE_DESIGN.md §L validation rules).
 */
final class FraudFlagRepository implements FraudFlagRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function hasActiveWithdrawHold(int $userId): bool
    {
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS aggregate FROM `fraud_flags`'
            . ' WHERE `user_id` = ? AND `status` IN (\'open\', \'reviewing\')'
            . ' AND `severity` IN (\'high\', \'critical\')',
            [$userId]
        );

        return (int) ($row['aggregate'] ?? 0) > 0;
    }
}
