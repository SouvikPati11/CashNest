<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Google login/registration orchestration contract.
 *
 * @phpstan-import-type TokenPair from AuthTokenServiceInterface
 */
interface GoogleLoginServiceInterface
{
    /**
     * Authenticate via a Google ID token, creating or linking the account as
     * needed, and issue app tokens.
     *
     * @return array{user: User, tokens: TokenPair, is_new: bool}
     *
     * @throws \App\Exceptions\UnauthorizedException On invalid Google token.
     * @throws \App\Exceptions\ForbiddenException On blocked accounts.
     * @throws \App\Exceptions\ValidationException On invalid referral code.
     */
    public function login(string $idToken, ?string $referralCode, ?string $ip, ?string $userAgent): array;
}
