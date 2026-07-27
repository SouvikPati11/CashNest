<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OfferRepositoryInterface;
use App\Contracts\OfferwallProviderRepositoryInterface;
use App\Contracts\OfferwallServiceInterface;
use App\Contracts\PostbackRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Helpers\Security;

/**
 * Offerwall read/click service.
 *
 * Read-only browsing plus click attribution: creating an `offer_clicks` row with
 * a unique token embedded in the provider redirect so a later postback can be
 * matched back to the user. No crediting happens here (that is postback-driven).
 */
final class OfferwallService implements OfferwallServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private OfferwallProviderRepositoryInterface $providersRepo,
        private OfferRepositoryInterface $offers,
        private PostbackRepositoryInterface $postbacks
    ) {
    }

    public function providers(): array
    {
        return array_map(static fn(array $p): array => [
            'slug'     => (string) $p['slug'],
            'name'     => (string) $p['name'],
            'logo_url' => $p['logo_url'] ?? null,
        ], $this->providersRepo->activeProviders());
    }

    public function offers(array $params): array
    {
        $limit  = $this->resolveLimit($params['limit'] ?? null);
        $page   = max(1, (int) ($params['page'] ?? 1));
        $sort   = is_string($params['sort'] ?? null) ? $params['sort'] : '-created_at';

        [$column, $dir] = match ($sort) {
            'payout_coins'  => ['payout_coins', 'ASC'],
            '-payout_coins' => ['payout_coins', 'DESC'],
            'created_at'    => ['created_at', 'ASC'],
            default         => ['created_at', 'DESC'],
        };

        $filters = [
            'provider' => is_string($params['provider'] ?? null) ? $params['provider'] : null,
            'category' => is_string($params['category'] ?? null) ? $params['category'] : null,
        ];

        $rows = $this->offers->activeOffers($filters, $column, $dir, $limit + 1, ($page - 1) * $limit);

        return $this->paginate($rows, $limit, $page, fn(array $r): array => $this->presentOffer($r));
    }

    public function offerDetail(int $offerId): array
    {
        $row = $this->offers->findOffer($offerId);

        if ($row === null) {
            throw new NotFoundException('Offer not found.');
        }

        return $this->presentOffer($row, true);
    }

    public function cpaOffers(array $params): array
    {
        $limit = $this->resolveLimit($params['limit'] ?? null);
        $page  = max(1, (int) ($params['page'] ?? 1));

        $filters = ['provider' => is_string($params['provider'] ?? null) ? $params['provider'] : null];

        $rows = $this->offers->activeCpaOffers($filters, $limit + 1, ($page - 1) * $limit);

        return $this->paginate($rows, $limit, $page, fn(array $r): array => $this->presentCpa($r));
    }

    public function cpaOfferDetail(int $offerId): array
    {
        $row = $this->offers->findCpaOffer($offerId);

        if ($row === null) {
            throw new NotFoundException('CPA offer not found.');
        }

        return $this->presentCpa($row);
    }

    public function recordClick(int $userId, int $offerId, ?string $ip, ?string $userAgent): array
    {
        $offer = $this->offers->findOffer($offerId);

        if ($offer === null) {
            throw new NotFoundException('Offer not found.');
        }

        $token = Security::uuid4();

        $this->postbacks->createClick([
            'user_id'     => $userId,
            'offer_id'    => $offerId,
            'provider_id' => (int) ($offer['provider_id'] ?? 0),
            'click_token' => $token,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent !== null ? substr($userAgent, 0, 255) : null,
        ]);

        $trackingUrl = is_string($offer['tracking_url'] ?? null) ? $offer['tracking_url'] : '';

        return [
            'redirect_url' => $this->buildRedirect($trackingUrl, $token),
            'click_token'  => $token,
        ];
    }

    private function buildRedirect(string $trackingUrl, string $token): string
    {
        if ($trackingUrl === '') {
            return '';
        }

        if (str_contains($trackingUrl, '{sub_id}')) {
            return str_replace('{sub_id}', rawurlencode($token), $trackingUrl);
        }

        return $trackingUrl . (str_contains($trackingUrl, '?') ? '&' : '?') . 's=' . rawurlencode($token);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presentOffer(array $row, bool $detailed = false): array
    {
        $data = [
            'id'            => (int) $row['id'],
            'provider'      => $row['provider_slug'] ?? null,
            'title'         => $row['title'] ?? null,
            'category'      => $row['category'] ?? null,
            'payout_coins'  => (int) $row['payout_coins'],
            'icon_url'      => $row['icon_url'] ?? null,
        ];

        if ($detailed) {
            $data['description'] = $row['description'] ?? null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presentCpa(array $row): array
    {
        return [
            'id'           => (int) $row['id'],
            'provider'     => $row['provider_slug'] ?? null,
            'title'        => $row['title'] ?? null,
            'goal'         => $row['goal'] ?? null,
            'payout_coins' => (int) $row['payout_coins'],
            'tracking_url' => $row['tracking_url'] ?? null,
        ];
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
