<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Email registration service contract.
 *
 * Orchestrates email/password sign-up: uniqueness check, optional referral
 * resolution, atomic creation of the user + email identity, and dispatch of the
 * verification credentials. Returns the newly created (unverified) user.
 */
interface RegistrationServiceInterface
{
    /**
     * Register a new email/password account.
     *
     * @param array<string, mixed> $data Validated input (name, email, password, referral_code?, registration_ip?).
     */
    public function register(array $data): User;
}
