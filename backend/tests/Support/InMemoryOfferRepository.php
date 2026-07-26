<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\OfferRepositoryInterface;

final class InMemoryOfferRepository implements OfferRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $offers = [];

    /** @var array<int, array<string, mixed>> */
    public array $cpa = [];

    public function seedOffer(array $data): int
    {
        $id = (int) ($data['id'] ?? (count($this->offers) + 1));
        $this->offers[$id] = array_merge(['id' => $id, 'is_active' => 1, 'payout_coins' => 0, 'provider_slug' => 'adgate'], $data, ['id' => $id]);

        return $id;
    }

    public function activeOffers(array $filters, string $orderColumn, string $orderDir, int $limit, int $offset): array
    {
        $rows = array_values(array_filter($this->offers, static fn(array $o): bool => (int) $o['is_active'] === 1));

        return array_slice($rows, $offset, $limit);
    }

    public function findOffer(int $id): ?array
    {
        $o = $this->offers[$id] ?? null;

        return $o !== null && (int) $o['is_active'] === 1 ? $o : null;
    }

    public function activeCpaOffers(array $filters, int $limit, int $offset): array
    {
        $rows = array_values(array_filter($this->cpa, static fn(array $o): bool => (int) $o['is_active'] === 1));

        return array_slice($rows, $offset, $limit);
    }

    public function findCpaOffer(int $id): ?array
    {
        return $this->cpa[$id] ?? null;
    }
}
