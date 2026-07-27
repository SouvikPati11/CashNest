<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\LoginServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Models\User;
use Core\Contracts\LoggerInterface;

/**
 * Email login authentication.
 *
 * Verifies email/password credentials in constant time and enforces account
 * state (blocked / unverified). Returns the authenticated user; token/session
 * issuance is deliberately out of scope (the JWT module owns that).
 */
final class LoginService implements LoginServiceInterface
{
    public function __construct(
        private AuthenticationServiceInterface $auth,
        private UserServiceInterface $users,
        private LoggerInterface $logger
    ) {
    }

    public function login(string $email, string $password): User
    {
        $email = strtolower(trim($email));

        // A single uniform error prevents email enumeration (spec §2.3 security).
        $invalid = static fn(): UnauthorizedException =>
            new UnauthorizedException('Invalid email or password.', 'INVALID_CREDENTIALS');

        $provider = $this->auth->findEmailProvider($email);

        if ($provider === null) {
            throw $invalid();
        }

        $hash = $provider->passwordHash();

        if ($hash === null || !$this->auth->verifyPassword($password, $hash)) {
            throw $invalid();
        }

        $userId = $provider->userId();
        $user   = $userId !== null ? $this->users->findById($userId) : null;

        if ($user === null) {
            throw $invalid();
        }

        if ($user->isBlocked()) {
            throw new ForbiddenException('Your account is not active.', 'ACCOUNT_SUSPENDED');
        }

        if (!$user->hasVerifiedEmail()) {
            throw new ForbiddenException('Please verify your email before logging in.', 'EMAIL_NOT_VERIFIED');
        }

        $id = $user->id();

        if ($id !== null) {
            $this->users->touchLastLogin($id);
        }

        $this->logger->info('Email login succeeded.', ['user_id' => $id]);

        return $user;
    }
}
