<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Offer repository contract (offers + cpa_offers).
 */
interface OfferRepositoryInterface
{
    /**
     * Active offers of active providers, filtered.
     *
     * @param array<string, mixed> $filters Allowed: provider (slug/id), category.
     * @param 'payout_coins'|'created_at' $orderColumn
     * @param 'ASC'|'DESC' $orderDir
     * @return array<int, array<string, mixed>>
     */
    public function activeOffers(array $filters, string $orderColumn, string $orderDir, int $limit, int $offset): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findOffer(int $id): ?array;

    /**
     * @param array<string, mixed> $filters Allowed: provider (id), country.
     * @return array<int, array<string, mixed>>
     */
    public function activeCpaOffers(array $filters, int $limit, int $offset): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findCpaOffer(int $id): ?array;
}
