<?php

declare(strict_types=1);

namespace App\Admin\Security;

/**
 * TOTP (RFC 6238) authenticator — the 2FA-ready verifier for admin login.
 *
 * Admins with `two_fa_enabled` present a 6-digit code from an authenticator app;
 * this verifies it against the shared base32 secret with a small time-step
 * window to tolerate clock drift. When 2FA is disabled the login flow simply
 * skips verification — the panel is 2FA-ready without forcing it on.
 */
final class TwoFactorAuthenticator
{
    private const DIGITS    = 6;
    private const PERIOD    = 30;
    private const ALPHABET  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Verify a submitted code against a base32 secret, allowing +/- $window steps.
     */
    public function verify(string $secretBase32, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $key = $this->base32Decode($secretBase32);
        if ($key === '') {
            return false;
        }

        $counter = intdiv(time(), self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->hotp($key, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the HOTP value for a counter (used by TOTP).
     */
    private function hotp(string $key, int $counter): string
    {
        $binCounter = pack('N*', 0) . pack('N*', $counter);
        $hash       = hash_hmac('sha1', $binCounter, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part   = substr($hash, $offset, 4);

        $value = (
            ((ord($part[0]) & 0x7F) << 24)
            | ((ord($part[1]) & 0xFF) << 16)
            | ((ord($part[2]) & 0xFF) << 8)
            | (ord($part[3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $value, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decode an RFC 4648 base32 secret to raw bytes (empty string on bad input).
     */
    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(rtrim($secret, '='));
        if ($secret === '') {
            return '';
        }

        $buffer = 0;
        $bits   = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                return '';
            }

            $buffer = ($buffer << 5) | $index;
            $bits  += 5;

            if ($bits >= 8) {
                $bits  -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
