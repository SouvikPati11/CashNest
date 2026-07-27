<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Security helper.
 *
 * Stateless cryptographic and sanitisation utilities used across the app:
 * password hashing, constant-time comparisons, token/UUID generation, HMAC
 * signing (for postbacks), and basic string sanitisation. No business logic.
 */
final class Security
{
    /**
     * Hash a password using the platform's strongest available algorithm.
     */
    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash (constant time internally).
     */
    public static function verifyPassword(string $plain, string $hash): bool
    {
        return $hash !== '' && password_verify($plain, $hash);
    }

    /**
     * Whether a stored hash should be re-hashed (algorithm/cost upgrade).
     */
    public static function passwordNeedsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Constant-time string comparison.
     */
    public static function hashEquals(string $known, string $given): bool
    {
        return hash_equals($known, $given);
    }

    /**
     * SHA-256 hex digest (e.g. hashing refresh tokens before storage).
     */
    public static function sha256(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Compute an HMAC-SHA256 signature (postback verification).
     */
    public static function hmac(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify an HMAC signature in constant time.
     */
    public static function verifyHmac(string $payload, string $secret, string $signature): bool
    {
        return hash_equals(self::hmac($payload, $secret), $signature);
    }

    /**
     * Cryptographically strong random token (hex).
     */
    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes(max(16, $bytes)));
    }

    /**
     * Generate an RFC-4122 v4 UUID.
     */
    public static function uuid4(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate a numeric OTP of the given length.
     */
    public static function numericOtp(int $length = 6): string
    {
        $otp = '';

        for ($i = 0; $i < $length; $i++) {
            $otp .= (string) random_int(0, 9);
        }

        return $otp;
    }

    /**
     * Trim and strip control characters from a scalar string.
     */
    public static function cleanString(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return trim($value);
    }

    /**
     * Whether a redirect/deep-link target uses an allowlisted scheme.
     *
     * @param array<int, string> $allowedSchemes
     */
    public static function isAllowedUrl(string $url, array $allowedSchemes = ['https', 'cashnest']): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), $allowedSchemes, true);
    }
}
