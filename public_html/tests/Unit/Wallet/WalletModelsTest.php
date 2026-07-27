<?php

declare(strict_types=1);

namespace Tests\Unit\Wallet;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use PHPUnit\Framework\TestCase;

final class WalletModelsTest extends TestCase
{
    public function testWalletCastsAndAvailable(): void
    {
        $wallet = Wallet::fromRow([
            'id'            => '2',
            'user_id'       => '9',
            'coin_balance'  => '4000',
            'coin_reserved' => '200',
            'version'       => '5',
        ]);

        self::assertSame(2, $wallet->id());
        self::assertSame(4000, $wallet->coinBalance());
        self::assertSame(200, $wallet->coinReserved());
        self::assertSame(4000, $wallet->available()); // reserved already excluded
    }

    public function testTransactionHidesReferenceId(): void
    {
        $txn = WalletTransaction::fromRow([
            'id'           => 1,
            'uuid'         => 'wt-1',
            'amount'       => '100',
            'direction'    => 'credit',
            'reference_id' => 'secret-ref',
            'metadata'     => '{"k":"v"}',
        ]);

        self::assertArrayNotHasKey('reference_id', $txn->toArray());
        self::assertSame(100, $txn->amount());
        self::assertSame(['k' => 'v'], $txn->get('metadata')); // json cast
    }

    public function testTypesConstantMatchesSchemaEnum(): void
    {
        self::assertContains('withdrawal_hold', WalletTransaction::TYPES);
        self::assertContains('reversal', WalletTransaction::TYPES);
        self::assertCount(16, WalletTransaction::TYPES);
    }
}
