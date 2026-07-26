<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Contracts\WalletServiceInterface;
use App\Contracts\WalletTransactionRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Models\WalletTransaction;

/**
 * Wallet service — read side of the wallet module.
 *
 * Serves balances (server-computed from `wallets`), conversion settings, and
 * user-scoped ledger history/detail. It performs no writes; all mutations go
 * through the LedgerService.
 */
final class WalletService implements WalletServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;
    private const MAX_RANGE_SECONDS = 366 * 24 * 60 * 60;

    public function __construct(
        private WalletRepositoryInterface $wallets,
        private WalletTransactionRepositoryInterface $ledger,
        private CurrencyRepositoryInterface $currency
    ) {
    }

    public function getBalance(int $userId): array
    {
        $currency     = $this->currency->getActive();
        $currencyCode = isset($currency['currency_code']) && is_string($currency['currency_code'])
            ? $currency['currency_code']
            : 'INR';

        $wallet = $this->wallets->findByUserId($userId);

        if ($wallet === null) {
            return [
                'coin_balance'   => 0,
                'coin_reserved'  => 0,
                'available'      => 0,
                'cash_balance'   => '0.0000',
                'currency'       => $currencyCode,
                'lifetime_earned' => 0,
                'lifetime_spent'  => 0,
            ];
        }

        $coinBalance = (int) $wallet['coin_balance'];

        return [
            'coin_balance'    => $coinBalance,
            'coin_reserved'   => (int) $wallet['coin_reserved'],
            'available'       => $coinBalance, // reserved already deducted on hold
            'cash_balance'    => (string) $wallet['cash_balance'],
            'currency'        => $currencyCode,
            'lifetime_earned' => (int) $wallet['lifetime_coins_earned'],
            'lifetime_spent'  => (int) $wallet['lifetime_coins_spent'],
        ];
    }

    public function getConversion(): array
    {
        $currency = $this->currency->getActive();

        if ($currency === null) {
            return [
                'coin_to_cash_rate'  => '0.00000000',
                'currency'           => 'INR',
                'min_withdraw_coins' => 0,
                'max_withdraw_coins' => null,
            ];
        }

        $max = $currency['max_withdraw_coins'] ?? null;

        return [
            'coin_to_cash_rate'  => (string) $currency['coin_to_cash_rate'],
            'currency'           => is_string($currency['currency_code'] ?? null) ? $currency['currency_code'] : 'INR',
            'min_withdraw_coins' => (int) ($currency['min_withdraw_coins'] ?? 0),
            'max_withdraw_coins' => $max === null ? null : (int) $max,
        ];
    }

    public function transactionHistory(int $userId, array $params): array
    {
        $limit = $this->resolveLimit($params['limit'] ?? null);
        $sort  = is_string($params['sort'] ?? null) && $params['sort'] !== '' ? $params['sort'] : '-created_at';

        [$column, $dir] = match ($sort) {
            'created_at'  => ['id', 'ASC'],
            'amount'      => ['amount', 'ASC'],
            '-amount'     => ['amount', 'DESC'],
            default       => ['id', 'DESC'], // -created_at
        };

        $useCursor = $column === 'id';
        $beforeId  = $useCursor ? $this->decodeCursor($params['cursor'] ?? null) : null;
        $page      = max(1, (int) ($params['page'] ?? 1));
        $offset    = $useCursor ? 0 : ($page - 1) * $limit;

        $filters = $this->resolveFilters($params);

        // Fetch one extra row to detect a further page.
        $rows = $this->ledger->queryForUser($userId, $filters, $column, $dir, $limit + 1, $beforeId, $offset);

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_values(
            array_map(static fn(array $row): WalletTransaction => WalletTransaction::fromRow($row), $rows)
        );

        $nextCursor = null;
        if ($useCursor && $hasMore && $items !== []) {
            $last       = end($items);
            $lastId     = $last->id();
            $nextCursor = $lastId !== null ? $this->encodeCursor($lastId) : null;
        }

        return [
            'items'       => $items,
            'has_more'    => $hasMore,
            'next_cursor' => $nextCursor,
            'limit'       => $limit,
            'page'        => $useCursor ? null : $page,
            'use_cursor'  => $useCursor,
        ];
    }

    public function findTransactionForUser(string $uuid, int $userId): ?WalletTransaction
    {
        $row = $this->ledger->findByUuidForUser($uuid, $userId);

        return $row === null ? null : WalletTransaction::fromRow($row);
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }

    /**
     * Normalise and validate filter params (dates parsed to UTC datetimes).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function resolveFilters(array $params): array
    {
        $from = $this->parseDate($params['date_from'] ?? null, 'date_from');
        $to   = $this->parseDate($params['date_to'] ?? null, 'date_to');

        if ($from !== null && $to !== null) {
            $span = strtotime($to . ' UTC') - strtotime($from . ' UTC');
            if ($span > self::MAX_RANGE_SECONDS) {
                throw new ValidationException(['date_from' => ['The date range may not exceed one year.']]);
            }
        }

        return [
            'type'      => is_string($params['type'] ?? null) ? $params['type'] : null,
            'direction' => is_string($params['direction'] ?? null) ? $params['direction'] : null,
            'date_from' => $from,
            'date_to'   => $to,
        ];
    }

    /**
     * Parse a client date to a `Y-m-d H:i:s` UTC string, or null when absent.
     */
    private function parseDate(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw new ValidationException([$field => ['The ' . $field . ' must be a valid date.']]);
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            throw new ValidationException([$field => ['The ' . $field . ' must be a valid date.']]);
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function encodeCursor(int $id): string
    {
        return rtrim(strtr(base64_encode((string) $id), '+/', '-_'), '=');
    }

    private function decodeCursor(mixed $cursor): ?int
    {
        if (!is_string($cursor) || $cursor === '') {
            return null;
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        return $decoded !== false && ctype_digit($decoded) ? (int) $decoded : null;
    }
}
