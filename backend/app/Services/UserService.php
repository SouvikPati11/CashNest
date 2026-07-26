<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Helpers\Security;
use App\Models\User;
use Core\Contracts\LoggerInterface;

/**
 * User service.
 *
 * Business operations on user accounts. Depends on the repository interface
 * (not the concrete class) for testability. Provides reusable primitives used by
 * later auth flows — it does not itself implement any login/registration flow.
 */
final class UserService extends BaseService implements UserServiceInterface
{
    /** Fields a client may set at creation time (everything else is derived/guarded). */
    private const CREATABLE_FIELDS = [
        'name',
        'email',
        'phone',
        'avatar_url',
        'referred_by',
        'country_code',
        'locale',
        'registration_ip',
    ];

    /** Fields a client may change via profile update. */
    private const PROFILE_FIELDS = [
        'name',
        'avatar_url',
        'country_code',
        'locale',
        'phone',
    ];

    private const REFERRAL_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const REFERRAL_LENGTH   = 8;

    public function __construct(
        private UserRepositoryInterface $users,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    public function createUser(array $attributes): User
    {
        $data = [];

        foreach (self::CREATABLE_FIELDS as $field) {
            if (array_key_exists($field, $attributes)) {
                $data[$field] = $attributes[$field];
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = $this->normaliseEmail($data['email']);
        }

        // Server-controlled fields always override any client-supplied value.
        $data['uuid']               = Security::uuid4();
        $data['referral_code']      = $this->generateReferralCode();
        $data['status']             = $this->resolveStatus($attributes['status'] ?? null);
        $data['coin_balance_cache'] = 0;
        $data['cash_balance_cache'] = '0.0000';

        $id = (int) $this->users->create($data);

        $this->logger->info('User created.', ['user_id' => $id]);

        return $this->findById($id) ?? User::fromRow($data + ['id' => $id]);
    }

    public function findById(int $id): ?User
    {
        $row = $this->users->find($id);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByUuid(string $uuid): ?User
    {
        $row = $this->users->findByUuid($uuid);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->users->findByEmail($this->normaliseEmail($email));

        return $row === null ? null : User::fromRow($row);
    }

    public function emailExists(string $email): bool
    {
        return $this->users->existsByEmail($this->normaliseEmail($email));
    }

    public function updateProfile(string $uuid, array $attributes): ?User
    {
        $user = $this->findByUuid($uuid);

        if ($user === null) {
            return null;
        }

        $data = [];

        foreach (self::PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $attributes)) {
                $data[$field] = $attributes[$field];
            }
        }

        $id = $user->id();

        if ($data !== [] && $id !== null) {
            $this->users->update($id, $data);
        }

        return $this->findByUuid($uuid);
    }

    public function touchLastLogin(int $userId): void
    {
        $this->users->update($userId, ['last_login_at' => gmdate('Y-m-d H:i:s')]);
    }

    public function generateReferralCode(): string
    {
        $max  = strlen(self::REFERRAL_ALPHABET) - 1;
        $code = '';

        // Bounded attempts so a saturated namespace can never loop forever.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = '';

            for ($i = 0; $i < self::REFERRAL_LENGTH; $i++) {
                $code .= self::REFERRAL_ALPHABET[random_int(0, $max)];
            }

            if (!$this->users->existsByReferralCode($code)) {
                return $code;
            }
        }

        // Extremely unlikely fallback: append entropy to guarantee uniqueness.
        return substr($code . strtoupper(bin2hex(random_bytes(4))), 0, 12);
    }

    /**
     * Lowercase + trim an email for consistent storage/lookup.
     */
    private function normaliseEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Validate a requested status, defaulting to active when absent/invalid.
     */
    private function resolveStatus(mixed $status): string
    {
        return is_string($status) && in_array($status, User::STATUSES, true)
            ? $status
            : User::STATUS_ACTIVE;
    }
}
