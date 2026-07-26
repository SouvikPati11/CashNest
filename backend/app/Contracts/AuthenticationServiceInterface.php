<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AuthProvider;

/**
 * Authentication service contract.
 *
 * Reusable authentication primitives: password hashing/verification and CRUD of
 * provider identities. These are the building blocks that later auth flows
 * (email/Google login, JWT issuance, etc.) will orchestrate — this milestone
 * deliberately does NOT implement any full login flow or token issuance.
 */
interface AuthenticationServiceInterface
{
    /**
     * Hash a plaintext password using the platform's strongest algorithm.
     */
    public function hashPassword(string $plain): string;

    /**
     * Verify a plaintext password against a stored hash (constant time).
     */
    public function verifyPassword(string $plain, string $hash): bool;

    /**
     * Whether a stored hash should be re-hashed (cost/algorithm upgrade).
     */
    public function passwordNeedsRehash(string $hash): bool;

    /**
     * Create and persist a provider identity for a user.
     *
     * @param array<string, mixed> $attributes
     */
    public function createProvider(array $attributes): AuthProvider;

    /**
     * Find an identity by provider + provider uid.
     */
    public function findProviderByUid(string $provider, string $providerUid): ?AuthProvider;

    /**
     * Find an email/password identity by email.
     */
    public function findEmailProvider(string $email): ?AuthProvider;

    /**
     * Find a user's identity for a specific provider.
     */
    public function findUserProvider(int $userId, string $provider): ?AuthProvider;

    /**
     * Mark an identity as just used (updates last_used_at).
     */
    public function markProviderUsed(int $providerId): void;
}
