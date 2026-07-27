<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\OfferRepositoryInterface;
use App\Contracts\OfferwallPostbackServiceInterface;
use App\Contracts\OfferwallProviderRepositoryInterface;
use App\Contracts\OfferwallServiceInterface;
use App\Contracts\PostbackRepositoryInterface;
use App\Contracts\ReferralCommissionServiceInterface;
use App\Repositories\OfferRepository;
use App\Repositories\OfferwallProviderRepository;
use App\Repositories\PostbackRepository;
use App\Services\OfferwallPostbackService;
use App\Services\OfferwallService;
use App\Services\PostbackSignatureVerifier;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;

/**
 * Offerwall & CPA module service provider.
 *
 * Binds provider/offer/postback repositories, the signature verifier, and the
 * offerwall + postback services. Postbacks credit via LedgerServiceInterface
 * (WalletServiceProvider) and fire ReferralCommissionServiceInterface
 * (ReferralServiceProvider). Does not modify the kernel.
 */
final class OfferwallServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            OfferwallProviderRepositoryInterface::class,
            static fn(ContainerInterface $c): OfferwallProviderRepository =>
                new OfferwallProviderRepository($c->get(Database::class))
        );

        $container->singleton(
            OfferRepositoryInterface::class,
            static fn(ContainerInterface $c): OfferRepository => new OfferRepository($c->get(Database::class))
        );

        $container->singleton(
            PostbackRepositoryInterface::class,
            static fn(ContainerInterface $c): PostbackRepository => new PostbackRepository($c->get(Database::class))
        );

        $container->singleton(
            PostbackSignatureVerifier::class,
            static fn(ContainerInterface $c): PostbackSignatureVerifier => new PostbackSignatureVerifier()
        );

        $container->singleton(
            OfferwallServiceInterface::class,
            static fn(ContainerInterface $c): OfferwallService => new OfferwallService(
                $c->get(OfferwallProviderRepositoryInterface::class),
                $c->get(OfferRepositoryInterface::class),
                $c->get(PostbackRepositoryInterface::class)
            )
        );

        $container->singleton(
            OfferwallPostbackServiceInterface::class,
            static fn(ContainerInterface $c): OfferwallPostbackService => new OfferwallPostbackService(
                $c->get(OfferwallProviderRepositoryInterface::class),
                $c->get(PostbackRepositoryInterface::class),
                $c->get(PostbackSignatureVerifier::class),
                $c->get(LedgerServiceInterface::class),
                $c->get(ReferralCommissionServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
