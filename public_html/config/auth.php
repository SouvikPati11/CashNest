<?php

declare(strict_types=1);

use Core\Env;

/**
 * Authentication configuration.
 */
return [
    /*
     * When true, email/password login is blocked until the account's email is
     * verified (OTP/link). Requires a working mailer (MAIL_DRIVER=smtp, ...).
     *
     * Default false so registration -> login -> profile works out of the box on
     * shared hosting where email delivery may not be configured. Set to true in
     * production once SMTP is configured.
     */
    'require_email_verification' => (bool) Env::get('AUTH_REQUIRE_EMAIL_VERIFICATION', false),
];
