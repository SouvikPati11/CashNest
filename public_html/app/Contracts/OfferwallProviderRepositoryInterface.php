<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Offerwall provider repository contract (offerwall_providers).
 */
interface OfferwallProviderRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeProviders(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;
}
