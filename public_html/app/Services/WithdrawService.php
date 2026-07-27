<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\FraudFlagRepositoryInterface;
use App\Contracts\KycRepositoryInterface;
use App\Contracts\LedgerServiceInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Contracts\WalletTransactionRepositoryInterface;
use App\Contracts\WithdrawMethodRepositoryInterface;
use App\Contracts\WithdrawRequestRepositoryInterface;
use App\Contracts\WithdrawServiceInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\Security;
use App\Models\WithdrawMethod;
use App\Models\WithdrawRequest;
use Core\Config;
use Core\Contracts\LoggerInterface;

/**
 * Withdraw service (user-facing).
 *
 * Creates payout requests and serves methods/history/detail/cancel. Coins are
 * reserved and released ONLY through LedgerService; balances are never mutated
 * directly. Requests are idempotent on the client `X-Idempotency-Key` (folded
 * into the ledger hold `reference_id`), so a retry never double-holds or creates
 * a duplicate request.
 */
final class WithdrawService implements WithdrawServiceInterface
{
    private const SOURCE_MODULE = 'withdraw';
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private WithdrawMethodRepositoryInterface $methods,
        private WithdrawRequestRepositoryInterface $requests,
        private WalletRepositoryInterface $wallets,
        private WalletTransactionRepositoryInterface $ledgerRepo,
        private CurrencyRepositoryInterface $currency,
        private KycRepositoryInterface $kyc,
        private FraudFlagRepositoryInterface $fraud,
        private LedgerServiceInterface $ledger,
        private WithdrawCalculator $calculator,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function methods(): array
    {
        return array_map(function (array $row): array {
            $method = WithdrawMethod::fromRow($row);

            return [
                'code'          => $method->code(),
                'name'          => $row['name'] ?? null,
                'min_coins'     => $method->minCoins(),
                'max_coins'     => $method->maxCoins(),
                'fee_percent'   => $method->feePercent(),
                'fee_flat'      => $method->feeFlat(),
                'detail_schema' => $method->detailSchema(),
                'icon_url'      => $row['icon_url'] ?? null,
            ];
        }, $this->methods->activeMethods());
    }

    public function request(
        int $userId,
        string $methodCode,
        int $coinsAmount,
        array $paymentDetail,
        string $idempotencyKey,
        ?string $ip
    ): array {
        // Idempotent replay: if this key already produced a request, return it
        // before re-validating (the prior hold has already moved the balance).
        if ($idempotencyKey !== '') {
            $replay = $this->findExistingByIdempotencyKey($idempotencyKey);
            if ($replay !== null) {
                return $replay;
            }
        }

        $methodRow = $this->methods->findActiveByCode($methodCode);
        if ($methodRow === null) {
            throw new ValidationException(['method_code' => ['The selected withdrawal method is unavailable.']]);
        }
        $method = WithdrawMethod::fromRow($methodRow);

        $currency = $this->currency->getActive();
        if ($currency === null) {
            throw new HttpException(500, 'INTERNAL_ERROR', 'Withdrawal is temporarily unavailable.');
        }

        $this->assertAmountWithinLimits($coinsAmount, $method, $currency);
        $this->assertPaymentDetail($method, $paymentDetail);
        $this->assertNotFraudHeld($userId);
        $this->assertKyc($userId, $coinsAmount);
        $this->assertSufficientBalance($userId, $coinsAmount);

        $rate  = (string) ($currency['coin_to_cash_rate'] ?? '0');
        $money = $this->calculator->compute($coinsAmount, $rate, $method->feePercent(), $method->feeFlat());

        $uuid    = Security::uuid4();
        $holdRef = 'withdraw_hold:' . ($idempotencyKey !== '' ? $idempotencyKey : $uuid);

        $hold = $this->ledger->reserve($userId, $coinsAmount, self::SOURCE_MODULE, $holdRef, [
            'cash_amount' => $money['cash_amount'],
            'description' => 'Withdrawal hold',
            'metadata'    => ['method' => $methodCode],
        ]);
        $holdId = (int) $hold->id();

        // Idempotent replay: this hold already backs a request — return it.
        $existing = $this->requests->findByHoldTransactionId($holdId);
        if ($existing !== null) {
            return $this->presentRequest($existing);
        }

        $currencyCode = is_string($currency['currency_code'] ?? null) ? $currency['currency_code'] : 'INR';

        $requestId = (int) $this->requests->create([
            'uuid'                => $uuid,
            'user_id'             => $userId,
            'method_id'           => (int) $method->id(),
            'gateway_id'          => $method->gatewayId(),
            'coins_amount'        => $coinsAmount,
            'cash_amount'         => $money['cash_amount'],
            'fee_amount'          => $money['fee_amount'],
            'net_amount'          => $money['net_amount'],
            'currency_code'       => $currencyCode,
            'conversion_rate'     => $rate,
            'payment_detail'      => $this->encodeDetail($paymentDetail),
            'status'              => WithdrawRequest::STATUS_PENDING,
            'hold_transaction_id' => $holdId,
            'requested_ip'        => $ip,
        ]);

        $this->requests->addHistory([
            'withdraw_request_id' => $requestId,
            'from_status'         => null,
            'to_status'           => WithdrawRequest::STATUS_PENDING,
            'note'                => 'Withdrawal requested.',
        ]);

        $this->logger->info('Withdrawal requested.', [
            'user_id'    => $userId,
            'request_id' => $requestId,
            'coins'      => $coinsAmount,
        ]);

        $row = $this->requests->find($requestId);

        return $this->presentRequest($row ?? []);
    }

