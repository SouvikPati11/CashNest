<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;

/**
 * User repository.
 *
 * Concrete data access for the `users` table. Inherits generic CRUD (find,
 * create, update, count) from BaseRepository — which uses parameterised queries
 * and validates identifiers — and adds user-specific finders.
 */
final class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected string $table = 'users';

    protected string $primaryKey = 'id';

    public function findByUuid(string $uuid): ?array
    {
        return $this->findBy('uuid', $uuid);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    public function findByReferralCode(string $code): ?array
    {
        return $this->findBy('referral_code', $code);
    }

    public function existsByEmail(string $email): bool
    {
        return $this->count(['email' => $email]) > 0;
    }

    public function existsByReferralCode(string $code): bool
    {
        return $this->count(['referral_code' => $code]) > 0;
    }
}
