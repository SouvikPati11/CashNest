<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\ReferralCommissionServiceInterface;
use App\Contracts\ReferralRepositoryInterface;
use App\Contracts\ReferralServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Repositories\ReferralRepository;
use App\Services\ReferralCommissionService;
use App\Services\ReferralService;
use Core\Config;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Referral module service provider.
 *
 * Binds referral repository/services. Commission and bonus credits flow through
 * LedgerServiceInterface (bound by WalletServiceProvider). Does not modify the kernel.
 */
final class ReferralServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            ReferralRepositoryInterface::class,
            static fn(ContainerInterface $c): ReferralRepository => new ReferralRepository($c->get(Database::class))
        );

        $container->singleton(
            ReferralCommissionServiceInterface::class,
            static fn(ContainerInterface $c): ReferralCommissionService => new ReferralCommissionService(
                $c->get(ReferralRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            ReferralServiceInterface::class,
            static fn(ContainerInterface $c): ReferralService => new ReferralService(
                $c->get(ReferralRepositoryInterface::class),
                $c->get(UserServiceInterface::class),
                $c->get(UserRepositoryInterface::class),
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
