<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Mail service contract.
 *
 * Defines how the application sends transactional email (verification, password
 * reset, etc.). Only the interface and a safe log driver exist at the foundation
 * stage; an SMTP driver is wired when feature modules need it.
 */
interface MailServiceInterface
{
    /**
     * Send an email.
     *
     * @param string               $toEmail   Recipient address.
     * @param string               $subject   Subject line.
     * @param string               $htmlBody  HTML body.
     * @param array<string, mixed> $options   Optional extras (cc, reply-to, text body).
     * @return bool True when accepted for delivery.
     */
    public function send(string $toEmail, string $subject, string $htmlBody, array $options = []): bool;
}
