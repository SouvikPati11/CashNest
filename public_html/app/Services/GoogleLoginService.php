<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\AuthTokenServiceInterface;
use App\Contracts\GoogleLoginServiceInterface;
use App\Contracts\GoogleTokenVerifierInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\AuthProvider;
use App\Models\User;
use Core\Contracts\LoggerInterface;

/**
 * Google login/registration orchestration.
 *
 * Verifies the Google ID token, then finds, links, or creates the account, and
 * issues app tokens. Google-verified emails are marked verified. New accounts are
 * created atomically (user + identity). No client-supplied identity is trusted —
 * only the verified claims.
 */
final class GoogleLoginService implements GoogleLoginServiceInterface
{
    public function __construct(
        private GoogleTokenVerifierInterface $verifier,
        private UserServiceInterface $users,
        private AuthenticationServiceInterface $auth,
        private AuthTokenServiceInterface $tokens,
        private TransactionRunnerInterface $transactions,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger
    ) {
    }

    public function login(string $idToken, ?string $referralCode, ?string $ip, ?string $userAgent): array
    {
        $claims = $this->verifier->verify($idToken);

        $sub     = $claims['sub'];
        $email   = $claims['email'];
        $name    = $claims['name'];
        $picture = $claims['picture'];

        [$user, $isNew] = $this->resolveUser($sub, $email, $name, $picture, $referralCode, $ip);

        if ($user->isBlocked()) {
            throw new ForbiddenException('Your account is not active.', 'ACCOUNT_SUSPENDED');
        }

        $userId = $user->id();
        if ($userId !== null) {
            $this->users->touchLastLogin($userId);
        }

        $tokens = $this->tokens->issueTokens($user, $ip, $userAgent);

        $this->logger->info('Google login succeeded.', ['user_id' => $userId, 'is_new' => $isNew]);

        return ['user' => $user, 'tokens' => $tokens, 'is_new' => $isNew];
    }

    /**
     * Find the Google identity, link to an existing email account, or create a
     * brand-new account.
     *
     * @return array{0: User, 1: bool}
     */
    private function resolveUser(
        string $sub,
        ?string $email,
        ?string $name,
        ?string $picture,
        ?string $referralCode,
        ?string $ip
    ): array {
        // 1) Existing Google identity -> log in.
        $provider = $this->auth->findProviderByUid(AuthProvider::PROVIDER_GOOGLE, $sub);

        if ($provider !== null) {
            $userId = $provider->userId();
            $user   = $userId !== null ? $this->users->findById($userId) : null;

            if ($user === null) {
                throw new UnauthorizedException('Google authentication failed.', 'INVALID_CREDENTIALS');
            }

            return [$user, false];
        }

        // 2) Existing account with the same email -> link Google to it.
        $existing = $email !== null ? $this->users->findByEmail($email) : null;

        if ($existing !== null) {
            $this->auth->createProvider([
                'user_id'      => $existing->id(),
                'provider'     => AuthProvider::PROVIDER_GOOGLE,
                'provider_uid' => $sub,
                'email'        => $email,
                'is_primary'   => false,
            ]);

            return [$existing, false];
        }

        // 3) Brand-new account (atomic user + identity).
        $referredBy = $this->resolveReferrer($referralCode);

        $create = function () use ($sub, $email, $name, $picture, $referredBy, $ip): User {
            $user = $this->users->createUser([
                'name'            => $name,
                'email'           => $email,
                'avatar_url'      => $picture,
                'referred_by'     => $referredBy,
                'registration_ip' => $ip,
            ]);

            $userId = $user->id();

            // Google verifies email ownership, so the account is verified.
            if ($userId !== null && $email !== null) {
                $this->userRepository->update($userId, ['email_verified_at' => gmdate('Y-m-d H:i:s')]);
            }

            $this->auth->createProvider([
                'user_id'      => $userId,
                'provider'     => AuthProvider::PROVIDER_GOOGLE,
                'provider_uid' => $sub,
                'email'        => $email,
                'is_primary'   => true,
            ]);

            return $user;
        };

        $user = $this->transactions->transaction($create);

        return [$user, true];
    }

    /**
     * Validate and resolve an optional referral code to a referrer id.
     */
    private function resolveReferrer(?string $referralCode): ?int
    {
        if ($referralCode === null || $referralCode === '') {
            return null;
        }

        $referrer = $this->userRepository->findByReferralCode($referralCode);

        if ($referrer === null) {
            throw new ValidationException(['referral_code' => ['The referral code is invalid.']]);
        }

        return isset($referrer['id']) ? (int) $referrer['id'] : null;
    }
}
