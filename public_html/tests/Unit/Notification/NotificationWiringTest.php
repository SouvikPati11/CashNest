<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Contracts\FirebaseDispatcherInterface;
use App\Contracts\NotificationCampaignServiceInterface;
use App\Contracts\NotificationPreferenceServiceInterface;
use App\Contracts\NotificationServiceInterface;
use App\Controllers\Notification\NotificationController;
use App\Controllers\Notification\NotificationPreferenceController;
use App\Jobs\JobInterface;
use App\Jobs\SendPushNotificationJob;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\NotificationServiceProvider;
use App\Services\NotificationCampaignService;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationService;
use Core\Container;
use Core\Contracts\LoggerInterface;
use Core\Contracts\QueueInterface;
use Core\Database\Database;
use Core\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeQueue;
use Tests\Support\NullLogger;

final class NotificationWiringTest extends TestCase
{
    public function testNotificationRoutesAreGuarded(): void
    {
        $router = new Router(new Container());
        (require __DIR__ . '/../../../routes/notification.php')($router);

        $paths = [];
        foreach ($router->routes() as $route) {
            self::assertContains(JwtAuthMiddleware::class, $route->getMiddleware());
            $paths[] = $route->method() . ' ' . $route->path();
        }

        self::assertCount(6, $router->routes());
        self::assertContains('GET /v1/notifications/unread-count', $paths);
        self::assertContains('GET /v1/notifications/preferences', $paths);
        self::assertContains('PUT /v1/notifications/preferences', $paths);
        self::assertContains('POST /v1/notifications/read-all', $paths);
        self::assertContains('POST /v1/notifications/{id}/read', $paths);
    }

    public function testProviderWiresServicesControllersAndJob(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(QueueInterface::class, new FakeQueue());

        NotificationServiceProvider::register($container);

        self::assertInstanceOf(NotificationService::class, $container->get(NotificationServiceInterface::class));
        self::assertInstanceOf(
            NotificationPreferenceService::class,
            $container->get(NotificationPreferenceServiceInterface::class)
        );
        self::assertInstanceOf(
            NotificationCampaignService::class,
            $container->get(NotificationCampaignServiceInterface::class)
        );
        self::assertInstanceOf(FirebaseDispatcherInterface::class, $container->get(FirebaseDispatcherInterface::class));

        self::assertInstanceOf(NotificationController::class, $container->get(NotificationController::class));
        self::assertInstanceOf(
            NotificationPreferenceController::class,
            $container->get(NotificationPreferenceController::class)
        );

        $job = $container->get(SendPushNotificationJob::class);
        self::assertInstanceOf(JobInterface::class, $job);
    }
}
