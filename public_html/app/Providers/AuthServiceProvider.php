<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AuthenticationRepositoryInterface;
use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Repositories\AuthenticationRepository;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\UserService;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Authentication module service provider.
 *
 * Binds the Authentication module's interfaces to their concrete implementations
 * in the container (Dependency Inversion). Kept as a self-contained, testable
 * registration unit that does not touch the foundation kernel. The HTTP layer
 * (built in a later milestone) invokes register() during boot; until then it is
 * exercised directly by tests.
 *
 * Relies on the foundation already having bound Database and LoggerInterface.
 */
final class AuthServiceProvider
{
    /**
     * Register the module's bindings on the given container.
     */
    public static function register(ContainerInterface $container): void
    {
        // Repositories.
        $container->singleton(
            UserRepositoryInterface::class,
            static fn(ContainerInterface $c): UserRepository => new UserRepository($c->get(Database::class))
        );

        $container->singleton(
            AuthenticationRepositoryInterface::class,
            static fn(ContainerInterface $c): AuthenticationRepository =>
                new AuthenticationRepository($c->get(Database::class))
        );

        // Services.
        $container->singleton(
            UserServiceInterface::class,
            static fn(ContainerInterface $c): UserService => new UserService(
                $c->get(UserRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            AuthenticationServiceInterface::class,
            static fn(ContainerInterface $c): AuthenticationService => new AuthenticationService(
                $c->get(AuthenticationRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
