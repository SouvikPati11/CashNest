<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Google ID-token verifier contract.
 *
 * Validates a Google-issued OIDC ID token server-side (signature, audience,
 * issuer, expiry) and returns the trusted claims. Implementations must never
 * trust client-supplied identity — only values proven by verification.
 */
interface GoogleTokenVerifierInterface
{
    /**
     * Verify a Google ID token and return its trusted claims.
     *
     * @return array{sub: string, email: ?string, name: ?string, picture: ?string, email_verified: bool}
     *
     * @throws \App\Exceptions\UnauthorizedException When the token is invalid.
     */
    public function verify(string $idToken): array;
}
