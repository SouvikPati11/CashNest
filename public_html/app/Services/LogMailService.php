<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MailServiceInterface;
use Core\Contracts\LoggerInterface;

/**
 * Default mail driver that logs instead of sending.
 *
 * Safe for the foundation stage and local development: no external SMTP call is
 * made. Swap the container binding for an SMTP driver in production once email
 * features exist.
 */
final class LogMailService implements MailServiceInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function send(string $toEmail, string $subject, string $htmlBody, array $options = []): bool
    {
        $this->logger->info('Mail (log driver) not actually sent.', [
            'to'      => $toEmail,
            'subject' => $subject,
            'bytes'   => strlen($htmlBody),
        ]);

        return true;
    }
}
