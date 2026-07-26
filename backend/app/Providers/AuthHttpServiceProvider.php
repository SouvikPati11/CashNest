<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\EmailVerificationServiceInterface;
use App\Contracts\LoginServiceInterface;
use App\Contracts\MailServiceInterface;
use App\Contracts\RegistrationServiceInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Services\DatabaseTransactionRunner;
use App\Services\EmailVerificationService;
use App\Services\LoginService;
use App\Services\RegistrationService;
use Core\Config;
use Core\Contracts\CacheInterface;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Authentication HTTP/flow service provider (Part 2).
 *
 * Binds the registration, login, verification, and transaction-runner
 * abstractions to their implementations. Depends on the Part 1 bindings
 * (register AuthServiceProvider first) and foundation bindings (Database, Cache,
 * Mail, Logger, Config). Kept self-contained so it does not modify the
 * foundation kernel; the HTTP bootstrap invokes both providers during boot.
 */
final class AuthHttpServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            TransactionRunnerInterface::class,
            static fn(ContainerInterface $c): DatabaseTransactionRunner =>
                new DatabaseTransactionRunner($c->get(Database::class))
        );

        $container->singleton(
            EmailVerificationServiceInterface::class,
            static fn(ContainerInterface $c): EmailVerificationService => new EmailVerificationService(
                $c->get(CacheInterface::class),
                $c->get(MailServiceInterface::class),
                $c->get(UserServiceInterface::class),
                $c->get(UserRepositoryInterface::class),
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            RegistrationServiceInterface::class,
            static fn(ContainerInterface $c): RegistrationService => new RegistrationService(
                $c->get(TransactionRunnerInterface::class),
                $c->get(UserServiceInterface::class),
                $c->get(AuthenticationServiceInterface::class),
                $c->get(UserRepositoryInterface::class),
                $c->get(EmailVerificationServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            LoginServiceInterface::class,
            static fn(ContainerInterface $c): LoginService => new LoginService(
                $c->get(AuthenticationServiceInterface::class),
                $c->get(UserServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
