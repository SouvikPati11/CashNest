<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AnnouncementRepositoryInterface;
use App\Contracts\AnnouncementServiceInterface;
use App\Contracts\AppSettingRepositoryInterface;
use App\Contracts\AppVersionRepositoryInterface;
use App\Contracts\AppVersionServiceInterface;
use App\Contracts\BannerRepositoryInterface;
use App\Contracts\BannerServiceInterface;
use App\Contracts\CmsPageRepositoryInterface;
use App\Contracts\CmsServiceInterface;
use App\Contracts\FaqRepositoryInterface;
use App\Contracts\HomeLayoutServiceInterface;
use App\Contracts\HomeSectionRepositoryInterface;
use App\Contracts\MaintenanceRepositoryInterface;
use App\Contracts\MaintenanceServiceInterface;
use App\Contracts\RemoteConfigRepositoryInterface;
use App\Contracts\RemoteConfigServiceInterface;
use App\Contracts\SettingsServiceInterface;
use App\Contracts\ThemeRepositoryInterface;
use App\Contracts\ThemeServiceInterface;
use App\Contracts\UserSettingsRepositoryInterface;
use App\Repositories\AnnouncementRepository;
use App\Repositories\AppSettingRepository;
use App\Repositories\AppVersionRepository;
use App\Repositories\BannerRepository;
use App\Repositories\CmsPageRepository;
use App\Repositories\FaqRepository;
use App\Repositories\HomeSectionRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\RemoteConfigRepository;
use App\Repositories\ThemeRepository;
use App\Repositories\UserSettingsRepository;
use App\Services\AnnouncementService;
use App\Services\AppVersionService;
use App\Services\BannerService;
use App\Services\CmsService;
use App\Services\HomeLayoutService;
use App\Services\MaintenanceService;
use App\Services\RemoteConfigService;
use App\Services\SettingsService;
use App\Services\ThemeService;
use Core\Config;
use Core\Contracts\ContainerInterface;
use Core\Database\Database;

/**
 * Settings Platform module service provider.
 *
 * Binds every server-driven config concern — settings, remote config, theme,
 * banners, announcements, app version, maintenance, CMS/FAQ, and home layout —
 * to its repository and service. Everything is read-optimised and admin-managed
 * later; no admin HTTP surface is bound here. Does not modify the kernel.
 */
final class SettingsPlatformServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        self::bindRepositories($container);
        self::bindServices($container);
    }

    private static function bindRepositories(ContainerInterface $container): void
    {
        $container->singleton(
            UserSettingsRepositoryInterface::class,
            static fn(ContainerInterface $c): UserSettingsRepository =>
                new UserSettingsRepository($c->get(Database::class))
        );
        $container->singleton(
            AppSettingRepositoryInterface::class,
            static fn(ContainerInterface $c): AppSettingRepository => new AppSettingRepository($c->get(Database::class))
        );
        $container->singleton(
            RemoteConfigRepositoryInterface::class,
            static fn(ContainerInterface $c): RemoteConfigRepository =>
                new RemoteConfigRepository($c->get(Database::class))
        );
        $container->singleton(
            ThemeRepositoryInterface::class,
            static fn(ContainerInterface $c): ThemeRepository => new ThemeRepository($c->get(Database::class))
        );
        $container->singleton(
            BannerRepositoryInterface::class,
            static fn(ContainerInterface $c): BannerRepository => new BannerRepository($c->get(Database::class))
        );
        $container->singleton(
            AnnouncementRepositoryInterface::class,
            static fn(ContainerInterface $c): AnnouncementRepository =>
                new AnnouncementRepository($c->get(Database::class))
        );
        $container->singleton(
            AppVersionRepositoryInterface::class,
            static fn(ContainerInterface $c): AppVersionRepository => new AppVersionRepository($c->get(Database::class))
        );
        $container->singleton(
            MaintenanceRepositoryInterface::class,
            static fn(ContainerInterface $c): MaintenanceRepository =>
                new MaintenanceRepository($c->get(Database::class))
        );
        $container->singleton(
            CmsPageRepositoryInterface::class,
            static fn(ContainerInterface $c): CmsPageRepository => new CmsPageRepository($c->get(Database::class))
        );
        $container->singleton(
            HomeSectionRepositoryInterface::class,
            static fn(ContainerInterface $c): HomeSectionRepository =>
                new HomeSectionRepository($c->get(Database::class))
        );
        $container->singleton(
            FaqRepositoryInterface::class,
            static fn(ContainerInterface $c): FaqRepository => new FaqRepository($c->get(Database::class))
        );
    }

    private static function bindServices(ContainerInterface $container): void
    {
        $container->singleton(
            AppVersionServiceInterface::class,
            static fn(ContainerInterface $c): AppVersionService =>
                new AppVersionService($c->get(AppVersionRepositoryInterface::class))
        );
        $container->singleton(
            MaintenanceServiceInterface::class,
            static fn(ContainerInterface $c): MaintenanceService =>
                new MaintenanceService($c->get(MaintenanceRepositoryInterface::class))
        );
        $container->singleton(
            SettingsServiceInterface::class,
            static fn(ContainerInterface $c): SettingsService => new SettingsService(
                $c->get(UserSettingsRepositoryInterface::class),
                $c->get(AppSettingRepositoryInterface::class),
                $c->get(AppVersionServiceInterface::class),
                $c->get(MaintenanceServiceInterface::class)
            )
        );
        $container->singleton(
            RemoteConfigServiceInterface::class,
            static fn(ContainerInterface $c): RemoteConfigService => new RemoteConfigService(
                $c->get(RemoteConfigRepositoryInterface::class),
                $c->get(AppSettingRepositoryInterface::class),
                $c->get(Config::class)
            )
        );
        $container->singleton(
            ThemeServiceInterface::class,
            static fn(ContainerInterface $c): ThemeService =>
                new ThemeService($c->get(ThemeRepositoryInterface::class))
        );
        $container->singleton(
            BannerServiceInterface::class,
            static fn(ContainerInterface $c): BannerService =>
                new BannerService($c->get(BannerRepositoryInterface::class))
        );
        $container->singleton(
            AnnouncementServiceInterface::class,
            static fn(ContainerInterface $c): AnnouncementService =>
                new AnnouncementService($c->get(AnnouncementRepositoryInterface::class))
        );
        $container->singleton(
            CmsServiceInterface::class,
            static fn(ContainerInterface $c): CmsService => new CmsService(
                $c->get(CmsPageRepositoryInterface::class),
                $c->get(FaqRepositoryInterface::class)
            )
        );
        $container->singleton(
            HomeLayoutServiceInterface::class,
            static fn(ContainerInterface $c): HomeLayoutService =>
                new HomeLayoutService($c->get(HomeSectionRepositoryInterface::class))
        );
    }
}
