<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Banner repository contract — reads active `banners`.
 */
interface BannerRepositoryInterface
{
    /**
     * Active, in-window banners for a placement, ordered by `sort_order`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForPlacement(string $placement, string $now): array;
}
