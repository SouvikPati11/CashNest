<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Models\Notification;
use App\Services\NotificationPreferenceService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryNotificationPreferenceRepository;

final class NotificationPreferenceServiceTest extends TestCase
{
    private const USER = 1;

    private InMemoryNotificationPreferenceRepository $repo;

    private NotificationPreferenceService $service;

    protected function setUp(): void
    {
        $this->repo    = new InMemoryNotificationPreferenceRepository();
        $this->service = new NotificationPreferenceService($this->repo);
    }

    public function testDefaultsAllEnabledWhenNoRow(): void
    {
        $prefs = $this->service->get(self::USER);

        self::assertTrue($prefs['notif_push_enabled']);
        self::assertTrue($prefs['notif_transactional']);
        self::assertTrue($prefs['notif_promotional']);
    }

    public function testUpdateAppliesOnlySuppliedToggles(): void
    {
        $prefs = $this->service->update(self::USER, ['notif_promotional' => false]);

        self::assertTrue($prefs['notif_push_enabled']);
        self::assertFalse($prefs['notif_promotional']);
        // Persisted for subsequent reads.
        self::assertFalse($this->service->get(self::USER)['notif_promotional']);
    }

    public function testAllowsPushHonorsMasterToggle(): void
    {
        $this->service->update(self::USER, ['notif_push_enabled' => false]);

        self::assertFalse($this->service->allowsPush(self::USER, Notification::TYPE_TRANSACTIONAL));
        self::assertFalse($this->service->allowsPush(self::USER, Notification::TYPE_SYSTEM));
    }

    public function testAllowsPushHonorsPromotionalToggle(): void
    {
        $this->service->update(self::USER, ['notif_promotional' => false]);

        self::assertFalse($this->service->allowsPush(self::USER, Notification::TYPE_PROMOTIONAL));
        // Non-promotional categories still allowed.
        self::assertTrue($this->service->allowsPush(self::USER, Notification::TYPE_TRANSACTIONAL));
        self::assertTrue($this->service->allowsPush(self::USER, Notification::TYPE_SYSTEM));
    }

    public function testAllowsPushHonorsTransactionalToggle(): void
    {
        $this->service->update(self::USER, ['notif_transactional' => false]);

        self::assertFalse($this->service->allowsPush(self::USER, Notification::TYPE_TRANSACTIONAL));
        self::assertTrue($this->service->allowsPush(self::USER, Notification::TYPE_ENGAGEMENT));
    }
}
