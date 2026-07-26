<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Contracts\WalletTransactionRepositoryInterface;
use App\Exceptions\HttpException;
use App\Helpers\Security;
use App\Models\WalletTransaction;
use Core\Contracts\LoggerInterface;

/**
 * Ledger service — the sole writer of coin balances.
 *
 * Guarantees (per DATABASE_DESIGN.md §B.1/§B.2):
 *  - Every movement is wrapped in a DB transaction.
 *  - The wallet row is locked FOR UPDATE, serialising per-user mutations.
 *  - Operations are idempotent by `reference_id` (checked under the lock, and
 *    backed by the unique DB constraint).
 *  - Exactly one immutable ledger row is written for every balance change; the
 *    balance is never updated without it.
 */
final class LedgerService implements LedgerServiceInterface
{
    public function __construct(
        private TransactionRunnerInterface $runner,
        private WalletRepositoryInterface $wallets,
        private WalletTransactionRepositoryInterface $ledger,
        private LoggerInterface $logger
    ) {
    }

    public function credit(
        int $userId,
        int $amount,
        string $type,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction {
        $apply = static function (array $w) use ($amount): array {
            $balance = (int) $w['coin_balance'] + $amount;

            return [
                'coin_balance'          => $balance,
                'coin_reserved'         => (int) $w['coin_reserved'],
                'lifetime_coins_earned' => (int) $w['lifetime_coins_earned'] + $amount,
                'lifetime_coins_spent'  => (int) $w['lifetime_coins_spent'],
                'balance_after'         => $balance,
            ];
        };

        return $this->write(
            $userId,
            $amount,
            WalletTransaction::DIRECTION_CREDIT,
            $type,
            $sourceModule,
            $referenceId,
            $options,
            $apply
        );
    }

    public function debit(
        int $userId,
        int $amount,
        string $type,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction {
        $apply = static function (array $w) use ($amount): array {
            $current = (int) $w['coin_balance'];

            if ($current < $amount) {
                throw new HttpException(422, 'INSUFFICIENT_BALANCE', 'Not enough coins for this operation.');
            }

            $balance = $current - $amount;

            return [
                'coin_balance'          => $balance,
                'coin_reserved'         => (int) $w['coin_reserved'],
                'lifetime_coins_earned' => (int) $w['lifetime_coins_earned'],
                'lifetime_coins_spent'  => (int) $w['lifetime_coins_spent'] + $amount,
                'balance_after'         => $balance,
            ];
        };

        return $this->write(
            $userId,
            $amount,
            WalletTransaction::DIRECTION_DEBIT,
            $type,
            $sourceModule,
            $referenceId,
            $options,
            $apply
        );
    }

    public function reserve(
        int $userId,
        int $amount,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction {
        $apply = static function (array $w) use ($amount): array {
            $current = (int) $w['coin_balance'];

            if ($current < $amount) {
                throw new HttpException(422, 'INSUFFICIENT_BALANCE', 'Not enough coins to reserve.');
            }

            $balance = $current - $amount;

            return [
                'coin_balance'          => $balance,
                'coin_reserved'         => (int) $w['coin_reserved'] + $amount,
                'lifetime_coins_earned' => (int) $w['lifetime_coins_earned'],
                'lifetime_coins_spent'  => (int) $w['lifetime_coins_spent'],
                'balance_after'         => $balance,
            ];
        };

        return $this->write(
            $userId,
            $amount,
            WalletTransaction::DIRECTION_DEBIT,
            WalletTransaction::TYPE_WITHDRAWAL_HOLD,
            $sourceModule,
            $referenceId,
            $options,
            $apply
        );
    }

    public function release(
        int $userId,
        int $amount,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction {
        $apply = static function (array $w) use ($amount): array {
            $reserved = (int) $w['coin_reserved'];

            if ($reserved < $amount) {
                throw new HttpException(422, 'INSUFFICIENT_BALANCE', 'Not enough reserved coins to release.');
            }

            $balance = (int) $w['coin_balance'] + $amount;

            return [
                'coin_balance'          => $balance,
                'coin_reserved'         => $reserved - $amount,
                'lifetime_coins_earned' => (int) $w['lifetime_coins_earned'],
                'lifetime_coins_spent'  => (int) $w['lifetime_coins_spent'],
                'balance_after'         => $balance,
            ];
        };

        return $this->write(
            $userId,
            $amount,
            WalletTransaction::DIRECTION_CREDIT,
            WalletTransaction::TYPE_WITHDRAWAL_RELEASE,
            $sourceModule,
            $referenceId,
            $options,
            $apply
        );
    }

    /**
     * The single guarded path all money movements funnel through.
     *
     * @param array<string, mixed>                               $options
     * @param callable(array<string, mixed>): array<string, int> $apply Computes new balances; throws on invalid state.
     */
    private function write(
        int $userId,
        int $amount,
        string $direction,
        string $type,
        string $sourceModule,
        string $referenceId,
        array $options,
        callable $apply
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'Amount must be a positive integer.');
        }

        if (!in_array($type, WalletTransaction::TYPES, true)) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'Invalid transaction type.');
        }

        if ($referenceId === '') {
            throw new HttpException(422, 'VALIDATION_ERROR', 'A reference id is required.');
        }

        return $this->runner->transaction(function () use (
            $userId,
            $amount,
            $direction,
            $type,
            $sourceModule,
            $referenceId,
            $options,
            $apply
        ): WalletTransaction {
            $wallet = $this->wallets->lockByUserId($userId) ?? $this->createAndLock($userId);

            // Idempotency: same-user mutations serialise on the wallet lock, so a
            // committed duplicate is visible here and returned unchanged.
            $existing = $this->ledger->findByReferenceId($referenceId);
            if ($existing !== null) {
                return WalletTransaction::fromRow($existing);
            }

            $balances = $apply($wallet);
            $walletId = (int) $wallet['id'];

            $ledgerId = (int) $this->ledger->create([
                'uuid'                   => Security::uuid4(),
                'user_id'                => $userId,
                'wallet_id'              => $walletId,
                'direction'              => $direction,
                'amount'                 => $amount,
                'cash_amount'            => $this->optionString($options, 'cash_amount'),
                'balance_after'          => $balances['balance_after'],
                'type'                   => $type,
                'source_module'          => $sourceModule,
                'source_id'              => $this->optionInt($options, 'source_id'),
                'reference_id'           => $referenceId,
                'related_transaction_id' => $this->optionInt($options, 'related_transaction_id'),
                'performed_by_admin_id'  => $this->optionInt($options, 'performed_by_admin_id'),
                'description'            => $this->optionString($options, 'description'),
                'metadata'               => $this->encodeMetadata($options),
            ]);

            $this->wallets->applyBalances($walletId, [
                'coin_balance'          => $balances['coin_balance'],
                'coin_reserved'         => $balances['coin_reserved'],
                'lifetime_coins_earned' => $balances['lifetime_coins_earned'],
                'lifetime_coins_spent'  => $balances['lifetime_coins_spent'],
                'version'               => (int) $wallet['version'] + 1,
                'last_transaction_id'   => $ledgerId,
            ]);

            $this->logger->info('Ledger entry written.', [
                'user_id'   => $userId,
                'direction' => $direction,
                'type'      => $type,
                'amount'    => $amount,
            ]);

            $row = $this->ledger->find($ledgerId);

            return WalletTransaction::fromRow($row ?? []);
        });
    }

    /**
     * Create a wallet then re-acquire the lock (first-ever movement for a user).
     *
     * @return array<string, mixed>
     */
    private function createAndLock(int $userId): array
    {
        $this->wallets->create(['user_id' => $userId]);

        $wallet = $this->wallets->lockByUserId($userId);

        if ($wallet === null) {
            throw new HttpException(500, 'INTERNAL_ERROR', 'Wallet could not be initialised.');
        }

        return $wallet;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function optionString(array $options, string $key): ?string
    {
        return isset($options[$key]) && is_string($options[$key]) && $options[$key] !== '' ? $options[$key] : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function optionInt(array $options, string $key): ?int
    {
        return isset($options[$key]) && (is_int($options[$key]) || ctype_digit((string) $options[$key]))
            ? (int) $options[$key]
            : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function encodeMetadata(array $options): ?string
    {
        if (!isset($options['metadata']) || !is_array($options['metadata']) || $options['metadata'] === []) {
            return null;
        }

        $json = json_encode($options['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }
}
