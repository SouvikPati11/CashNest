<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Daily check-in service contract.
 */
interface CheckinServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function status(int $userId): array;

    /**
     * @return array<string, mixed>
     */
    public function calendar(int $userId): array;

    /**
     * Claim today's check-in (credits via LedgerService).
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException ALREADY_CLAIMED when today is claimed.
     */
    public function claim(int $userId): array;
}
