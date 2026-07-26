<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * User service contract.
 *
 * Business operations on user accounts, returning typed User models. These are
 * reusable primitives (persist a user, look one up, update a profile) — NOT
 * end-to-end auth flows, which are built in later milestones.
 */
interface UserServiceInterface
{
    /**
     * Create and persist a new user, assigning uuid, referral code, and defaults.
     *
     * @param array<string, mixed> $attributes
     */
    public function createUser(array $attributes): User;

    /**
     * Find a user by numeric id.
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by public uuid.
     */
    public function findByUuid(string $uuid): ?User;

    /**
     * Find a user by email (normalised internally).
     */
    public function findByEmail(string $email): ?User;

    /**
     * Whether an account already uses the given email.
     */
    public function emailExists(string $email): bool;

    /**
     * Update a user's editable profile fields, returning the refreshed model.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateProfile(string $uuid, array $attributes): ?User;

    /**
     * Record a successful login timestamp for a user.
     */
    public function touchLastLogin(int $userId): void;

    /**
     * Generate a unique, collision-checked referral code.
     */
    public function generateReferralCode(): string;
}
