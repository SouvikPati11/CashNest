<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Models\Notification;
use App\Services\Notification\NotificationTemplates;
use PHPUnit\Framework\TestCase;

final class NotificationTemplatesTest extends TestCase
{
    private NotificationTemplates $templates;

    protected function setUp(): void
    {
        $this->templates = new NotificationTemplates();
    }

    public function testRendersPlaceholders(): void
    {
        $result = $this->templates->render('withdrawal_paid', ['amount' => '5.0000']);

        self::assertSame('Withdrawal paid', $result['title']);
        self::assertStringContainsString('5.0000', $result['body']);
        self::assertSame(Notification::TYPE_TRANSACTIONAL, $result['type']);
        self::assertSame('/wallet/withdrawals', $result['deep_link']);
    }

    public function testLeavesUnknownPlaceholderIntact(): void
    {
        $result = $this->templates->render('welcome', []);

        self::assertStringContainsString('{name}', $result['body']);
    }

    public function testHasReportsKnownKeys(): void
    {
        self::assertTrue($this->templates->has('reward_earned'));
        self::assertFalse($this->templates->has('does_not_exist'));
    }

    public function testUnknownTemplateThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->templates->render('does_not_exist');
    }
}
