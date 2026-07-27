<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Scratch card service contract.
 */
interface ScratchServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function available(int $userId): array;

    /**
     * Reveal a card; the server decides the reward (no credit yet).
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException NOT_FOUND / RESOURCE_CONFLICT / EXPIRED.
     */
    public function reveal(int $userId, int $cardId): array;

    /**
     * Claim a revealed card (credits via LedgerService).
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException NOT_FOUND / RESOURCE_CONFLICT / EXPIRED.
     */
    public function claim(int $userId, int $cardId): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function history(int $userId, array $params): array;
}
