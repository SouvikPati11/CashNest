<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CheckinRepositoryInterface;
use App\Contracts\CheckinServiceInterface;
use App\Contracts\LedgerServiceInterface;
use App\Exceptions\HttpException;
use Core\Contracts\LoggerInterface;

/**
 * Daily check-in service.
 *
 * Streak-aware daily reward. The claim credits coins ONLY through the
 * LedgerService, keyed by a deterministic `reference_id` (`checkin:<user>:<date>`)
 * so a retry never double-credits. One claim per UTC day.
 */
final class CheckinService implements CheckinServiceInterface
{
    public function __construct(
        private CheckinRepositoryInterface $checkins,
        private LedgerServiceInterface $ledger,
        private LoggerInterface $logger
    ) {
    }

    public function status(int $userId): array
    {
        $latest = $this->checkins->findLatestForUser($userId);
        $today  = gmdate('Y-m-d');

        [$streak, $claimedToday, $nextDay] = $this->streakState($latest, $today);

        return [
            'can_claim_today'   => !$claimedToday,
            'current_streak'    => $streak,
            'next_reward_coins' => $this->coinsForDay($nextDay),
            'last_checkin_date' => is_array($latest) && isset($latest['checkin_date'])
                ? (string) $latest['checkin_date']
                : null,
        ];
    }

    public function calendar(int $userId): array
    {
        $latest = $this->checkins->findLatestForUser($userId);
        $today  = gmdate('Y-m-d');
        [$streak] = $this->streakState($latest, $today);

        $ladder = array_map(static fn(array $rung): array => [
            'day'          => (int) $rung['day_number'],
            'coins'        => (int) $rung['coins'],
            'is_milestone' => (bool) ($rung['is_milestone'] ?? false),
        ], $this->checkins->activeLadder());

        return ['ladder' => $ladder, 'current_streak' => $streak];
    }

    public function claim(int $userId): array
    {
        $today = gmdate('Y-m-d');

        if ($this->checkins->findByUserAndDate($userId, $today) !== null) {
            throw new HttpException(409, 'ALREADY_CLAIMED', 'You have already checked in today.');
        }

        $latest    = $this->checkins->findLatestForUser($userId);
        $yesterday = gmdate('Y-m-d', strtotime('-1 day'));

        $streakDay = is_array($latest) && ($latest['checkin_date'] ?? null) === $yesterday
            ? (int) $latest['streak_day'] + 1
            : 1;

        $coins = $this->coinsForDay($streakDay);

        if ($coins <= 0) {
            throw new HttpException(422, 'VALIDATION_ERROR', 'No check-in reward is configured.');
        }

        // Credit ONLY through the ledger; idempotent by reference id.
        $txn = $this->ledger->credit($userId, $coins, 'checkin', 'checkin', "checkin:{$userId}:{$today}");

        $this->checkins->create([
            'user_id'        => $userId,
            'checkin_date'   => $today,
            'streak_day'     => $streakDay,
            'coins_awarded'  => $coins,
            'transaction_id' => $txn->id(),
        ]);

        $this->logger->info('Check-in claimed.', ['user_id' => $userId, 'streak_day' => $streakDay]);

        return [
            'coins_awarded'    => $coins,
            'streak_day'       => $streakDay,
            'new_balance'      => (int) $txn->get('balance_after', 0),
            'transaction_uuid' => $txn->uuid(),
        ];
    }

    /**
     * Derive [current_streak, claimed_today, next_claim_day] from the latest row.
     *
     * @param array<string, mixed>|null $latest
     * @return array{0: int, 1: bool, 2: int}
     */
    private function streakState(?array $latest, string $today): array
    {
        if ($latest === null) {
            return [0, false, 1];
        }

        $lastDate  = (string) ($latest['checkin_date'] ?? '');
        $streakDay = (int) ($latest['streak_day'] ?? 0);
        $yesterday = gmdate('Y-m-d', strtotime('-1 day'));

        if ($lastDate === $today) {
            return [$streakDay, true, $streakDay + 1];
        }

        if ($lastDate === $yesterday) {
            return [$streakDay, false, $streakDay + 1];
        }

        return [0, false, 1]; // streak broken
    }

    /**
     * Coins for a streak day: exact rung, else the highest rung ≤ day (cap).
     */
    private function coinsForDay(int $day): int
    {
        $best = 0;

        foreach ($this->checkins->activeLadder() as $rung) {
            $dayNumber = (int) $rung['day_number'];

            if ($dayNumber === $day) {
                return (int) $rung['coins'];
            }

            if ($dayNumber < $day) {
                $best = (int) $rung['coins'];
            }
        }

        return $best;
    }
}
