<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\OfferRepositoryInterface;

/**
 * Offer repository. Reads `offers` and `cpa_offers`, joined to active providers.
 */
final class OfferRepository extends BaseRepository implements OfferRepositoryInterface
{
    protected string $table = 'offers';

    public function activeOffers(array $filters, string $orderColumn, string $orderDir, int $limit, int $offset): array
    {
        $column = in_array($orderColumn, ['payout_coins', 'created_at'], true) ? $orderColumn : 'created_at';
        $dir    = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $where    = ['o.`is_active` = 1', 'p.`is_active` = 1'];
        $bindings = [];

        if (isset($filters['provider']) && is_string($filters['provider']) && $filters['provider'] !== '') {
            $where[]    = 'p.`slug` = ?';
            $bindings[] = $filters['provider'];
        }

        if (isset($filters['category']) && is_string($filters['category']) && $filters['category'] !== '') {
            $where[]    = 'o.`category` = ?';
            $bindings[] = $filters['category'];
        }

        $sql = 'SELECT o.*, p.`slug` AS provider_slug FROM `offers` o'
            . ' INNER JOIN `offerwall_providers` p ON p.`id` = o.`provider_id`'
            . ' WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY o.`%s` %s, o.`id` DESC LIMIT %d OFFSET %d', $column, $dir, $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function findOffer(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT o.*, p.`slug` AS provider_slug FROM `offers` o'
            . ' INNER JOIN `offerwall_providers` p ON p.`id` = o.`provider_id`'
            . ' WHERE o.`id` = ? AND o.`is_active` = 1 LIMIT 1',
            [$id]
        );
    }

    public function activeCpaOffers(array $filters, int $limit, int $offset): array
    {
        $where    = ['c.`is_active` = 1', 'p.`is_active` = 1'];
        $bindings = [];

        if (isset($filters['provider']) && is_string($filters['provider']) && $filters['provider'] !== '') {
            $where[]    = 'p.`slug` = ?';
            $bindings[] = $filters['provider'];
        }

        $sql = 'SELECT c.*, p.`slug` AS provider_slug FROM `cpa_offers` c'
            . ' INNER JOIN `offerwall_providers` p ON p.`id` = c.`provider_id`'
            . ' WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY c.`id` DESC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function findCpaOffer(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT c.*, p.`slug` AS provider_slug FROM `cpa_offers` c'
            . ' INNER JOIN `offerwall_providers` p ON p.`id` = c.`provider_id`'
            . ' WHERE c.`id` = ? AND c.`is_active` = 1 LIMIT 1',
            [$id]
        );
    }
}
