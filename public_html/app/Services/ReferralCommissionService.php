<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\ReferralCommissionServiceInterface;
use App\Contracts\ReferralRepositoryInterface;
use App\Models\Referral;
use App\Models\WalletTransaction;
use Core\Contracts\LoggerInterface;

/**
 * Referral commission service.
 *
 * On a referred user's earning: (1) if the referral is still pending and the
 * config qualifies on first earn, pay the one-time signup bonuses; (2) credit
 * the referrer a percentage commission on the earning. Every credit flows
 * through the LedgerService with a deterministic reference id, so nothing is
 * ever double-paid.
 */
final class ReferralCommissionService implements ReferralCommissionServiceInterface
{
    public function __construct(
        private ReferralRepositoryInterface $referrals,
        private LedgerServiceInterface $ledger,
        private LoggerInterface $logger
    ) {
    }

    public function applyForEarning(int $earnerUserId, WalletTransaction $earning): void
    {
        $referralRow = $this->referrals->findByReferee($earnerUserId);

        if ($referralRow === null) {
            return; // user was not referred
        }

        $config = $this->referrals->activeConfig();
        if ($config === null) {
            return;
        }

        // Qualify + pay one-time bonuses on the first qualifying earn.
        if (
            (string) $referralRow['status'] === Referral::STATUS_PENDING
            && (string) ($config['qualification_rule'] ?? '') === 'on_first_earn'
        ) {
            $this->qualifyAndReward($referralRow);
            $refreshed = $this->referrals->findByReferee($earnerUserId);
            if ($refreshed !== null) {
                $referralRow = $refreshed;
            }
        }

        $this->payCommission($referralRow, $earnerUserId, $earning, $config);
    }

    /**
     * @param array<string, mixed> $referralRow
     * @param array<string, mixed> $config
     */
    private function payCommission(
        array $referralRow,
        int $earnerUserId,
        WalletTransaction $earning,
        array $config
    ): void {
        $percent = (float) ($config['commission_percent'] ?? 0);
        if ($percent <= 0) {
            return;
        }

        if (!$this->withinCommissionWindow($referralRow, $config)) {
            return;
        }

        $commission = (int) floor($earning->amount() * $percent);
        if ($commission <= 0) {
            return;
        }

        $sourceTxnId = (int) $earning->id();

        // One commission per source earning (idempotent).
        if ($this->referrals->findEarningBySourceTxn($sourceTxnId) !== null) {
            return;
        }

        $referrerId = (int) $referralRow['referrer_id'];
        $referralId = (int) $referralRow['id'];

        $txn = $this->ledger->credit(
            $referrerId,
            $commission,
            'referral_commission',
            'referral',
            "referral_commission:{$sourceTxnId}",
            ['source_id' => $referralId]
        );

        $this->referrals->createEarning([
            'referral_id'           => $referralId,
            'referrer_id'           => $referrerId,
            'referee_id'            => $earnerUserId,
            'source_transaction_id' => $sourceTxnId,
            'commission_coins'      => $commission,
            'transaction_id'        => $txn->id(),
        ]);

        $this->logger->info('Referral commission credited.', [
            'referrer_id' => $referrerId,
            'commission'  => $commission,
        ]);
    }

    /**
     * @param array<string, mixed> $referralRow
     */
    private function qualifyAndReward(array $referralRow): void
    {
        $referralId  = (int) $referralRow['id'];
        $referrerId  = (int) $referralRow['referrer_id'];
        $refereeId   = (int) $referralRow['referee_id'];
        $now         = gmdate('Y-m-d H:i:s');

        $updates = [
            'status'       => Referral::STATUS_REWARDED,
            'qualified_at' => $now,
            'rewarded_at'  => $now,
        ];

        $referrerBonus = (int) ($referralRow['signup_bonus_coins'] ?? 0);
        if ($referrerBonus > 0) {
            $t = $this->ledger->credit(
                $referrerId,
                $referrerBonus,
                'referral',
                'referral',
                "referral_bonus_referrer:{$referralId}",
                ['source_id' => $referralId]
            );
            $updates['referrer_transaction_id'] = $t->id();
        }

        $refereeBonus = (int) ($referralRow['referee_bonus_coins'] ?? 0);
        if ($refereeBonus > 0) {
            $t = $this->ledger->credit(
                $refereeId,
                $refereeBonus,
                'referral',
                'referral',
                "referral_bonus_referee:{$referralId}",
                ['source_id' => $referralId]
            );
            $updates['referee_transaction_id'] = $t->id();
        }

        $this->referrals->updateReferral($referralId, $updates);

        $this->logger->info('Referral qualified and bonuses paid.', ['referral_id' => $referralId]);
    }

    /**
     * @param array<string, mixed> $referralRow
     * @param array<string, mixed> $config
     */
    private function withinCommissionWindow(array $referralRow, array $config): bool
    {
        $days = $config['commission_duration_days'] ?? null;

        if ($days === null) {
            return true; // lifetime
        }

        $createdAt = isset($referralRow['created_at']) && is_string($referralRow['created_at'])
            ? strtotime($referralRow['created_at'] . ' UTC')
            : false;

        if ($createdAt === false) {
            return true;
        }

        return (time() - $createdAt) <= ((int) $days * 86400);
    }
}
