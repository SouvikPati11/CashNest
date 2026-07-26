<?php

declare(strict_types=1);

namespace App\Requests\Withdraw;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/withdraw/request (API_SPECIFICATION.md §2.50).
 *
 * Amounts/fees/rates are server-computed; the client supplies only the method,
 * the coin amount, and the method-specific payout detail.
 */
final class WithdrawRequestRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'method_code'    => 'required|string|max:40',
            'coins_amount'   => 'required|integer|min:1',
            'payment_detail' => 'required|array',
        ];
    }
}
