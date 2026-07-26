<?php

declare(strict_types=1);

namespace Tests\Unit\Withdraw;

use App\Services\WithdrawCalculator;
use PHPUnit\Framework\TestCase;

final class WithdrawCalculatorTest extends TestCase
{
    private WithdrawCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new WithdrawCalculator();
    }

    public function testComputesCashWithNoFee(): void
    {
        $result = $this->calculator->compute(5000, '0.00100000', '0.0000', '0.0000');

        self::assertSame('5.0000', $result['cash_amount']);
        self::assertSame('0.0000', $result['fee_amount']);
        self::assertSame('5.0000', $result['net_amount']);
    }

    public function testComputesPercentageFee(): void
    {
        $result = $this->calculator->compute(10000, '0.00100000', '0.0250', '0.0000');

        // 10.0000 gross, 2.5% fee = 0.2500, net 9.7500.
        self::assertSame('10.0000', $result['cash_amount']);
        self::assertSame('0.2500', $result['fee_amount']);
        self::assertSame('9.7500', $result['net_amount']);
    }

    public function testComputesPercentagePlusFlatFee(): void
    {
        $result = $this->calculator->compute(10000, '0.00100000', '0.0250', '1.0000');

        // 0.2500 percentage + 1.0000 flat = 1.2500 fee, net 8.7500.
        self::assertSame('1.2500', $result['fee_amount']);
        self::assertSame('8.7500', $result['net_amount']);
    }

    public function testRoundsHalfUpToFourDecimals(): void
    {
        // 333 * 0.001 = 0.333 → 0.3330 exactly at 4dp.
        $result = $this->calculator->compute(333, '0.00100000', '0.0000', '0.0000');

        self::assertSame('0.3330', $result['cash_amount']);
    }

    public function testFeeNeverExceedsCash(): void
    {
        $result = $this->calculator->compute(1000, '0.00100000', '0.0000', '999.0000');

        // Flat fee dwarfs the 1.0000 cash value; fee is clamped and net is zero.
        self::assertSame('1.0000', $result['cash_amount']);
        self::assertSame('1.0000', $result['fee_amount']);
        self::assertSame('0.0000', $result['net_amount']);
    }

    public function testToScaledIntTruncatesExtraPrecision(): void
    {
        self::assertSame(100000, $this->calculator->toScaledInt('0.00100000', 8));
        self::assertSame(250, $this->calculator->toScaledInt('0.0250', 4));
        self::assertSame(0, $this->calculator->toScaledInt('not-a-number', 4));
    }
}