    public function history(int $userId, array $params): array
    {
        $limit  = $this->resolveLimit($params['limit'] ?? null);
        $page   = max(1, (int) ($params['page'] ?? 1));
        $status = is_string($params['status'] ?? null) && $params['status'] !== '' ? $params['status'] : null;

        $rows    = $this->requests->listForUser($userId, $limit + 1, ($page - 1) * $limit, $status);
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        return [
            'items'    => array_map(fn(array $row): array => $this->presentSummary($row), $rows),
            'has_more' => $hasMore,
            'page'     => $page,
            'limit'    => $limit,
        ];
    }

    public function detail(int $userId, string $uuid): array
    {
        $row = $this->requests->findByUuidForUser($uuid, $userId);
        if ($row === null) {
            throw new NotFoundException('Withdrawal request not found.');
        }

        $detail = $this->presentRequest($row);
        $detail['payment_detail'] = $this->decodeDetail($row['payment_detail'] ?? null);
        $detail['history'] = array_map(static fn(array $h): array => [
            'from_status' => $h['from_status'] ?? null,
            'to_status'   => (string) $h['to_status'],
            'note'        => $h['note'] ?? null,
            'created_at'  => $h['created_at'] ?? null,
        ], $this->requests->historyForRequest((int) $row['id']));

        return $detail;
    }

