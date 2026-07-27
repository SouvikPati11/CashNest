<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthTokenServiceInterface;
use App\Contracts\SessionRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\Security;
use App\Models\Session;
use App\Models\User;
use Core\Config;
use Core\Contracts\LoggerInterface;
use Core\Security\JwtService;

/**
 * Auth token & session service.
 *
 * Issues short-lived JWT access tokens (via the foundation JwtService) paired
 * with opaque, rotating refresh tokens. Only the SHA-256 hash of a refresh token
 * is persisted in `user_sessions`. Refresh performs rotation with reuse
 * detection (a revoked token being presented revokes the whole chain).
 */
final class AuthTokenService implements AuthTokenServiceInterface
{
    public function __construct(
        private JwtService $jwt,
        private SessionRepositoryInterface $sessions,
        private UserServiceInterface $users,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function issueTokens(User $user, ?string $ip, ?string $userAgent): array
    {
        $accessTtl  = (int) $this->config->get('jwt.access_ttl', 1800);
        $refreshTtl = (int) $this->config->get('jwt.refresh_ttl', 2592000);

        $accessToken  = $this->jwt->issue((string) $user->uuid(), ['scope' => 'user'], $accessTtl);
        $refreshToken = Security::randomToken(32);

        $this->sessions->create([
            'user_id'            => $user->id(),
            'refresh_token_hash' => Security::sha256($refreshToken),
            'ip_address'         => $ip,
            'user_agent'         => $userAgent !== null ? substr($userAgent, 0, 255) : null,
            'expires_at'         => gmdate('Y-m-d H:i:s', time() + $refreshTtl),
        ]);

        return [
            'access_token'  => $accessToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $accessTtl,
            'refresh_token' => $refreshToken,
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $row = $this->sessions->findByRefreshHash(Security::sha256($refreshToken));

        if ($row === null) {
            throw $this->invalidRefresh();
        }

        $session = Session::fromRow($row);

        // Reuse of an already-revoked token => probable theft: kill the chain.
        if ($session->isRevoked()) {
            $userId = $session->userId();
            if ($userId !== null) {
                $this->sessions->revokeAllForUser($userId, $this->now());
                $this->logger->warning('Refresh token reuse detected; sessions revoked.', ['user_id' => $userId]);
            }
            throw $this->invalidRefresh();
        }

        if ($session->isExpired(time())) {
            throw $this->invalidRefresh();
        }

        $userId = $session->userId();
        $user   = $userId !== null ? $this->users->findById($userId) : null;

        if ($user === null) {
            throw $this->invalidRefresh();
        }

        if ($user->isBlocked()) {
            throw new ForbiddenException('Your account is not active.', 'ACCOUNT_SUSPENDED');
        }

        // Rotate: revoke the presented session, issue a fresh pair.
        $sessionId = $session->id();
        if ($sessionId !== null) {
            $this->sessions->revoke($sessionId, $this->now());
        }

        return $this->issueTokens($user, $session->ipAddress(), $session->userAgent());
    }

    public function logout(string $refreshToken): void
    {
        $row = $this->sessions->findByRefreshHash(Security::sha256($refreshToken));

        if ($row === null) {
            return; // idempotent
        }

        $id = isset($row['id']) ? (int) $row['id'] : 0;

        if ($id > 0) {
            $this->sessions->revoke($id, $this->now());
        }
    }

    public function logoutAll(int $userId): void
    {
        $this->sessions->revokeAllForUser($userId, $this->now());
    }

    private function invalidRefresh(): UnauthorizedException
    {
        return new UnauthorizedException('The refresh token is invalid or has expired.', 'INVALID_REFRESH_TOKEN');
    }

    private function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
