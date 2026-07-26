<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Referral repository contract (referrals + referral_config + referral_earnings).
 */
interface ReferralRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByReferee(int $refereeId): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function createReferral(array $data): string;

    /**
     * @param array<string, mixed> $data
     */
    public function updateReferral(int $id, array $data): int;

    /**
     * Referrals owned by a referrer, with referee display name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForReferrer(int $referrerId, int $limit, int $offset, ?string $status): array;

    public function countForReferrer(int $referrerId): int;

    public function countForReferrerByStatus(int $referrerId, string $status): int;

    /**
     * @return array<string, mixed>|null
     */
    public function activeConfig(): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function createEarning(array $data): string;

    /**
     * @return array<string, mixed>|null
     */
    public function findEarningBySourceTxn(int $sourceTransactionId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEarningsForReferrer(int $referrerId, int $limit, int $offset): array;

    public function sumEarningsForReferrer(int $referrerId): int;
}
