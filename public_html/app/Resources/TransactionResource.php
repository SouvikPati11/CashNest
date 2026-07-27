<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\WalletTransaction;

/**
 * Wallet transaction API resource.
 *
 * Shapes a ledger entry for API responses (API_SPECIFICATION.md §2.18/§2.19).
 * The internal `reference_id` is never exposed. `created_at` is emitted as ISO-8601 UTC.
 */
final class TransactionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(WalletTransaction $txn, bool $detailed = false): array
    {
        $data = [
            'uuid'          => $txn->uuid(),
            'direction'     => $txn->direction(),
            'amount'        => $txn->amount(),
            'balance_after' => (int) $txn->get('balance_after', 0),
            'type'          => $txn->get('type'),
            'source_module' => $txn->get('source_module'),
            'description'   => $txn->get('description'),
            'created_at'    => self::iso($txn->get('created_at')),
        ];

        if ($detailed) {
            $data['cash_amount'] = $txn->get('cash_amount');
            $data['metadata']    = $txn->get('metadata');
            $data['source_id']   = $txn->get('source_id');
        }

        return $data;
    }

    /**
     * @param list<WalletTransaction> $items
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $items): array
    {
        return array_map(static fn(WalletTransaction $t): array => self::toArray($t), $items);
    }

    /**
     * Convert a stored `Y-m-d H:i:s` UTC datetime to ISO-8601.
     */
    private static function iso(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $timestamp = strtotime($value . ' UTC');

        return $timestamp === false ? $value : gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
}
