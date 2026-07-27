<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Email login service contract.
 *
 * Verifies email/password credentials and account state and returns the
 * authenticated user. Token/session issuance is explicitly out of scope for this
 * module (handled by the JWT module) — this service only authenticates.
 */
interface LoginServiceInterface
{
    /**
     * Authenticate a user by email + password.
     *
     * @throws \App\Exceptions\UnauthorizedException On invalid credentials.
     * @throws \App\Exceptions\ForbiddenException On blocked or unverified accounts.
     */
    public function login(string $email, string $password): User;
}
