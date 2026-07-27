<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\AuthTokenServiceInterface;
use App\Contracts\GoogleLoginServiceInterface;
use App\Contracts\GoogleTokenVerifierInterface;
use App\Contracts\HttpFetcherInterface;
use App\Contracts\SessionRepositoryInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Repositories\SessionRepository;
use App\Services\AuthTokenService;
use App\Services\CurlHttpFetcher;
use App\Services\GoogleLoginService;
use App\Services\GoogleTokenVerifier;
use Core\Config;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;
use Core\Security\JwtService;

/**
 * Authentication token / Google / session service provider (Part 3).
 *
 * Binds JWT/session token services, Google verification, and Google login to
 * their implementations. Depends on the Part 1 & Part 2 provider bindings and
 * foundation bindings (Database, JwtService, Config, Logger); does not modify the
 * kernel. The HTTP bootstrap registers all providers during boot.
 */
final class AuthTokenServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            HttpFetcherInterface::class,
            static fn(ContainerInterface $c): CurlHttpFetcher => new CurlHttpFetcher()
        );

        $container->singleton(
            GoogleTokenVerifierInterface::class,
            static fn(ContainerInterface $c): GoogleTokenVerifier => new GoogleTokenVerifier(
                $c->get(HttpFetcherInterface::class),
                (string) $c->get(Config::class)->get('google.client_id', ''),
                (string) $c->get(Config::class)->get('google.tokeninfo_url', 'https://oauth2.googleapis.com/tokeninfo'),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            SessionRepositoryInterface::class,
            static fn(ContainerInterface $c): SessionRepository => new SessionRepository($c->get(Database::class))
        );

        $container->singleton(
            AuthTokenServiceInterface::class,
            static fn(ContainerInterface $c): AuthTokenService => new AuthTokenService(
                $c->get(JwtService::class),
                $c->get(SessionRepositoryInterface::class),
                $c->get(UserServiceInterface::class),
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            GoogleLoginServiceInterface::class,
            static fn(ContainerInterface $c): GoogleLoginService => new GoogleLoginService(
                $c->get(GoogleTokenVerifierInterface::class),
                $c->get(UserServiceInterface::class),
                $c->get(AuthenticationServiceInterface::class),
                $c->get(AuthTokenServiceInterface::class),
                $c->get(TransactionRunnerInterface::class),
                $c->get(UserRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
