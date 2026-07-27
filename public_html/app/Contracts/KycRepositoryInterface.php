<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * KYC repository contract — reads `user_kyc`.
 *
 * Used by the Withdraw module to gate payouts on verification status. The
 * encrypted document number is never read or returned here.
 */
interface KycRepositoryInterface
{
    /**
     * The user's KYC record, if one exists.
     *
     * @return array<string, mixed>|null
     */
    public function findByUserId(int $userId): ?array;
}
