<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\LedgerServiceInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Contracts\WalletServiceInterface;
use App\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\LedgerService;
use App\Services\WalletService;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Wallet & Ledger module service provider.
 *
 * Binds wallet/ledger repositories and services to their implementations.
 * Depends on the Part 2 TransactionRunnerInterface binding and foundation
 * bindings (Database, Logger); does not modify the kernel.
 */
final class WalletServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            WalletRepositoryInterface::class,
            static fn(ContainerInterface $c): WalletRepository => new WalletRepository($c->get(Database::class))
        );

        $container->singleton(
            WalletTransactionRepositoryInterface::class,
            static fn(ContainerInterface $c): WalletTransactionRepository =>
                new WalletTransactionRepository($c->get(Database::class))
        );

        $container->singleton(
            CurrencyRepositoryInterface::class,
            static fn(ContainerInterface $c): CurrencyRepository => new CurrencyRepository($c->get(Database::class))
        );

        $container->singleton(
            LedgerServiceInterface::class,
            static fn(ContainerInterface $c): LedgerService => new LedgerService(
                $c->get(TransactionRunnerInterface::class),
                $c->get(WalletRepositoryInterface::class),
                $c->get(WalletTransactionRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            WalletServiceInterface::class,
            static fn(ContainerInterface $c): WalletService => new WalletService(
                $c->get(WalletRepositoryInterface::class),
                $c->get(WalletTransactionRepositoryInterface::class),
                $c->get(CurrencyRepositoryInterface::class)
            )
        );
    }
}
