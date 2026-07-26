<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EmailVerificationServiceInterface;
use App\Contracts\MailServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Helpers\Security;
use App\Models\User;
use Core\Config;
use Core\Contracts\CacheInterface;
use Core\Contracts\LoggerInterface;

/**
 * Email verification service.
 *
 * Issues a single-use link token and a 6-digit OTP (both expiring in 15 minutes,
 * per API_SPECIFICATION.md §2.4), stores them in the cache layer, delivers them
 * by email, and validates them. OTP attempts are capped to defend against
 * brute force. On success the account's `email_verified_at` is set via the
 * existing user repository.
 */
final class EmailVerificationService implements EmailVerificationServiceInterface
{
    /** Token/OTP lifetime in seconds (15 minutes). */
    private const TTL = 900;

    /** Maximum OTP attempts before the code is invalidated. */
    private const MAX_OTP_ATTEMPTS = 5;

    private const TOKEN_PREFIX = 'emailverify:token:';
    private const OTP_PREFIX   = 'emailverify:otp:';

    public function __construct(
        private CacheInterface $cache,
        private MailServiceInterface $mail,
        private UserServiceInterface $users,
        private UserRepositoryInterface $userRepository,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function sendVerification(User $user): void
    {
        $email  = $user->email();
        $userId = $user->id();

        if ($email === null || $userId === null) {
            return;
        }

        $token = Security::randomToken(24);
        $otp   = Security::numericOtp(6);

        $this->cache->put(self::TOKEN_PREFIX . $token, $userId, self::TTL);
        $this->cache->put(self::OTP_PREFIX . $userId, ['otp' => $otp, 'attempts' => 0], self::TTL);

        $this->mail->send(
            $email,
            'Verify your CashNest email',
            $this->buildEmailBody($token, $otp)
        );

        $this->logger->info('Email verification issued.', ['user_id' => $userId]);
    }

    public function verifyToken(string $token): ?int
    {
        if ($token === '') {
            return null;
        }

        $key    = self::TOKEN_PREFIX . $token;
        $userId = $this->cache->get($key);

        if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
            return null;
        }

        $this->cache->forget($key); // single-use

        return (int) $userId;
    }

    public function verifyOtp(string $email, string $otp): ?int
    {
        $user   = $this->users->findByEmail($email);
        $userId = $user?->id();

        if ($userId === null) {
            return null;
        }

        $key    = self::OTP_PREFIX . $userId;
        $record = $this->cache->get($key);

        if (!is_array($record) || !isset($record['otp'])) {
            return null;
        }

        $attempts = (int) ($record['attempts'] ?? 0);

        if ($attempts >= self::MAX_OTP_ATTEMPTS) {
            $this->cache->forget($key);
            return null;
        }

        if (hash_equals((string) $record['otp'], $otp)) {
            $this->cache->forget($key); // single-use
            return $userId;
        }

        // Wrong OTP: record the attempt, invalidating the code once the cap is hit.
        $record['attempts'] = $attempts + 1;

        if ($record['attempts'] >= self::MAX_OTP_ATTEMPTS) {
            $this->cache->forget($key);
        } else {
            $this->cache->put($key, $record, self::TTL);
        }

        return null;
    }

    public function markVerified(int $userId): void
    {
        $this->userRepository->update($userId, ['email_verified_at' => gmdate('Y-m-d H:i:s')]);
        $this->logger->info('Email verified.', ['user_id' => $userId]);
    }

    /**
     * Build the (plain HTML) verification email body.
     */
    private function buildEmailBody(string $token, string $otp): string
    {
        $appUrl = rtrim((string) $this->config->get('app.url', 'http://localhost'), '/');
        $link   = $appUrl . '/verify-email?token=' . rawurlencode($token);

        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

        return '<p>Welcome to CashNest!</p>'
            . '<p>Verify your email using this code: <strong>' . $safeOtp . '</strong></p>'
            . '<p>Or tap the link: <a href="' . $safeLink . '">Verify my email</a></p>'
            . '<p>This code expires in 15 minutes.</p>';
    }
}
