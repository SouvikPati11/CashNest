<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GoogleTokenVerifierInterface;
use App\Contracts\HttpFetcherInterface;
use App\Exceptions\UnauthorizedException;
use Core\Contracts\LoggerInterface;

/**
 * Google ID-token verifier.
 *
 * Verifies the token server-side via Google's tokeninfo endpoint (Google performs
 * the signature/expiry checks), then enforces the audience and issuer locally.
 * Only claims proven by Google are trusted and returned.
 */
final class GoogleTokenVerifier implements GoogleTokenVerifierInterface
{
    private const VALID_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    public function __construct(
        private HttpFetcherInterface $http,
        private string $clientId,
        private string $tokenInfoUrl,
        private LoggerInterface $logger
    ) {
    }

    public function verify(string $idToken): array
    {
        if ($idToken === '') {
            throw $this->invalid('Empty Google token.');
        }

        if ($this->clientId === '') {
            // Misconfiguration: fail closed rather than accept any audience.
            $this->logger->error('Google client id is not configured.');
            throw $this->invalid('Google login is not available.');
        }

        $response = $this->http->get($this->tokenInfoUrl . '?id_token=' . rawurlencode($idToken));

        if ($response['status'] !== 200) {
            throw $this->invalid('Google token verification failed.');
        }

        $data = json_decode($response['body'], true);

        if (!is_array($data)) {
            throw $this->invalid('Malformed Google token response.');
        }

        $audience = isset($data['aud']) && is_string($data['aud']) ? $data['aud'] : '';
        if (!hash_equals($this->clientId, $audience)) {
            throw $this->invalid('Google token audience mismatch.');
        }

        $issuer = isset($data['iss']) && is_string($data['iss']) ? $data['iss'] : '';
        if (!in_array($issuer, self::VALID_ISSUERS, true)) {
            throw $this->invalid('Google token issuer mismatch.');
        }

        $sub = isset($data['sub']) && is_string($data['sub']) ? $data['sub'] : '';
        if ($sub === '') {
            throw $this->invalid('Google token is missing a subject.');
        }

        return [
            'sub'            => $sub,
            'email'          => isset($data['email']) && is_string($data['email']) ? strtolower($data['email']) : null,
            'name'           => isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            'picture'        => isset($data['picture']) && is_string($data['picture']) ? $data['picture'] : null,
            'email_verified' => $this->truthy($data['email_verified'] ?? false),
        ];
    }

    /**
     * Google returns email_verified as the string "true"/"false" or a bool.
     */
    private function truthy(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    private function invalid(string $logReason): UnauthorizedException
    {
        $this->logger->info('Google verification rejected.', ['reason' => $logReason]);

        return new UnauthorizedException('Google authentication failed.', 'INVALID_CREDENTIALS');
    }
}
