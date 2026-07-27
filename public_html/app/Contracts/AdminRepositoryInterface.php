<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Admin account repository contract — reads/updates `admins`.
 */
interface AdminRepositoryInterface
{
    /**
     * An active (non-deleted) admin by login email.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByEmail(string $email): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * Record a successful login (timestamp + IP).
     */
    public function recordLogin(int $id, string $ip, string $at): void;
}
