<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Email verification service contract.
 *
 * Issues single-use, expiring verification credentials (a link token and an OTP),
 * validates them, and marks the account verified. Tokens are stored in the cache
 * layer (no dedicated table exists in the finalised schema), which is
 * shared-hosting friendly and honours the fixed database design.
 */
interface EmailVerificationServiceInterface
{
    /**
     * Generate and deliver verification credentials for a user (no-op if the
     * user has no email or id).
     */
    public function sendVerification(User $user): void;

    /**
     * Validate a link token; returns the user id on success (consuming it) or null.
     */
    public function verifyToken(string $token): ?int;

    /**
     * Validate an email + OTP; returns the user id on success (consuming it) or null.
     */
    public function verifyOtp(string $email, string $otp): ?int;

    /**
     * Mark a user's email as verified.
     */
    public function markVerified(int $userId): void;
}
