<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Spin wheel service contract.
 */
interface SpinServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function status(int $userId): array;

    /**
     * Perform a spin; server determines the outcome, credits via LedgerService.
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException LIMIT_REACHED when the daily cap is hit.
     */
    public function spin(int $userId, string $source): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function history(int $userId, array $params): array;
}
