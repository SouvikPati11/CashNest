<?php

declare(strict_types=1);

namespace App\Requests\Wallet;

use App\Models\WalletTransaction;
use App\Requests\FormRequest;

/**
 * Validation for GET /v1/wallet/transactions (API_SPECIFICATION.md §2.18).
 *
 * Reads from the query string (flattening the `filter[...]` group) and validates
 * the allowed filter/sort/pagination values. Date-range limits and parsing are
 * enforced in the service.
 */
final class TransactionHistoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function data(): array
    {
        $query  = $this->request->allQuery();
        $filter = is_array($query['filter'] ?? null) ? $query['filter'] : [];

        return [
            'type'      => $filter['type'] ?? null,
            'direction' => $filter['direction'] ?? null,
            'date_from' => $filter['date_from'] ?? null,
            'date_to'   => $filter['date_to'] ?? null,
            'sort'      => $query['sort'] ?? null,
            'limit'     => $query['limit'] ?? null,
            'page'      => $query['page'] ?? null,
            'cursor'    => $query['cursor'] ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'type'      => 'nullable|in:' . implode(',', WalletTransaction::TYPES),
            'direction' => 'nullable|in:credit,debit',
            'date_from' => 'nullable|string|max:40',
            'date_to'   => 'nullable|string|max:40',
            'sort'      => 'nullable|in:created_at,-created_at,amount,-amount',
            'limit'     => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
            'cursor'    => 'nullable|string|max:191',
        ];
    }
}
