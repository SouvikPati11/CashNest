<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Withdraw method (DATABASE_DESIGN.md §G.1).
 *
 * A supported payout channel (UPI, PayPal, gift card, bank) with per-method
 * limits and fees. `detail_schema` drives client form rendering and server-side
 * validation of the `payment_detail` supplied on a withdraw request.
 */
final class WithdrawMethod extends BaseModel
{
    protected string $table = 'withdraw_methods';

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'code',
        'gateway_id',
        'min_coins',
        'max_coins',
        'fee_percent',
        'fee_flat',
        'detail_schema',
        'icon_url',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'            => 'int',
        'gateway_id'    => 'int',
        'min_coins'     => 'int',
        'max_coins'     => 'int',
        'is_active'     => 'bool',
        'sort_order'    => 'int',
        'detail_schema' => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function code(): string
    {
        $code = $this->get('code');

        return is_string($code) ? $code : '';
    }

    public function minCoins(): int
    {
        return (int) $this->get('min_coins', 0);
    }

    public function maxCoins(): ?int
    {
        $max = $this->get('max_coins');

        return $max === null ? null : (int) $max;
    }

    public function feePercent(): string
    {
        $value = $this->get('fee_percent', '0.0000');

        return is_scalar($value) ? (string) $value : '0.0000';
    }

    public function feeFlat(): string
    {
        $value = $this->get('fee_flat', '0.0000');

        return is_scalar($value) ? (string) $value : '0.0000';
    }

    /**
     * Required payout fields keyed by name (from `detail_schema`).
     *
     * @return array<string, mixed>
     */
    public function detailSchema(): array
    {
        $schema = $this->get('detail_schema');

        return is_array($schema) ? $schema : [];
    }

    public function gatewayId(): ?int
    {
        $gateway = $this->get('gateway_id');

        return $gateway === null ? null : (int) $gateway;
    }
}
