<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OfferwallProvider;

/**
 * Postback signature & source verification.
 *
 * Signature scheme (canonical, documented for provider configuration):
 *   signature = HMAC-SHA256("<transaction_id>|<sub_id>|<payout>", provider_secret)
 * compared in constant time against the `signature` field in the payload.
 *
 * IP allowlisting: when a provider defines a non-empty allowlist, the source IP
 * must match an entry (exact, or IPv4 CIDR); an empty allowlist skips the IP check.
 */
final class PostbackSignatureVerifier
{
    /**
     * @param array<string, mixed> $payload
     */
    public function verifySignature(OfferwallProvider $provider, array $payload): bool
    {
        $secret = $provider->postbackSecret();

        if ($secret === '') {
            return false; // fail closed on misconfiguration
        }

        $provided = isset($payload['signature']) && is_string($payload['signature']) ? $payload['signature'] : '';

        if ($provided === '') {
            return false;
        }

        $canonical = implode('|', [
            $this->str($payload, 'transaction_id'),
            $this->str($payload, 'sub_id'),
            $this->str($payload, 'payout'),
        ]);

        $expected = hash_hmac('sha256', $canonical, $secret);

        return hash_equals($expected, $provided);
    }

    /**
     * Whether the source IP is permitted for the provider.
     */
    public function ipAllowed(OfferwallProvider $provider, string $ip): bool
    {
        $allowlist = $provider->ipAllowlist();

        if ($allowlist === []) {
            return true; // no restriction configured
        }

        foreach ($allowlist as $entry) {
            if ($this->matches($entry, $ip)) {
                return true;
            }
        }

        return false;
    }

    private function matches(string $entry, string $ip): bool
    {
        if ($entry === $ip) {
            return true;
        }

        if (!str_contains($entry, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $entry, 2);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false || !ctype_digit($bits)) {
            return false; // not IPv4 CIDR
        }

        $prefix = (int) $bits;
        if ($prefix < 0 || $prefix > 32) {
            return false;
        }

        if ($prefix === 0) {
            return true;
        }

        $mask = -1 << (32 - $prefix);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function str(array $payload, string $key): string
    {
        $value = $payload[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}
