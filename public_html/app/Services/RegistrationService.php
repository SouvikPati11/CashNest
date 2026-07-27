<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\EmailVerificationServiceInterface;
use App\Contracts\RegistrationServiceInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Models\AuthProvider;
use App\Models\User;
use Core\Contracts\LoggerInterface;

/**
 * Email registration orchestration.
 *
 * Ties together the Part 1 user and authentication services to perform a
 * complete email/password sign-up: unique-email check, optional referral
 * resolution, atomic creation of the user + email identity, and dispatch of
 * verification credentials. No tokens/sessions are issued here.
 */
final class RegistrationService implements RegistrationServiceInterface
{
    public function __construct(
        private TransactionRunnerInterface $transactions,
        private UserServiceInterface $users,
        private AuthenticationServiceInterface $auth,
        private UserRepositoryInterface $userRepository,
        private EmailVerificationServiceInterface $verification,
        private LoggerInterface $logger
    ) {
    }

    public function register(array $data): User
    {
        $email    = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $name     = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;

        if ($this->users->emailExists($email)) {
            throw new HttpException(409, 'EMAIL_EXISTS', 'This email is already registered.');
        }

        $referredBy = $this->resolveReferrer($data['referral_code'] ?? null);

        $registrationIp = isset($data['registration_ip']) && is_string($data['registration_ip'])
            ? $data['registration_ip']
            : null;

        // Atomic: user + email identity are created together or not at all.
        $create = function () use ($name, $email, $password, $referredBy, $registrationIp): User {
            $user = $this->users->createUser([
                'name'            => $name,
                'email'           => $email,
                'referred_by'     => $referredBy,
                'registration_ip' => $registrationIp,
            ]);

            $this->auth->createProvider([
                'user_id'       => $user->id(),
                'provider'      => AuthProvider::PROVIDER_EMAIL,
                'email'         => $email,
                'password_hash' => $this->auth->hashPassword($password),
                'is_primary'    => true,
            ]);

            return $user;
        };

        $user = $this->transactions->transaction($create);

        // Deliver verification outside the transaction (never send mail mid-commit).
        $this->verification->sendVerification($user);

        $this->logger->info('Email registration completed.', ['user_id' => $user->id()]);

        return $user;
    }

    /**
     * Validate and resolve an optional referral code to a referrer id.
     */
    private function resolveReferrer(mixed $referralCode): ?int
    {
        if (!is_string($referralCode) || $referralCode === '') {
            return null;
        }

        $referrer = $this->userRepository->findByReferralCode($referralCode);

        if ($referrer === null) {
            throw new ValidationException(['referral_code' => ['The referral code is invalid.']]);
        }

        return isset($referrer['id']) ? (int) $referrer['id'] : null;
    }
}
