<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BannerRepositoryInterface;
use App\Contracts\BannerServiceInterface;
use App\Models\Banner;
use App\Resources\BannerResource;

/**
 * Banner service — serves active, in-window banners for a placement, ordered by
 * `sort_order` (ordering is database-driven).
 */
final class BannerService implements BannerServiceInterface
{
    public function __construct(private BannerRepositoryInterface $banners)
    {
    }

    public function forPlacement(string $placement): array
    {
        $placement = $placement !== '' ? $placement : 'home_top';
        $now       = gmdate('Y-m-d H:i:s');

        return array_map(
            static fn(array $row): array => BannerResource::toArray(Banner::fromRow($row)),
            $this->banners->activeForPlacement($placement, $now)
        );
    }
}
