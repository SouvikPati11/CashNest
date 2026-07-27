<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Referral read/apply service contract.
 */
interface ReferralServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function overview(int $userId): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function listReferrals(int $userId, array $params): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function earnings(int $userId, array $params): array;

    /**
     * Apply a referral code as the current user.
     *
     * @return array{applied: bool}
     *
     * @throws \App\Exceptions\HttpException On invalid code, self-referral, or already referred.
     */
    public function apply(int $userId, string $code, ?string $ip): array;
}
