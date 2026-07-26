<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Session repository contract.
 *
 * Data access for the `user_sessions` table (DATABASE_DESIGN.md §A.4): refresh
 * sessions holding only the hash of the refresh token (never the raw token).
 */
interface SessionRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * Look up a session by the SHA-256 hash of its refresh token.
     *
     * @return array<string, mixed>|null
     */
    public function findByRefreshHash(string $hash): ?array;

    /**
     * Insert a session row and return its new id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * Mark a single session revoked; returns affected rows.
     */
    public function revoke(int $id, string $timestamp): int;

    /**
     * Revoke all of a user's still-active sessions; returns affected rows.
     */
    public function revokeAllForUser(int $userId, string $timestamp): int;
}
