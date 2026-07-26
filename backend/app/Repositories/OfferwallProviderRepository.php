<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\OfferwallProviderRepositoryInterface;

/**
 * Offerwall provider repository.
 */
final class OfferwallProviderRepository extends BaseRepository implements OfferwallProviderRepositoryInterface
{
    protected string $table = 'offerwall_providers';

    public function activeProviders(): array
    {
        return $this->db->select(
            'SELECT * FROM `offerwall_providers` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC'
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }
}
