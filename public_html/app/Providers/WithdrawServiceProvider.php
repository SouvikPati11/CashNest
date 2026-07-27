<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\FraudFlagRepositoryInterface;
use App\Contracts\KycRepositoryInterface;
use App\Contracts\LedgerServiceInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Contracts\WalletTransactionRepositoryInterface;
use App\Contracts\WithdrawMethodRepositoryInterface;
use App\Contracts\WithdrawRequestRepositoryInterface;
use App\Contracts\WithdrawServiceInterface;
use App\Contracts\WithdrawSettlementServiceInterface;
use App\Repositories\FraudFlagRepository;
use App\Repositories\KycRepository;
use App\Repositories\WithdrawMethodRepository;
use App\Repositories\WithdrawRequestRepository;
use App\Services\WithdrawCalculator;
use App\Services\WithdrawService;
use App\Services\WithdrawSettlementService;
use Core\Config;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Withdraw module service provider.
 *
 * Binds withdraw repositories, the money calculator, and the user-facing +
 * settlement services. All coin movements flow through LedgerServiceInterface
 * (bound by WalletServiceProvider); currency settings are read via the wallet
 * module's CurrencyRepositoryInterface. Does not modify the kernel.
 */
final class WithdrawServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            WithdrawMethodRepositoryInterface::class,
            static fn(ContainerInterface $c): WithdrawMethodRepository =>
                new WithdrawMethodRepository($c->get(Database::class))
        );

        $container->singleton(
            WithdrawRequestRepositoryInterface::class,
            static fn(ContainerInterface $c): WithdrawRequestRepository =>
                new WithdrawRequestRepository($c->get(Database::class))
        );

        $container->singleton(
            KycRepositoryInterface::class,
            static fn(ContainerInterface $c): KycRepository => new KycRepository($c->get(Database::class))
        );

        $container->singleton(
            FraudFlagRepositoryInterface::class,
            static fn(ContainerInterface $c): FraudFlagRepository => new FraudFlagRepository($c->get(Database::class))
        );

        $container->singleton(
            WithdrawCalculator::class,
            static fn(ContainerInterface $c): WithdrawCalculator => new WithdrawCalculator()
        );

        $container->singleton(
            WithdrawServiceInterface::class,
            static fn(ContainerInterface $c): WithdrawService => new WithdrawService(
                $c->get(WithdrawMethodRepositoryInterface::class),
                $c->get(WithdrawRequestRepositoryInterface::class),
                $c->get(WalletRepositoryInterface::class),
                $c->get(WalletTransactionRepositoryInterface::class),
                $c->get(CurrencyRepositoryInterface::class),
                $c->get(KycRepositoryInterface::class),
                $c->get(FraudFlagRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                $c->get(WithdrawCalculator::class),
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            WithdrawSettlementServiceInterface::class,
            static fn(ContainerInterface $c): WithdrawSettlementService => new WithdrawSettlementService(
                $c->get(WithdrawRequestRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
