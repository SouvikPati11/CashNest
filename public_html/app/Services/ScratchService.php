<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\ScratchRepositoryInterface;
use App\Contracts\ScratchServiceInterface;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Models\ScratchCard;
use Core\Contracts\LoggerInterface;

/**
 * Scratch card service.
 *
 * Two-step: reveal (server picks the prize by weighted odds, honouring per-day
 * caps) then claim (credits via LedgerService, keyed `scratch:<cardId>` so a
 * retry never double-credits). Odds/config are never exposed to clients.
 */
final class ScratchService implements ScratchServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private ScratchRepositoryInterface $cards,
        private LedgerServiceInterface $ledger,
        private WeightedPicker $picker,
        private LoggerInterface $logger
    ) {
    }

    public function available(int $userId): array
    {
        return array_map(
            static fn(array $row): array => [
                'id'         => (int) $row['id'],
                'status'     => (string) $row['status'],
                'source'     => (string) $row['source'],
                'expires_at' => $row['expires_at'] ?? null,
            ],
            $this->cards->availableForUser($userId)
        );
    }

    public function reveal(int $userId, int $cardId): array
    {
        $row = $this->cards->findForUser($cardId, $userId);

        if ($row === null) {
            throw new NotFoundException('Scratch card not found.');
        }

        $card = ScratchCard::fromRow($row);
        $now  = time();

        if ($card->status() === ScratchCard::STATUS_CLAIMED) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'This card has already been claimed.');
        }

        if ($card->status() === ScratchCard::STATUS_EXPIRED || $card->isExpired($now)) {
            throw new HttpException(410, 'EXPIRED', 'This scratch card has expired.');
        }

        // Idempotent: re-revealing returns the same reward.
        if ($card->status() === ScratchCard::STATUS_REVEALED) {
            return ['id' => $cardId, 'status' => ScratchCard::STATUS_REVEALED, 'reward_coins' => $card->rewardCoins()];
        }

        $reward = $this->pickReward($userId);

        $this->cards->updateCard($cardId, [
            'status'       => ScratchCard::STATUS_REVEALED,
            'reward_coins' => $reward,
            'revealed_at'  => gmdate('Y-m-d H:i:s'),
        ]);

        return ['id' => $cardId, 'status' => ScratchCard::STATUS_REVEALED, 'reward_coins' => $reward];
    }

    public function claim(int $userId, int $cardId): array
    {
        $row = $this->cards->findForUser($cardId, $userId);

        if ($row === null) {
            throw new NotFoundException('Scratch card not found.');
        }

        $card = ScratchCard::fromRow($row);

        if ($card->status() === ScratchCard::STATUS_EXPIRED || $card->isExpired(time())) {
            throw new HttpException(410, 'EXPIRED', 'This scratch card has expired.');
        }

        if ($card->status() !== ScratchCard::STATUS_REVEALED) {
            $message = $card->status() === ScratchCard::STATUS_CLAIMED
                ? 'This card has already been claimed.'
                : 'Reveal the card before claiming.';
            throw new HttpException(409, 'RESOURCE_CONFLICT', $message);
        }

        $reward = $card->rewardCoins();

        if ($reward <= 0) {
            // "Better luck" card: mark claimed, no ledger movement.
            $this->cards->updateCard($cardId, [
                'status'     => ScratchCard::STATUS_CLAIMED,
                'claimed_at' => gmdate('Y-m-d H:i:s'),
            ]);

            return ['coins_awarded' => 0, 'new_balance' => null, 'transaction_uuid' => null];
        }

        $txn = $this->ledger->credit(
            $userId,
            $reward,
            'scratch',
            'scratch',
            "scratch:{$cardId}",
            ['source_id' => $cardId]
        );

        $this->cards->updateCard($cardId, [
            'status'         => ScratchCard::STATUS_CLAIMED,
            'claimed_at'     => gmdate('Y-m-d H:i:s'),
            'transaction_id' => $txn->id(),
        ]);

        $this->logger->info('Scratch card claimed.', ['user_id' => $userId, 'card_id' => $cardId]);

        return [
            'coins_awarded'    => $reward,
            'new_balance'      => (int) $txn->get('balance_after', 0),
            'transaction_uuid' => $txn->uuid(),
        ];
    }

    public function history(int $userId, array $params): array
    {
        $limit  = $this->resolveLimit($params['limit'] ?? null);
        $page   = max(1, (int) ($params['page'] ?? 1));
        $status = is_string($params['status'] ?? null) ? $params['status'] : null;

        $rows = $this->cards->historyForUser($userId, $limit + 1, ($page - 1) * $limit, $status);

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(static fn(array $r): array => [
            'id'           => (int) $r['id'],
            'status'       => (string) $r['status'],
            'source'       => (string) $r['source'],
            'reward_coins' => $r['reward_coins'] === null ? null : (int) $r['reward_coins'],
            'created_at'   => $r['created_at'] ?? null,
        ], $rows);

        return ['items' => $items, 'has_more' => $hasMore, 'page' => $page, 'limit' => $limit];
    }

    /**
     * Weighted prize selection honouring per-tier daily caps.
     */
    private function pickReward(int $userId): int
    {
        $today = gmdate('Y-m-d');

        $eligible = array_values(array_filter(
            $this->cards->activePool(),
            function (array $tier) use ($userId, $today): bool {
                $limit = $tier['daily_limit'] ?? null;

                if ($limit === null) {
                    return true;
                }

                $won = $this->cards->countWonTodayByReward($userId, (int) $tier['reward_coins'], $today);

                return $won < (int) $limit;
            }
        ));

        $picked = $this->picker->pick($eligible);

        return $picked === null ? 0 : (int) $picked['reward_coins'];
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }
}
