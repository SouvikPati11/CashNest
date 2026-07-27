<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * User repository contract.
 *
 * Data-access abstraction for the `users` table. Returns raw associative rows
 * (or null); mapping to the User model happens in the service layer. Depending
 * on this interface (not the concrete repository) keeps the service layer
 * testable and swappable (Dependency Inversion).
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by numeric id.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * Find a user by public uuid.
     *
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $uuid): ?array;

    /**
     * Find a user by email (case-insensitive; caller normalises).
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array;

    /**
     * Find a user by referral code.
     *
     * @return array<string, mixed>|null
     */
    public function findByReferralCode(string $code): ?array;

    /**
     * Whether a user exists with the given email.
     */
    public function existsByEmail(string $email): bool;

    /**
     * Whether a referral code is already taken.
     */
    public function existsByReferralCode(string $code): bool;

    /**
     * Insert a user row and return its new id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * Update a user by id; returns affected row count.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): int;
}
