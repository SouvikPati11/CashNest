<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Offerwall/CPA postback processing contract.
 *
 * Verifies signature + source, enforces idempotency by (provider, transaction),
 * credits the user ONLY through the LedgerService, fires referral commission,
 * and audits every attempt to `postback_logs`.
 */
interface OfferwallPostbackServiceInterface
{
    public const RESULT_CREDITED        = 'credited';
    public const RESULT_DUPLICATE       = 'duplicate';
    public const RESULT_INVALID_SIG     = 'invalid_signature';
    public const RESULT_IP_BLOCKED      = 'ip_blocked';
    public const RESULT_USER_NOT_FOUND  = 'user_not_found';
    public const RESULT_ERROR           = 'error';

    /**
     * Process a postback.
     *
     * @param 'offerwall'|'cpa'    $kind
     * @param array<string, mixed> $payload
     * @return array{result: string, http_status: int, conversion_id: ?int}
     */
    public function process(string $kind, string $providerSlug, array $payload, string $ip, string $method): array;
}
