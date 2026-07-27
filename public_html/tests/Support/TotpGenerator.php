<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Test-only TOTP code generator (mirrors RFC 6238) used to exercise the
 * production TwoFactorAuthenticator verifier.
 */
final class TotpGenerator
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function now(string $secretBase32): string
    {
        $key     = self::base32Decode($secretBase32);
        $counter = intdiv(time(), 30);

        $bin  = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part   = substr($hash, $offset, 4);

        $value = (
            ((ord($part[0]) & 0x7F) << 24)
            | ((ord($part[1]) & 0xFF) << 16)
            | ((ord($part[2]) & 0xFF) << 8)
            | (ord($part[3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(rtrim($secret, '='));
        $buffer = 0;
        $bits   = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $index  = strpos(self::ALPHABET, $char);
            $buffer = ($buffer << 5) | (int) $index;
            $bits  += 5;

            if ($bits >= 8) {
                $bits  -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
