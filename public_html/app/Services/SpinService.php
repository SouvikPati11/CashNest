<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\SpinServiceInterface;
use App\Contracts\SpinRepositoryInterface;
use App\Models\SpinSegment;
use App\Exceptions\HttpException;
use Core\Config;
use Core\Contracts\LoggerInterface;

/**
 * Spin wheel service.
 *
 * Server-authoritative outcome by weighted random over active segments. Coin
 * rewards credit ONLY through the LedgerService, keyed `spin:<spinId>`. Enforces
 * a per-day spin cap. Segment weights/rewards are never exposed via /status.
 */
final class SpinService implements SpinServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private SpinRepositoryInterface $spins,
        private LedgerServiceInterface $ledger,
        private WeightedPicker $picker,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function status(int $userId): array
    {
        $today     = gmdate('Y-m-d');
        $dailyLimit = $this->dailyLimit();
        $used      = $this->spins->countSpinsOnDate($userId, $today);

        $segments = array_map(static fn(array $s): array => [
            'id'        => (int) $s['id'],
            'label'     => (string) $s['label'],
            'color_hex' => $s['color_hex'] ?? null,
            'position'  => (int) $s['position'],
        ], $this->spins->activeSegments());

        return [
            'spins_remaining' => max(0, $dailyLimit - $used),
            'daily_limit'     => $dailyLimit,
            'segments'        => $segments,
        ];
    }

    public function spin(int $userId, string $source): array
    {
        $source = in_array($source, ['free', 'ad', 'purchase'], true) ? $source : 'free';
        $today  = gmdate('Y-m-d');

        $dailyLimit = $this->dailyLimit();
        $used       = $this->spins->countSpinsOnDate($userId, $today);

        if ($used >= $dailyLimit) {
            throw new HttpException(429, 'LIMIT_REACHED', 'No spins remaining today.');
        }

        $segments = $this->spins->activeSegments();
        $picked   = $this->picker->pick($segments);

        if ($picked === null) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'The spin wheel is not configured.');
        }

        $rewardType  = is_string($picked['reward_type'] ?? null) ? $picked['reward_type'] : SpinSegment::REWARD_NOTHING;
        $rewardCoins = $rewardType === SpinSegment::REWARD_COINS ? (int) $picked['reward_coins'] : 0;
        $segmentId   = (int) $picked['id'];

        $spinId = (int) $this->spins->createSpin([
            'user_id'      => $userId,
            'segment_id'   => $segmentId,
            'reward_coins' => $rewardCoins,
            'spin_date'    => $today,
            'source'       => $source,
        ]);

        $transactionUuid = null;
        $newBalance      = null;

        if ($rewardCoins > 0) {
            $txn = $this->ledger->credit(
                $userId,
                $rewardCoins,
                'spin',
                'spin',
                "spin:{$spinId}",
                ['source_id' => $spinId]
            );
            $this->spins->updateSpin($spinId, ['transaction_id' => $txn->id()]);
            $transactionUuid = $txn->uuid();
            $newBalance      = (int) $txn->get('balance_after', 0);
        }

        $this->logger->info('Spin performed.', ['user_id' => $userId, 'segment_id' => $segmentId]);

        return [
            'segment_id'       => $segmentId,
            'reward_type'      => $rewardType,
            'reward_coins'     => $rewardCoins,
            'spins_remaining'  => max(0, $dailyLimit - ($used + 1)),
            'new_balance'      => $newBalance,
            'transaction_uuid' => $transactionUuid,
        ];
    }

    public function history(int $userId, array $params): array
    {
        $limit = $this->resolveLimit($params['limit'] ?? null);
        $page  = max(1, (int) ($params['page'] ?? 1));

        $rows = $this->spins->historyForUser($userId, $limit + 1, ($page - 1) * $limit);

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(static fn(array $r): array => [
            'id'           => (int) $r['id'],
            'segment_id'   => (int) $r['segment_id'],
            'reward_coins' => (int) $r['reward_coins'],
            'source'       => (string) $r['source'],
            'created_at'   => $r['created_at'] ?? null,
        ], $rows);

        return ['items' => $items, 'has_more' => $hasMore, 'page' => $page, 'limit' => $limit];
    }

    private function dailyLimit(): int
    {
        return max(0, (int) $this->config->get('rewards.spin.daily_limit', 3));
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }
}
