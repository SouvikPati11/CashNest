<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\ReferralRepositoryInterface;

final class InMemoryReferralRepository implements ReferralRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $referrals = [];

    /** @var array<int, array<string, mixed>> */
    public array $earnings = [];

    /** @var array<string, mixed>|null */
    public ?array $config;

    private int $nextReferralId = 1;

    private int $nextEarningId = 1;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [
            'referrer_bonus_coins'      => 500,
            'referee_bonus_coins'       => 100,
            'commission_percent'        => '0.1000',
            'qualification_rule'        => 'on_first_earn',
            'commission_duration_days'  => null,
            'is_active'                 => 1,
        ];
    }

    public function findByReferee(int $refereeId): ?array
    {
        foreach ($this->referrals as $r) {
            if ((int) $r['referee_id'] === $refereeId) {
                return $r;
            }
        }

        return null;
    }

    public function createReferral(array $data): string
    {
        $id = $this->nextReferralId++;
        $this->referrals[$id] = array_merge(['status' => 'pending', 'created_at' => gmdate('Y-m-d H:i:s')], $data, ['id' => $id]);

        return (string) $id;
    }

    public function updateReferral(int $id, array $data): int
    {
        if (!isset($this->referrals[$id])) {
            return 0;
        }
        $this->referrals[$id] = array_merge($this->referrals[$id], $data);

        return 1;
    }

    public function listForReferrer(int $referrerId, int $limit, int $offset, ?string $status): array
    {
        $rows = array_values(array_filter($this->referrals, static function (array $r) use ($referrerId, $status): bool {
            return (int) $r['referrer_id'] === $referrerId && ($status === null || $status === '' || $r['status'] === $status);
        }));

        return array_slice(array_map(static fn(array $r): array => $r + ['referee_name' => $r['referee_name'] ?? null], $rows), $offset, $limit);
    }

    public function countForReferrer(int $referrerId): int
    {
        return count(array_filter($this->referrals, static fn(array $r): bool => (int) $r['referrer_id'] === $referrerId));
    }

    public function countForReferrerByStatus(int $referrerId, string $status): int
    {
        return count(array_filter(
            $this->referrals,
            static fn(array $r): bool => (int) $r['referrer_id'] === $referrerId && $r['status'] === $status
        ));
    }

    public function activeConfig(): ?array
    {
        return $this->config;
    }

    public function createEarning(array $data): string
    {
        if ($this->findEarningBySourceTxn((int) $data['source_transaction_id']) !== null) {
            throw new \RuntimeException('Duplicate earning (unique source_transaction_id).');
        }
        $id = $this->nextEarningId++;
        $this->earnings[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function findEarningBySourceTxn(int $sourceTransactionId): ?array
    {
        foreach ($this->earnings as $e) {
            if ((int) $e['source_transaction_id'] === $sourceTransactionId) {
                return $e;
            }
        }

        return null;
    }

    public function listEarningsForReferrer(int $referrerId, int $limit, int $offset): array
    {
        $rows = array_values(array_filter($this->earnings, static fn(array $e): bool => (int) $e['referrer_id'] === $referrerId));

        return array_slice($rows, $offset, $limit);
    }

    public function sumEarningsForReferrer(int $referrerId): int
    {
        $sum = 0;
        foreach ($this->earnings as $e) {
            if ((int) $e['referrer_id'] === $referrerId) {
                $sum += (int) $e['commission_coins'];
            }
        }

        return $sum;
    }
}
