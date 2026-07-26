<?php

declare(strict_types=1);

namespace App\Admin\Security;

use App\Admin\Support\SessionInterface;
use App\Helpers\Security;

/**
 * CSRF protection for admin form submissions.
 *
 * A per-session token is embedded in every form and verified on each mutating
 * request using a constant-time comparison. The token is rotated lazily and can
 * be regenerated after privilege changes (login).
 */
final class CsrfGuard
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private SessionInterface $session)
    {
    }

    /**
     * The current token, generating and storing one on first use.
     */
    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = Security::randomToken(32);
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Validate a submitted token against the session token (constant time).
     */
    public function validate(?string $submitted): bool
    {
        $stored = $this->session->get(self::SESSION_KEY);

        if (!is_string($stored) || $stored === '' || !is_string($submitted) || $submitted === '') {
            return false;
        }

        return Security::hashEquals($stored, $submitted);
    }

    /**
     * Rotate the token (call after login).
     */
    public function rotate(): void
    {
        $this->session->set(self::SESSION_KEY, Security::randomToken(32));
    }
}
