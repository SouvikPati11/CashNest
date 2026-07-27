<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ReferralRepositoryInterface;
use App\Contracts\ReferralServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Models\Referral;
use Core\Config;
use Core\Contracts\LoggerInterface;

/**
 * Referral read/apply service.
 *
 * Serves the caller's referral overview, referred-user list, and commission
 * history, and lets a user apply a referrer's code once (guarding self-referral
 * and duplicates). Actual bonus/commission crediting is done by
 * ReferralCommissionService through the ledger.
 */
final class ReferralService implements ReferralServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private ReferralRepositoryInterface $referrals,
        private UserServiceInterface $users,
        private UserRepositoryInterface $userRepository,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function overview(int $userId): array
    {
        $user = $this->users->findById($userId);
        $code = $user !== null && is_string($user->get('referral_code')) ? (string) $user->get('referral_code') : '';

        $config           = $this->referrals->activeConfig();
        $commissionPercent = $config !== null ? (string) ($config['commission_percent'] ?? '0.0000') : '0.0000';

        $qualified = $this->referrals->countForReferrerByStatus($userId, Referral::STATUS_QUALIFIED)
            + $this->referrals->countForReferrerByStatus($userId, Referral::STATUS_REWARDED);

        $appUrl = rtrim((string) $this->config->get('app.url', 'http://localhost'), '/');

        return [
            'referral_code'      => $code,
            'referral_link'      => $code === '' ? null : $appUrl . '/r/' . $code,
            'total_referrals'    => $this->referrals->countForReferrer($userId),
            'qualified'          => $qualified,
            'total_earned_coins' => $this->referrals->sumEarningsForReferrer($userId),
            'commission_percent' => $commissionPercent,
        ];
    }

    public function listReferrals(int $userId, array $params): array
    {
        $limit  = $this->resolveLimit($params['limit'] ?? null);
        $page   = max(1, (int) ($params['page'] ?? 1));
        $status = is_string($params['status'] ?? null) ? $params['status'] : null;

        $rows = $this->referrals->listForReferrer($userId, $limit + 1, ($page - 1) * $limit, $status);

        return $this->paginate($rows, $limit, $page, static fn(array $r): array => [
            'referee_name' => $r['referee_name'] ?? null,
            'status'       => (string) $r['status'],
            'joined_at'    => $r['created_at'] ?? null,
        ]);
    }

    public function earnings(int $userId, array $params): array
    {
        $limit = $this->resolveLimit($params['limit'] ?? null);
        $page  = max(1, (int) ($params['page'] ?? 1));

        $rows = $this->referrals->listEarningsForReferrer($userId, $limit + 1, ($page - 1) * $limit);

        return $this->paginate($rows, $limit, $page, static fn(array $r): array => [
            'commission_coins' => (int) $r['commission_coins'],
            'referee_id'       => (int) $r['referee_id'],
            'created_at'       => $r['created_at'] ?? null,
        ]);
    }

    public function apply(int $userId, string $code, ?string $ip): array
    {
        $referrer = $this->userRepository->findByReferralCode($code);

        if ($referrer === null) {
            throw new ValidationException(['referral_code' => ['The referral code is invalid.']]);
        }

        $referrerId = (int) $referrer['id'];

        if ($referrerId === $userId) {
            throw new ForbiddenException('You cannot use your own referral code.', 'FORBIDDEN');
        }

        if ($this->referrals->findByReferee($userId) !== null) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'A referral code has already been applied.');
        }

        $config = $this->referrals->activeConfig();

        $this->referrals->createReferral([
            'referrer_id'         => $referrerId,
            'referee_id'          => $userId,
            'referral_code'       => $code,
            'status'              => Referral::STATUS_PENDING,
            'signup_bonus_coins'  => (int) ($config['referrer_bonus_coins'] ?? 0),
            'referee_bonus_coins' => (int) ($config['referee_bonus_coins'] ?? 0),
            'signup_ip'           => $ip,
        ]);

        $this->logger->info('Referral applied.', ['referrer_id' => $referrerId, 'referee_id' => $userId]);

        return ['applied' => true];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param callable(array<string, mixed>): array<string, mixed> $present
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    private function paginate(array $rows, int $limit, int $page, callable $present): array
    {
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        return [
            'items'    => array_map($present, $rows),
            'has_more' => $hasMore,
            'page'     => $page,
            'limit'    => $limit,
        ];
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }
}
