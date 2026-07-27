<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\MailServiceInterface;

/**
 * Mail service double that records messages instead of sending them.
 */
final class FakeMailService implements MailServiceInterface
{
    /** @var array<int, array{to: string, subject: string, body: string}> */
    public array $sent = [];

    public function send(string $toEmail, string $subject, string $htmlBody, array $options = []): bool
    {
        $this->sent[] = ['to' => $toEmail, 'subject' => $subject, 'body' => $htmlBody];

        return true;
    }
}
