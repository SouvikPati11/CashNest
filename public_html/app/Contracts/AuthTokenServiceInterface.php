<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Auth token service contract.
 *
 * Issues JWT access tokens plus opaque, hashed, rotating refresh tokens backed by
 * the sessions table, and handles refresh (with reuse detection) and revocation.
 *
 * @phpstan-type TokenPair array{access_token: string, token_type: string, expires_in: int, refresh_token: string}
 */
interface AuthTokenServiceInterface
{
    /**
     * Issue a new access + refresh token pair and persist the session.
     *
     * @return TokenPair
     */
    public function issueTokens(User $user, ?string $ip, ?string $userAgent): array;

    /**
     * Rotate a refresh token: validate, revoke the old session, issue a new pair.
     *
     * @return TokenPair
     *
     * @throws \App\Exceptions\UnauthorizedException On invalid/expired/reused tokens.
     * @throws \App\Exceptions\ForbiddenException On blocked accounts.
     */
    public function refresh(string $refreshToken): array;

    /**
     * Revoke the session identified by a refresh token (idempotent).
     */
    public function logout(string $refreshToken): void;

    /**
     * Revoke all active sessions for a user.
     */
    public function logoutAll(int $userId): void;
}
