<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Contracts\UserServiceInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\JwtService;

/**
 * JWT authentication middleware.
 *
 * Guards protected routes: it extracts the Bearer access token, verifies it via
 * the foundation JwtService (expired/invalid tokens surface as TokenException,
 * which the global handler maps to TOKEN_EXPIRED / INVALID_TOKEN), loads the
 * user, enforces account status, and attaches the identity to the request for
 * downstream handlers.
 */
final class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwt,
        private UserServiceInterface $users
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new UnauthorizedException('Authentication is required.', 'AUTH_REQUIRED');
        }

        // Throws TokenException (expired/invalid) -> handled globally as 401.
        $claims = $this->jwt->verify($token);

        $uuid = isset($claims['sub']) && is_string($claims['sub']) ? $claims['sub'] : '';

        if ($uuid === '') {
            throw new UnauthorizedException('The access token is invalid.', 'INVALID_TOKEN');
        }

        $user = $this->users->findByUuid($uuid);

        if ($user === null) {
            throw new UnauthorizedException('The access token is invalid.', 'INVALID_TOKEN');
        }

        if ($user->isBlocked()) {
            throw new ForbiddenException('Your account is not active.', 'ACCOUNT_SUSPENDED');
        }

        $request->setAttribute('user', $user);
        $request->setAttribute('user_id', $user->id());
        $request->setAttribute('user_uuid', $uuid);

        return $next($request);
    }
}
