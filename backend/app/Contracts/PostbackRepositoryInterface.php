<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Postback pipeline repository contract (offer_clicks + offer_conversions +
 * postback_logs) — the data behind offerwall attribution and crediting.
 */
interface PostbackRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function createClick(array $data): string;

    /**
     * @return array<string, mixed>|null
     */
    public function findClickByToken(string $token): ?array;

    /**
     * Idempotency lookup: has this provider transaction already been recorded?
     *
     * @return array<string, mixed>|null
     */
    public function findConversion(int $providerId, string $transactionIdExt): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function createConversion(array $data): string;

    /**
     * @param array<string, mixed> $data
     */
    public function updateConversion(int $id, array $data): int;

    /**
     * Append a raw postback audit row.
     *
     * @param array<string, mixed> $data
     */
    public function logPostback(array $data): string;
}
