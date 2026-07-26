<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthenticationRepositoryInterface;
use App\Contracts\AuthenticationServiceInterface;
use App\Helpers\Security;
use App\Models\AuthProvider;
use Core\Contracts\LoggerInterface;

/**
 * Authentication service.
 *
 * Reusable authentication primitives: password hashing/verification and CRUD of
 * provider identities. It does NOT implement any end-to-end login flow, token
 * issuance, refresh, verification, or password reset — those are later
 * milestones that orchestrate these primitives.
 */
final class AuthenticationService extends BaseService implements AuthenticationServiceInterface
{
    /** Fields accepted when creating a provider identity. */
    private const PROVIDER_FIELDS = [
        'user_id',
        'provider',
        'provider_uid',
        'password_hash',
        'email',
        'is_primary',
    ];

    public function __construct(
        private AuthenticationRepositoryInterface $providers,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    public function hashPassword(string $plain): string
    {
        return Security::hashPassword($plain);
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return Security::verifyPassword($plain, $hash);
    }

    public function passwordNeedsRehash(string $hash): bool
    {
        return Security::passwordNeedsRehash($hash);
    }

    public function createProvider(array $attributes): AuthProvider
    {
        $provider = $attributes['provider'] ?? null;

        if (!is_string($provider) || !in_array($provider, AuthProvider::PROVIDERS, true)) {
            throw new \InvalidArgumentException('A valid provider (google|email) is required.');
        }

        $data = [];

        foreach (self::PROVIDER_FIELDS as $field) {
            if (array_key_exists($field, $attributes)) {
                $data[$field] = $attributes[$field];
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        $data['is_primary'] = !empty($attributes['is_primary']) ? 1 : 0;

        $id = (int) $this->providers->create($data);

        $this->logger->info('Auth provider created.', [
            'provider'      => $provider,
            'user_id'       => $data['user_id'] ?? null,
            'auth_provider' => $id,
        ]);

        $row = $this->providers->find($id);

        return $row === null ? AuthProvider::fromRow($data + ['id' => $id]) : AuthProvider::fromRow($row);
    }

    public function findProviderByUid(string $provider, string $providerUid): ?AuthProvider
    {
        $row = $this->providers->findByProviderUid($provider, $providerUid);

        return $row === null ? null : AuthProvider::fromRow($row);
    }

    public function findEmailProvider(string $email): ?AuthProvider
    {
        $row = $this->providers->findEmailProviderByEmail(strtolower(trim($email)));

        return $row === null ? null : AuthProvider::fromRow($row);
    }

    public function findUserProvider(int $userId, string $provider): ?AuthProvider
    {
        $row = $this->providers->findByUserAndProvider($userId, $provider);

        return $row === null ? null : AuthProvider::fromRow($row);
    }

    public function markProviderUsed(int $providerId): void
    {
        $this->providers->markUsed($providerId, gmdate('Y-m-d H:i:s'));
    }
}
