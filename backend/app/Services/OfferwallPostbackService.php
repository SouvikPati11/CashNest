<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\OfferwallPostbackServiceInterface;
use App\Contracts\OfferwallProviderRepositoryInterface;
use App\Contracts\PostbackRepositoryInterface;
use App\Contracts\ReferralCommissionServiceInterface;
use App\Models\OfferConversion;
use App\Models\OfferwallProvider;
use Core\Contracts\LoggerInterface;

/**
 * Offerwall / CPA postback processor.
 *
 * Security & integrity guarantees:
 *  - HMAC signature verified and source IP allowlisted before any processing.
 *  - Idempotent by (provider_id, transaction_id_ext): a duplicate is
 *    acknowledged (200) but never re-credits.
 *  - The user is credited ONLY through LedgerService, keyed by a deterministic
 *    reference id.
 *  - Referral commission fires from the resulting earning transaction.
 *  - Every attempt (valid, invalid, duplicate) is written to `postback_logs`.
 */
final class OfferwallPostbackService implements OfferwallPostbackServiceInterface
{
    public function __construct(
        private OfferwallProviderRepositoryInterface $providers,
        private PostbackRepositoryInterface $postbacks,
        private PostbackSignatureVerifier $verifier,
        private LedgerServiceInterface $ledger,
        private ReferralCommissionServiceInterface $referralCommission,
        private LoggerInterface $logger
    ) {
    }

    public function process(string $kind, string $providerSlug, array $payload, string $ip, string $method): array
    {
        $ctx = [
            'endpoint' => 'postback/' . $kind . '/' . $providerSlug,
            'method'   => $method,
            'raw'      => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            'ip'       => $ip,
        ];

        $providerRow = $this->providers->findBySlug($providerSlug);

        if ($providerRow === null) {
            $this->log($ctx, null, false, false, self::RESULT_ERROR, null, 'Unknown provider.');
            return $this->outcome(self::RESULT_ERROR, 404, null);
        }

        $provider   = OfferwallProvider::fromRow($providerRow);
        $providerId = (int) $provider->id();

        $signatureValid = $this->verifier->verifySignature($provider, $payload);
        $ipAllowed      = $this->verifier->ipAllowed($provider, $ip);

        if (!$signatureValid) {
            $this->log($ctx, $providerId, false, $ipAllowed, self::RESULT_INVALID_SIG, null, 'Signature check failed.');
            return $this->outcome(self::RESULT_INVALID_SIG, 401, null);
        }

        if (!$ipAllowed) {
            $this->log($ctx, $providerId, true, false, self::RESULT_IP_BLOCKED, null, 'Source IP not allowed.');
            return $this->outcome(self::RESULT_IP_BLOCKED, 403, null);
        }

        $txnExt = $this->str($payload, 'transaction_id');
        if ($txnExt === '') {
            $this->log($ctx, $providerId, true, true, self::RESULT_ERROR, null, 'Missing transaction_id.');
            return $this->outcome(self::RESULT_ERROR, 422, null);
        }

        // Idempotency: never process the same provider transaction twice.
        $existing = $this->postbacks->findConversion($providerId, $txnExt);
        if ($existing !== null) {
            $this->log($ctx, $providerId, true, true, self::RESULT_DUPLICATE, (int) $existing['id'], null);
            return $this->outcome(self::RESULT_DUPLICATE, 200, (int) $existing['id']);
        }

        $click = $this->postbacks->findClickByToken($this->str($payload, 'sub_id'));
        if ($click === null) {
            $this->log($ctx, $providerId, true, true, self::RESULT_USER_NOT_FOUND, null, 'Click token not found.');
            return $this->outcome(self::RESULT_USER_NOT_FOUND, 404, null);
        }

        $coins = (int) floor((float) $this->str($payload, 'payout') * $provider->currencyRatio());
        if ($coins <= 0) {
            $this->log($ctx, $providerId, true, true, self::RESULT_ERROR, null, 'Non-positive payout.');
            return $this->outcome(self::RESULT_ERROR, 422, null);
        }

        $userId = (int) $click['user_id'];

        $conversionId = (int) $this->postbacks->createConversion([
            'provider_id'        => $providerId,
            'user_id'            => $userId,
            'offer_id'           => $click['offer_id'] ?? null,
            'click_id'           => (int) $click['id'],
            'transaction_id_ext' => $txnExt,
            'payout_coins'       => $coins,
            'status'             => OfferConversion::STATUS_PENDING,
            'ip_address'         => $ip,
            'signature_valid'    => 1,
        ]);

        $type      = $kind === 'cpa' ? 'cpa' : 'offerwall';
        $reference = $type . ':' . $providerSlug . ':' . $txnExt;

        $txn = $this->ledger->credit($userId, $coins, $type, $kind, $reference, ['source_id' => $conversionId]);

        $this->postbacks->updateConversion($conversionId, [
            'status'                => OfferConversion::STATUS_CREDITED,
            'wallet_transaction_id' => $txn->id(),
        ]);

        // Referral commission flows from this earning (idempotent internally).
        $this->referralCommission->applyForEarning($userId, $txn);

        $this->log($ctx, $providerId, true, true, self::RESULT_CREDITED, $conversionId, null);
        $this->logger->info('Offerwall conversion credited.', ['user_id' => $userId, 'coins' => $coins]);

        return $this->outcome(self::RESULT_CREDITED, 200, $conversionId);
    }

    /**
     * @return array{result: string, http_status: int, conversion_id: ?int}
     */
    private function outcome(string $result, int $status, ?int $conversionId): array
    {
        return ['result' => $result, 'http_status' => $status, 'conversion_id' => $conversionId];
    }

    /**
     * @param array{endpoint: string, method: string, raw: string, ip: string} $ctx
     */
    private function log(
        array $ctx,
        ?int $providerId,
        bool $signatureValid,
        bool $ipAllowed,
        string $result,
        ?int $conversionId,
        ?string $error
    ): void {
        $this->postbacks->logPostback([
            'provider_id'       => $providerId,
            'endpoint'          => substr($ctx['endpoint'], 0, 120),
            'http_method'       => substr($ctx['method'], 0, 8),
            'raw_payload'       => $ctx['raw'],
            'ip_address'        => $ctx['ip'],
            'signature_valid'   => $signatureValid ? 1 : 0,
            'ip_allowed'        => $ipAllowed ? 1 : 0,
            'processing_result' => $result,
            'conversion_id'     => $conversionId,
            'error_message'     => $error !== null ? substr($error, 0, 512) : null,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function str(array $payload, string $key): string
    {
        $value = $payload[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
