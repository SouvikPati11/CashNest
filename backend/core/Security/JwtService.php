<?php

declare(strict_types=1);

namespace Core\Security;

use Core\Exceptions\TokenException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

/**
 * JWT issuing/verification service.
 *
 * Thin wrapper over firebase/php-jwt that centralises signing config (secret,
 * algorithm, issuer, audience) and normalises failures into a TokenException the
 * HTTP layer can translate into standard error codes. No authentication logic
 * lives here — that arrives with the Auth module; this is pure token plumbing.
 */
final class JwtService
{
    /**
     * @param string $secret   Signing secret (HS256).
     * @param string $algo     Signing algorithm.
     * @param string $issuer   Expected `iss` claim.
     * @param string $audience Expected `aud` claim.
     */
    public function __construct(
        private string $secret,
        private string $algo = 'HS256',
        private string $issuer = 'cashnest',
        private string $audience = 'cashnest-app'
    ) {
    }

    /**
     * Issue a signed token.
     *
     * @param string               $subject Subject (typically the user uuid).
     * @param array<string, mixed> $claims  Additional custom claims.
     * @param int                  $ttl     Lifetime in seconds.
     */
    public function issue(string $subject, array $claims = [], int $ttl = 1800): string
    {
        $now = time();

        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $subject,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(16)),
        ]);

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Verify and decode a token into an associative array of claims.
     *
     * @return array<string, mixed>
     * @throws TokenException When the token is expired or otherwise invalid.
     */
    public function verify(string $token): array
    {
        if ($this->secret === '') {
            throw new TokenException('JWT secret is not configured.');
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));

            /** @var array<string, mixed> $claims */
            $claims = (array) $decoded;

            if (($claims['iss'] ?? null) !== $this->issuer) {
                throw new TokenException('Token issuer mismatch.');
            }

            if (($claims['aud'] ?? null) !== $this->audience) {
                throw new TokenException('Token audience mismatch.');
            }

            return $claims;
        } catch (ExpiredException $e) {
            throw new TokenException('Token has expired.', TokenException::REASON_EXPIRED, $e);
        } catch (SignatureInvalidException $e) {
            throw new TokenException('Token signature is invalid.', TokenException::REASON_INVALID, $e);
        } catch (TokenException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new TokenException('Token is invalid.', TokenException::REASON_INVALID, $e);
        }
    }
}
