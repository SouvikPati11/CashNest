<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Withdrawal money calculator.
 *
 * Computes the gross cash value, fee, and net payout for a coin amount using
 * exact fixed-point integer arithmetic (the bcmath extension is not part of the
 * shared-hosting baseline). Cash values follow the DB precision `DECIMAL(18,4)`
 * and are returned as canonical 4-dp strings. Coins remain the authoritative,
 * integer unit of account throughout.
 */
final class WithdrawCalculator
{
    /** Cash values are tracked in ten-thousandths (4 decimal places). */
    private const CASH_SCALE = 4;

    /** Conversion rate precision is `DECIMAL(18,8)`. */
    private const RATE_SCALE = 8;

    /** Fee percent precision is `DECIMAL(6,4)` and expressed as a fraction. */
    private const PERCENT_SCALE = 4;

    /**
     * @return array{cash_amount: string, fee_amount: string, net_amount: string}
     */
    public function compute(int $coins, string $rate, string $feePercent, string $feeFlat): array
    {
        $rateE8    = $this->toScaledInt($rate, self::RATE_SCALE);
        $percentE4 = $this->toScaledInt($feePercent, self::PERCENT_SCALE);
        $flatE4    = $this->toScaledInt($feeFlat, self::CASH_SCALE);

        // cash(1e-4) = coins * rate = coins * rateE8 / 1e8, expressed in 1e-4 units.
        $cash4 = $this->mulDivRound($coins, $rateE8, 10 ** (self::RATE_SCALE - self::CASH_SCALE));

        // percentage fee, then flat fee, both in 1e-4 units.
        $feePct4 = $this->mulDivRound($cash4, $percentE4, 10 ** self::PERCENT_SCALE);
        $fee4    = $feePct4 + $flatE4;

        if ($fee4 > $cash4) {
            $fee4 = $cash4;
        }

        $net4 = $cash4 - $fee4;

        return [
            'cash_amount' => $this->fromScaledInt($cash4, self::CASH_SCALE),
            'fee_amount'  => $this->fromScaledInt($fee4, self::CASH_SCALE),
            'net_amount'  => $this->fromScaledInt($net4, self::CASH_SCALE),
        ];
    }

    /**
     * Parse a non-negative decimal string into an integer scaled by 10^$scale.
     *
     * Extra fractional digits beyond $scale are truncated (values in this module
     * never carry more precision than their declared scale).
     */
    public function toScaledInt(string $decimal, int $scale): int
    {
        $decimal = trim($decimal);

        if (preg_match('/^\d+(\.\d+)?$/', $decimal) !== 1) {
            return 0;
        }

        $parts    = explode('.', $decimal, 2);
        $integer  = $parts[0];
        $fraction = $parts[1] ?? '';

        $fraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);

        return (int) $integer * (10 ** $scale) + (int) ($fraction === '' ? '0' : $fraction);
    }

    /**
     * Render an integer scaled by 10^$scale back to a canonical decimal string.
     */
    private function fromScaledInt(int $value, int $scale): string
    {
        $unit     = 10 ** $scale;
        $integer  = intdiv($value, $unit);
        $fraction = $value % $unit;

        return $integer . '.' . str_pad((string) $fraction, $scale, '0', STR_PAD_LEFT);
    }

    /**
     * Compute round($a * $b / $divisor) with half-up rounding, using integer
     * math and a float fallback only for magnitudes that would overflow int.
     */
    private function mulDivRound(int $a, int $b, int $divisor): int
    {
        if ($divisor <= 0) {
            return 0;
        }

        if ($a !== 0 && intdiv(PHP_INT_MAX, $a) < $b) {
            return (int) round(($a * $b) / $divisor);
        }

        $product   = $a * $b;
        $quotient  = intdiv($product, $divisor);
        $remainder = $product % $divisor;

        if ($remainder * 2 >= $divisor) {
            $quotient++;
        }

        return $quotient;
    }
}