    public function cancel(int $userId, string $uuid): array
    {
        $row = $this->requests->findByUuidForUser($uuid, $userId);
        if ($row === null) {
            throw new NotFoundException('Withdrawal request not found.');
        }

        $request = WithdrawRequest::fromRow($row);

        // Idempotent re-cancel: already cancelled, report current state.
        if ($request->status() === WithdrawRequest::STATUS_CANCELLED) {
            return [
                'status'         => WithdrawRequest::STATUS_CANCELLED,
                'refunded_coins' => 0,
                'new_balance'    => $this->currentBalance($userId),
            ];
        }

        if (!$request->canTransitionTo(WithdrawRequest::STATUS_CANCELLED)) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'This request can no longer be cancelled.');
        }

        $coins = $request->coinsAmount();

        $refund = $this->ledger->release(
            $userId,
            $coins,
            self::SOURCE_MODULE,
            'withdraw_release:' . $uuid,
            ['description' => 'Withdrawal cancelled by user', 'source_id' => (int) $row['id']]
        );

        $this->requests->updateRequest((int) $row['id'], [
            'status'                => WithdrawRequest::STATUS_CANCELLED,
            'refund_transaction_id' => (int) $refund->id(),
            'processed_at'          => gmdate('Y-m-d H:i:s'),
        ]);

        $this->requests->addHistory([
            'withdraw_request_id' => (int) $row['id'],
            'from_status'         => WithdrawRequest::STATUS_PENDING,
            'to_status'           => WithdrawRequest::STATUS_CANCELLED,
            'note'                => 'Cancelled by user.',
        ]);

        return [
            'status'         => WithdrawRequest::STATUS_CANCELLED,
            'refunded_coins' => $coins,
            'new_balance'    => $this->currentBalance($userId),
        ];
    }

    /**
     * Return an already-created request for this idempotency key, or null.
     *
     * @return array<string, mixed>|null
     */
    private function findExistingByIdempotencyKey(string $idempotencyKey): ?array
    {
        $hold = $this->ledgerRepo->findByReferenceId('withdraw_hold:' . $idempotencyKey);
        if ($hold === null) {
            return null;
        }

        $request = $this->requests->findByHoldTransactionId((int) $hold['id']);

        return $request === null ? null : $this->presentRequest($request);
    }

    /**
     * @param array<string, mixed> $currency
     */
    private function assertAmountWithinLimits(int $coins, WithdrawMethod $method, array $currency): void
    {
        if ($coins <= 0) {
            throw new ValidationException(['coins_amount' => ['The coins amount must be a positive integer.']]);
        }

        $min = max($method->minCoins(), (int) ($currency['min_withdraw_coins'] ?? 0));
        if ($coins < $min) {
            throw new HttpException(
                422,
                'MIN_WITHDRAW_NOT_MET',
                sprintf('The minimum withdrawal is %d coins.', $min)
            );
        }

        $caps = array_filter([
            $method->maxCoins(),
            isset($currency['max_withdraw_coins']) ? (int) $currency['max_withdraw_coins'] : null,
        ], static fn(?int $cap): bool => $cap !== null && $cap > 0);

        if ($caps !== [] && $coins > min($caps)) {
            throw new ValidationException([
                'coins_amount' => [sprintf('The maximum withdrawal is %d coins.', (int) min($caps))],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $paymentDetail
     */
    private function assertPaymentDetail(WithdrawMethod $method, array $paymentDetail): void
    {
        foreach (array_keys($method->detailSchema()) as $field) {
            $value = $paymentDetail[$field] ?? null;

            if (!is_scalar($value) || trim((string) $value) === '') {
                throw new ValidationException([
                    'payment_detail' => [sprintf('The %s field is required for this method.', (string) $field)],
                ]);
            }
        }
    }

    private function assertNotFraudHeld(int $userId): void
    {
        if ($this->fraud->hasActiveWithdrawHold($userId)) {
            throw new ForbiddenException('Withdrawals are temporarily on hold.', 'FRAUD_HOLD');
        }
    }

    private function assertKyc(int $userId, int $coins): void
    {
        $threshold = (int) $this->config->get('withdraw.kyc_required_threshold_coins', 0);

        if ($coins < $threshold) {
            return;
        }

        $kyc = $this->kyc->findByUserId($userId);

        if ($kyc === null || ($kyc['status'] ?? null) !== 'approved') {
            throw new ForbiddenException('Complete KYC to withdraw.', 'KYC_REQUIRED');
        }
    }

    private function assertSufficientBalance(int $userId, int $coins): void
    {
        if ($this->currentBalance($userId) < $coins) {
            throw new HttpException(422, 'INSUFFICIENT_BALANCE', 'Not enough coins available.');
        }
    }

    private function currentBalance(int $userId): int
    {
        $wallet = $this->wallets->findByUserId($userId);

        return $wallet === null ? 0 : (int) $wallet['coin_balance'];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presentRequest(array $row): array
    {
        $summary = $this->presentSummary($row);

        $summary['conversion_rate'] = (string) ($row['conversion_rate'] ?? '0');
        $summary['hold_transaction_uuid'] = $this->transactionUuid($row['hold_transaction_id'] ?? null);

        return $summary;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presentSummary(array $row): array
    {
        return [
            'uuid'         => $row['uuid'] ?? null,
            'status'       => (string) ($row['status'] ?? WithdrawRequest::STATUS_PENDING),
            'coins_amount' => (int) ($row['coins_amount'] ?? 0),
            'cash_amount'  => (string) ($row['cash_amount'] ?? '0.0000'),
            'fee_amount'   => (string) ($row['fee_amount'] ?? '0.0000'),
            'net_amount'   => (string) ($row['net_amount'] ?? '0.0000'),
            'currency'     => (string) ($row['currency_code'] ?? 'INR'),
            'created_at'   => $row['created_at'] ?? null,
            'processed_at' => $row['processed_at'] ?? null,
        ];
    }

    private function transactionUuid(mixed $transactionId): ?string
    {
        if ($transactionId === null) {
            return null;
        }

        $txn = $this->ledgerRepo->find((int) $transactionId);

        return is_string($txn['uuid'] ?? null) ? $txn['uuid'] : null;
    }

    /**
     * @param array<string, mixed> $detail
     */
    private function encodeDetail(array $detail): string
    {
        $json = json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '{}' : $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeDetail(mixed $detail): array
    {
        if (is_array($detail)) {
            return $detail;
        }

        if (is_string($detail) && $detail !== '') {
            $decoded = json_decode($detail, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }
}
