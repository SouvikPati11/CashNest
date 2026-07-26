<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Task service contract.
 */
interface TaskServiceInterface
{
    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function listTasks(int $userId, array $params): array;

    /**
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function detail(int $userId, int $taskId): array;

    /**
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException LIMIT_REACHED / RESOURCE_CONFLICT / NOT_FOUND.
     */
    public function start(int $userId, int $taskId): array;

    /**
     * Complete a task; auto-verified tasks credit via LedgerService.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException RESOURCE_CONFLICT / NOT_FOUND.
     */
    public function complete(int $userId, int $taskId, array $data): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function history(int $userId, array $params): array;
}
