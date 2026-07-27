<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CheckinRepositoryInterface;
use App\Contracts\CheckinServiceInterface;
use App\Contracts\LedgerServiceInterface;
use App\Contracts\RandomizerInterface;
use App\Contracts\ScratchRepositoryInterface;
use App\Contracts\ScratchServiceInterface;
use App\Contracts\SpinRepositoryInterface;
use App\Contracts\SpinServiceInterface;
use App\Contracts\TaskRepositoryInterface;
use App\Contracts\TaskServiceInterface;
use App\Repositories\CheckinRepository;
use App\Repositories\ScratchRepository;
use App\Repositories\SpinRepository;
use App\Repositories\TaskRepository;
use App\Services\CheckinService;
use App\Services\RandomGenerator;
use App\Services\ScratchService;
use App\Services\SpinService;
use App\Services\TaskService;
use App\Services\WeightedPicker;
use Core\Config;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Rewards module service provider (Daily Check-in, Scratch, Spin, Tasks).
 *
 * Binds reward repositories/services to implementations. Every reward service
 * depends on LedgerServiceInterface (bound by WalletServiceProvider) so all
 * coin credits flow through the ledger. Does not modify the kernel.
 */
final class RewardsServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            RandomizerInterface::class,
            static fn(ContainerInterface $c): RandomGenerator => new RandomGenerator()
        );

        // Repositories.
        $container->singleton(
            CheckinRepositoryInterface::class,
            static fn(ContainerInterface $c): CheckinRepository => new CheckinRepository($c->get(Database::class))
        );
        $container->singleton(
            ScratchRepositoryInterface::class,
            static fn(ContainerInterface $c): ScratchRepository => new ScratchRepository($c->get(Database::class))
        );
        $container->singleton(
            SpinRepositoryInterface::class,
            static fn(ContainerInterface $c): SpinRepository => new SpinRepository($c->get(Database::class))
        );
        $container->singleton(
            TaskRepositoryInterface::class,
            static fn(ContainerInterface $c): TaskRepository => new TaskRepository($c->get(Database::class))
        );

        // Services.
        $container->singleton(
            CheckinServiceInterface::class,
            static fn(ContainerInterface $c): CheckinService => new CheckinService(
                $c->get(CheckinRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
        $container->singleton(
            ScratchServiceInterface::class,
            static fn(ContainerInterface $c): ScratchService => new ScratchService(
                $c->get(ScratchRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                new WeightedPicker($c->get(RandomizerInterface::class)),
                $c->get(LoggerInterface::class)
            )
        );
        $container->singleton(
            SpinServiceInterface::class,
            static fn(ContainerInterface $c): SpinService => new SpinService(
                $c->get(SpinRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                new WeightedPicker($c->get(RandomizerInterface::class)),
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            )
        );
        $container->singleton(
            TaskServiceInterface::class,
            static fn(ContainerInterface $c): TaskService => new TaskService(
                $c->get(TaskRepositoryInterface::class),
                $c->get(LedgerServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
