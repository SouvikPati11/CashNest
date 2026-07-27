<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Banner service contract — serves active banners for a placement.
 */
interface BannerServiceInterface
{
    /**
     * Active, in-window banners for a placement, ordered by `sort_order`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPlacement(string $placement): array;
}
