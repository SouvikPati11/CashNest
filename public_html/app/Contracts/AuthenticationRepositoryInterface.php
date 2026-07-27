<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Authentication repository contract.
 *
 * Data-access abstraction for the `user_auth_providers` table. Returns raw
 * associative rows (or null); mapping to the AuthProvider model happens in the
 * service layer.
 */
interface AuthenticationRepositoryInterface
{
    /**
     * Find an identity by numeric id.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * Find an identity by provider + provider uid (e.g. google + sub).
     *
     * @return array<string, mixed>|null
     */
    public function findByProviderUid(string $provider, string $providerUid): ?array;

    /**
     * Find a user's identity for a specific provider.
     *
     * @return array<string, mixed>|null
     */
    public function findByUserAndProvider(int $userId, string $provider): ?array;

    /**
     * Find an email/password identity by email.
     *
     * @return array<string, mixed>|null
     */
    public function findEmailProviderByEmail(string $email): ?array;

    /**
     * Insert an identity row and return its new id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * Update the last-used timestamp of an identity; returns affected rows.
     */
    public function markUsed(int $id, string $timestamp): int;
}
