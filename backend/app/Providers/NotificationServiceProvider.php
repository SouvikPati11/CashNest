<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\DeviceTokenRepositoryInterface;
use App\Contracts\FirebaseDispatcherInterface;
use App\Contracts\NotificationCampaignRepositoryInterface;
use App\Contracts\NotificationCampaignServiceInterface;
use App\Contracts\NotificationPreferenceRepositoryInterface;
use App\Contracts\NotificationPreferenceServiceInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\NotificationServiceInterface;
use App\Repositories\DeviceTokenRepository;
use App\Repositories\NotificationCampaignRepository;
use App\Repositories\NotificationPreferenceRepository;
use App\Repositories\NotificationRepository;
use App\Services\Notification\NotificationTemplates;
use App\Services\NotificationCampaignService;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationService;
use App\Services\Push\NullFirebaseDispatcher;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\QueueInterface;
use Core\Database\Database;

/**
 * Notification module service provider.
 *
 * Binds notification/campaign/preference repositories and services, the template
 * registry, the device-token reader, and the Firebase dispatcher placeholder.
 * Pushes are enqueued via the foundation QueueInterface and delivered by
 * SendPushNotificationJob; swap the FirebaseDispatcherInterface binding for the
 * real FCM client without touching the pipeline. Does not modify the kernel.
 */
final class NotificationServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        $container->singleton(
            NotificationRepositoryInterface::class,
            static fn(ContainerInterface $c): NotificationRepository =>
                new NotificationRepository($c->get(Database::class))
        );

        $container->singleton(
            NotificationCampaignRepositoryInterface::class,
            static fn(ContainerInterface $c): NotificationCampaignRepository =>
                new NotificationCampaignRepository($c->get(Database::class))
        );

        $container->singleton(
            NotificationPreferenceRepositoryInterface::class,
            static fn(ContainerInterface $c): NotificationPreferenceRepository =>
                new NotificationPreferenceRepository($c->get(Database::class))
        );

        $container->singleton(
            DeviceTokenRepositoryInterface::class,
            static fn(ContainerInterface $c): DeviceTokenRepository =>
                new DeviceTokenRepository($c->get(Database::class))
        );

        $container->singleton(
            NotificationTemplates::class,
            static fn(ContainerInterface $c): NotificationTemplates => new NotificationTemplates()
        );

        $container->singleton(
            FirebaseDispatcherInterface::class,
            static fn(ContainerInterface $c): NullFirebaseDispatcher =>
                new NullFirebaseDispatcher($c->get(LoggerInterface::class))
        );

        $container->singleton(
            NotificationServiceInterface::class,
            static fn(ContainerInterface $c): NotificationService => new NotificationService(
                $c->get(NotificationRepositoryInterface::class),
                $c->get(QueueInterface::class),
                $c->get(NotificationTemplates::class),
                $c->get(LoggerInterface::class)
            )
        );

        $container->singleton(
            NotificationPreferenceServiceInterface::class,
            static fn(ContainerInterface $c): NotificationPreferenceService => new NotificationPreferenceService(
                $c->get(NotificationPreferenceRepositoryInterface::class)
            )
        );

        $container->singleton(
            NotificationCampaignServiceInterface::class,
            static fn(ContainerInterface $c): NotificationCampaignService => new NotificationCampaignService(
                $c->get(NotificationCampaignRepositoryInterface::class),
                $c->get(NotificationServiceInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
    }
}
